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
        $merchants = $this->uncategorisedMerchants();

        if ($limit !== null) {
            $merchants = $merchants->take($limit);
        }

        if ($merchants->isEmpty()) {
            return ['merchants' => 0, 'rules' => 0, 'categorised' => 0, 'undecided' => 0];
        }

        $categories = Category::query()
            ->where('kind', 'expense')
            ->get()
            ->mapWithKeys(fn (Category $c): array => [$c->fullName() => $c->id]);

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
    private function uncategorisedMerchants(): Collection
    {
        $gruppi = [];

        foreach (Transaction::query()->whereNull('category_id')->where('category_locked', false)->get() as $movement) {
            $merchant = $this->merchantOf((string) ($movement->description ?? $movement->raw_description));

            if (mb_strlen($merchant) < 4) {
                continue;
            }

            $gruppi[$merchant]['count'] = ($gruppi[$merchant]['count'] ?? 0) + 1;
            $gruppi[$merchant]['total'] = ($gruppi[$merchant]['total'] ?? 0) + (float) $movement->amount;
            $gruppi[$merchant]['samples'][] = (string) $movement->description;
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
