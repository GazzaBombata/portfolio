<?php

namespace App\Finance;

use App\Finance\Ai\Classifier;
use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * Copre la coda che le regole non prendono.
 *
 * Il lavoro è raggruppare prima di chiedere: quattrocento movimenti da
 * classificare sono centottanta esercenti distinti, e più della metà si
 * ripetono. Quello che torna dal modello diventa una REGOLA, non una
 * classificazione singola — così vale anche per l'estratto conto del mese
 * prossimo, senza pagare di nuovo la stessa domanda.
 */
class AiCategoriser
{
    public function __construct(
        private readonly Classifier $classifier,
        private readonly Categoriser $categoriser,
    ) {}

    /**
     * @return array{merchants: int, rules: int, categorised: int, undecided: int}
     */
    public function run(?int $limit = null): array
    {
        /*
         * Due passate separate, una per il denaro che esce e una per quello che
         * entra.
         *
         * Sono due domande diverse — "per cosa l'ho speso" e "da dove è
         * arrivato" — e mescolarle significa offrire al modello solo categorie
         * di spesa anche per uno stipendio. Fatto una volta: le entrate
         * restavano scoperte, tranne otto finite dentro voci di spesa.
         */
        $totali = ['merchants' => 0, 'rules' => 0, 'categorised' => 0, 'undecided' => 0];

        foreach (['expense', 'income'] as $kind) {
            $esito = $this->runFor($kind, $limit);

            foreach ($totali as $chiave => $valore) {
                $totali[$chiave] = $valore + $esito[$chiave];
            }
        }

        return $totali;
    }

    /**
     * @return array{merchants: int, rules: int, categorised: int, undecided: int}
     */
    private function runFor(string $kind, ?int $limit): array
    {
        $merchants = $this->uncategorisedMerchants($kind);

        if ($limit !== null) {
            $merchants = $merchants->take($limit);
        }

        if ($merchants->isEmpty()) {
            return ['merchants' => 0, 'rules' => 0, 'categorised' => 0, 'undecided' => 0];
        }

        $categories = Category::query()
            ->where('kind', $kind)
            ->get()
            ->mapWithKeys(fn (Category $c): array => [$c->fullName() => $c->id]);

        if ($categories->isEmpty()) {
            return ['merchants' => 0, 'rules' => 0, 'categorised' => 0, 'undecided' => 0];
        }

        $decisions = [];

        foreach ($merchants->chunk((int) config('ai.batch_size', 40)) as $chunk) {
            $decisions += $this->classifier->classify($chunk->values()->all(), $categories->keys()->all());
        }

        $rules = 0;

        foreach ($decisions as $merchant => $categoryName) {
            $categoryId = $categories[$categoryName] ?? null;

            if ($categoryId === null || mb_strlen((string) $merchant) < 4) {
                continue;
            }

            CategoryRule::firstOrCreate(
                ['pattern' => $merchant],
                [
                    'category_id' => $categoryId,
                    'match_type' => 'contains',
                    // Dietro alle regole scritte a mano: quelle sono decisioni,
                    // queste sono proposte.
                    'priority' => 200,
                    'auto_learned' => true,
                ],
            );

            $rules++;
        }

        $esito = $this->categoriser->run();

        return [
            'merchants' => $merchants->count(),
            'rules' => $rules,
            'categorised' => $esito['categorised'],
            'undecided' => $merchants->count() - count($decisions),
        ];
    }

    /**
     * Gli esercenti ancora scoperti, con quel tanto di contesto che serve a
     * distinguere un'abitudine da un acquisto: quante volte e per quanto.
     *
     * @return Collection<int, array{merchant: string, samples: array<int, string>, total: string, count: int}>
     */
    private function uncategorisedMerchants(string $kind): Collection
    {
        $gruppi = [];

        $movimenti = Transaction::query()
            ->whereNull('category_id')
            ->where('category_locked', false)
            ->where('amount', $kind === 'income' ? '>' : '<', 0)
            ->get();

        foreach ($movimenti as $movement) {
            $merchant = $this->nameFor($movement);

            if (mb_strlen($merchant) < 4) {
                continue;
            }

            $gruppi[$merchant]['count'] = ($gruppi[$merchant]['count'] ?? 0) + 1;
            $gruppi[$merchant]['total'] = ($gruppi[$merchant]['total'] ?? 0) + (float) $movement->amount;
            // Il campione utile è la causale estesa quando c'è: contiene il
            // mittente e il riferimento alla fattura, cioè le uniche due cose
            // che dicono di che entrata si tratta.
            $gruppi[$merchant]['samples'][] = trim(mb_substr(
                (string) ($movement->notes ?: $movement->description), 0, 160
            ));
        }

        return collect($gruppi)
            ->map(fn (array $g, string $merchant): array => [
                'merchant' => $merchant,
                'count' => $g['count'],
                'total' => number_format($g['total'], 2, ',', '.').' €',
                'samples' => array_slice(array_unique($g['samples']), 0, 3),
            ])
            // I più frequenti per primi: se qualcosa va storto a metà, si è
            // comunque coperto quello che pesa di più.
            ->sortByDesc('count')
            ->values();
    }

    /**
     * Con che nome chiamare questo movimento quando lo si chiede al modello.
     *
     * Per un pagamento in negozio è l'esercente. Per un bonifico l'esercente
     * non esiste: la descrizione dice "ACCREDITO BONIFICO ISTANTANEO" e basta,
     * uguale per tutti, mentre chi ha mandato i soldi sta nella causale estesa.
     * Raggruppare per quella riga significherebbe un unico gruppo enorme con
     * dentro clienti diversi; raggruppare per mittente dà gruppi veri, e le
     * regole che ne nascono servono a qualcosa.
     */
    private function nameFor(Transaction $movement): string
    {
        $sender = $this->senderOf((string) $movement->notes);

        if ($sender !== '') {
            return $sender;
        }

        return $this->merchantOf((string) ($movement->description ?? $movement->raw_description));
    }

    /** Il mittente dichiarato in una causale di bonifico. */
    private function senderOf(string $notes): string
    {
        if (preg_match('/MITT\.\s*:\s*(.+?)\s+(?:BENEF|BIC|COD|$)/iu', $notes, $matches) !== 1) {
            return '';
        }

        return trim(preg_replace('/\s+/', ' ', $matches[1]) ?? '');
    }

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
