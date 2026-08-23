<?php

namespace App\Finance;

use App\Models\CategoryRule;
use App\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * Sorts movements into categories using the rules the user has accumulated.
 *
 * This runs before anything is sent to a model, and on a real statement it is
 * most of the work: a bank statement is largely the same twenty merchants
 * every month, and matching them against stored rules is instant and free.
 * The model is for the tail — the once-off purchases nobody has a rule for.
 *
 * A category chosen by a person is never overwritten. That is what makes
 * correcting one worth the effort: it stays corrected.
 */
class Categoriser
{
    /**
     * @return array{categorised: int, untouched: int}
     */
    public function run(bool $onlyUncategorised = true): array
    {
        $rules = CategoryRule::query()->with('category')->orderBy('priority')->get();

        if ($rules->isEmpty()) {
            return ['categorised' => 0, 'untouched' => 0];
        }

        $movements = Transaction::query()
            ->where('category_locked', false)
            ->when($onlyUncategorised, fn ($q) => $q->whereNull('category_id'))
            ->get();

        $categorised = 0;

        foreach ($movements as $movement) {
            $rule = $this->firstMatch($rules, $movement);

            if ($rule === null) {
                continue;
            }

            $movement->update(['category_id' => $rule->category_id]);
            $rule->increment('times_applied');
            $categorised++;
        }

        return [
            'categorised' => $categorised,
            'untouched' => $movements->count() - $categorised,
        ];
    }

    /**
     * @param  Collection<int, CategoryRule>  $rules
     */
    private function firstMatch(Collection $rules, Transaction $movement): ?CategoryRule
    {
        // Both the tidied text and the bank's original: cleaning strips
        // prefixes like "Operazione presso", and a rule written against what
        // the user sees must still match, as must one written against the raw.
        $haystacks = array_filter([
            $movement->description,
            $movement->raw_description,
            $movement->counterparty,
            // La causale estesa: è lì che sta il mittente di un bonifico, e
            // "ACCREDITO BONIFICO ISTANTANEO" da solo non dice niente.
            $movement->notes,
        ]);

        $isIncoming = (float) $movement->amount > 0;

        foreach ($rules as $rule) {
            /*
             * Il segno decide quali categorie sono ammissibili.
             *
             * Un accredito non è "Software e servizi" col meno davanti: è
             * un'entrata, e va in una categoria di entrata. Senza questo
             * controllo un rimborso finisce a ridurre una voce di spesa, e la
             * categoria mostra un totale che non corrisponde a quanto è stato
             * speso davvero.
             */
            $kind = $rule->category?->kind;

            if ($kind === 'income' && ! $isIncoming) {
                continue;
            }

            if ($kind === 'expense' && $isIncoming) {
                continue;
            }

            foreach ($haystacks as $haystack) {
                if ($rule->matches($haystack)) {
                    return $rule;
                }
            }
        }

        return null;
    }

    /**
     * Turn a correction into a rule, so the same merchant never has to be
     * corrected twice.
     *
     * The pattern is the merchant's name with the noise stripped: card
     * statements append a city, a store number and a transaction reference
     * that differ every time, and a rule containing them would match exactly
     * once — the movement it was made from.
     */
    public function learnFrom(Transaction $movement): ?CategoryRule
    {
        if ($movement->category_id === null) {
            return null;
        }

        $pattern = $this->merchantOf($movement->description ?? $movement->raw_description);

        if (mb_strlen($pattern) < 4) {
            // Too short to be anyone's name: it would catch half the statement.
            return null;
        }

        $existing = CategoryRule::query()
            ->whereRaw('LOWER(pattern) = ?', [mb_strtolower($pattern)])
            ->first();

        if ($existing !== null) {
            $existing->update(['category_id' => $movement->category_id]);

            return $existing;
        }

        return CategoryRule::create([
            'category_id' => $movement->category_id,
            'pattern' => $pattern,
            'match_type' => 'contains',
            'priority' => 50,
            'auto_learned' => true,
        ]);
    }

    /**
     * The recognisable part of a statement line: the merchant, without the receipt.
     *
     * It stops at the first word containing digits rather than removing those
     * words and joining what is left. "ESSELUNGA 4471 BRESCIA" would otherwise
     * become the pattern "ESSELUNGA BRESCIA", which no longer appears anywhere
     * in next month's "ESSELUNGA 4471 BRESCIA" — the store number sits between
     * the two words. Everything up to the first number is the name; what
     * follows is the till.
     */
    private function merchantOf(string $description): string
    {
        $words = [];

        foreach (array_filter(explode(' ', preg_replace('/\s+/', ' ', trim($description)) ?? '')) as $word) {
            if (preg_match('/\d/', $word) === 1) {
                break;
            }

            $words[] = $word;

            if (count($words) === 3) {
                break;
            }
        }

        return trim(implode(' ', $words));
    }
}
