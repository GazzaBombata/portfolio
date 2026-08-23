<?php

namespace App\Finance;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * Finds money that only moved between your own accounts.
 *
 * This is the difference between "how much did I spend" and a number that is
 * simply wrong. Every purchase on a credit card is already recorded one by one;
 * when the statement is settled from the current account, that single large
 * outgoing repeats all of them. Left alone it inflates exactly the months you
 * spent the most.
 *
 * The match is made on facts, not on wording: an outgoing from one account and
 * an incoming on a different one, for the same amount, within a few days.
 *
 * Where two candidates would fit the same transaction it pairs neither. A
 * wrongly matched pair removes two real movements from the totals and says
 * nothing about it, which is worse than leaving a transfer counted — that one
 * at least is visible in the list.
 */
class TransferMatcher
{
    /** Days a transfer may take to land on the other side. */
    private const WINDOW_DAYS = 5;

    /*
     * How banks describe money being moved rather than spent.
     *
     * Amount and date alone are not enough: a €35 membership fee and a €35
     * PayPal credit five days apart look exactly like a transfer, and pairing
     * them removed two genuine movements from the totals. At least one of the
     * two sides must also SAY it is a transfer — which is a fact printed on the
     * statement, not a guess about intent.
     */
    private const TRANSFER_WORDS = [
        'bonifico', 'giroconto', 'postagiro', 'estratto conto carta',
        'addebito diretto sepa', 'domiciliazione', 'pagamento ricevuto',
        'pagamento carta', 'saldo carta', 'ricarica', 'trasferimento',
    ];

    /**
     * @return array{paired: int, ambiguous: int}
     */
    public function run(): array
    {
        $category = $this->transferCategory();

        $candidates = Transaction::query()
            ->where('category_locked', false)
            ->where(fn ($q) => $q->whereNull('category_id')->orWhere('category_id', '!=', $category->id))
            ->orderBy('booked_on')
            ->get();

        $outgoing = $candidates->filter(fn (Transaction $t): bool => (float) $t->amount < 0);
        $incoming = $candidates->filter(fn (Transaction $t): bool => (float) $t->amount > 0);

        $paired = 0;
        $ambiguous = 0;
        $used = [];

        foreach ($outgoing as $out) {
            $matches = $this->matchesFor($out, $incoming, $used);

            if ($matches->count() !== 1) {
                // Zero: an ordinary expense. More than one: not decidable from
                // the data, and guessing would hide two real movements.
                $ambiguous += $matches->count() > 1 ? 1 : 0;

                continue;
            }

            $in = $matches->first();

            if (! $this->soundsLikeTransfer($out) && ! $this->soundsLikeTransfer($in)) {
                // Somigliano a un travaso solo per importo e data. Restano
                // contati, e restano visibili nell'elenco.
                $ambiguous++;

                continue;
            }

            $used[$in->id] = true;

            foreach ([$out, $in] as $t) {
                $t->update(['category_id' => $category->id]);
            }

            $paired++;
        }

        return ['paired' => $paired, 'ambiguous' => $ambiguous];
    }

    /**
     * Le coppie che sembrano un travaso ma che non sono decidibili dai dati:
     * o perché più di un movimento combacia, o perché nessuno dei due lo
     * dichiara nelle parole della banca.
     *
     * Restano contate nei totali — è il verso giusto in cui sbagliare — ma
     * senza una schermata che le mostri nessuno le guarderebbe mai, e il
     * conteggio in fondo a un comando è un numero che scorre via.
     *
     * @return array<int, array{out: Transaction, candidates: Collection<int, Transaction>, reason: string}>
     */
    public function pending(): array
    {
        $category = $this->transferCategory();

        $candidates = Transaction::query()
            ->where('category_locked', false)
            ->where(fn ($q) => $q->whereNull('category_id')->orWhere('category_id', '!=', $category->id))
            ->with(['account', 'category'])
            ->orderBy('booked_on')
            ->get();

        $outgoing = $candidates->filter(fn (Transaction $t): bool => (float) $t->amount < 0);
        $incoming = $candidates->filter(fn (Transaction $t): bool => (float) $t->amount > 0);

        $dubbi = [];

        foreach ($outgoing as $out) {
            $matches = $this->matchesFor($out, $incoming, []);

            if ($matches->isEmpty()) {
                continue;
            }

            if ($matches->count() > 1) {
                $dubbi[] = ['out' => $out, 'candidates' => $matches, 'reason' => 'più di un movimento combacia'];

                continue;
            }

            $in = $matches->first();

            if (! $this->soundsLikeTransfer($out) && ! $this->soundsLikeTransfer($in)) {
                $dubbi[] = [
                    'out' => $out,
                    'candidates' => $matches,
                    'reason' => 'combaciano per importo e data, ma nessuno dei due dice di essere un travaso',
                ];
            }
        }

        return $dubbi;
    }

    /** Marca a mano una coppia come giroconto. */
    public function confirm(Transaction $out, Transaction $in): void
    {
        $category = $this->transferCategory();

        foreach ([$out, $in] as $t) {
            // Deciso da una persona: la passata automatica non lo tocca più.
            $t->update(['category_id' => $category->id, 'category_locked' => true]);
        }
    }

    /**
     * @param  Collection<int, Transaction>  $incoming
     * @param  array<int, bool>  $used
     * @return Collection<int, Transaction>
     */
    private function matchesFor(Transaction $out, Collection $incoming, array $used): Collection
    {
        $amount = abs((float) $out->amount);
        $from = $out->booked_on->copy()->subDays(self::WINDOW_DAYS);
        $to = $out->booked_on->copy()->addDays(self::WINDOW_DAYS);

        return $incoming->filter(function (Transaction $in) use ($out, $amount, $from, $to, $used): bool {
            // Money that leaves and returns to the same account is not a
            // transfer between accounts; it is a refund, and it is spending.
            if ($in->account_id === $out->account_id) {
                return false;
            }

            if (isset($used[$in->id])) {
                return false;
            }

            return abs((float) $in->amount) === $amount
                && $in->booked_on->betweenIncluded($from, $to);
        })->values();
    }

    private function soundsLikeTransfer(Transaction $transaction): bool
    {
        $text = mb_strtolower($transaction->raw_description.' '.$transaction->description);

        foreach (self::TRANSFER_WORDS as $word) {
            if (str_contains($text, $word)) {
                return true;
            }
        }

        return false;
    }

    private function transferCategory(): Category
    {
        return Category::firstOrCreate(
            ['parent_id' => null, 'name' => 'Giroconti'],
            ['kind' => 'transfer'],
        );
    }
}
