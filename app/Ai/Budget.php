<?php

namespace App\Ai;

use App\Models\AiUsage;
use RuntimeException;

/**
 * Il tetto di spesa mensile, controllato prima di ogni chiamata.
 *
 * Il punto non è risparmiare qualche euro: è che un ciclo che non converge, o
 * una classificazione lanciata due volte su un anno di movimenti, spende in
 * silenzio finché non arriva l'estratto della carta. Un limite superato è un
 * errore esplicito con dentro il numero, non una sorpresa a fine mese.
 */
class Budget
{
    public static function spentThisMonth(): float
    {
        return (float) AiUsage::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('cost');
    }

    public static function limit(): float
    {
        return (float) config('ai.monthly_limit', 0);
    }

    public static function remaining(): float
    {
        $limite = self::limit();

        return $limite <= 0 ? INF : max(0, $limite - self::spentThisMonth());
    }

    /** Lanciata prima di ogni chiamata pagata. */
    public static function guard(): void
    {
        $limite = self::limit();

        if ($limite <= 0) {
            return;
        }

        $speso = self::spentThisMonth();

        if ($speso >= $limite) {
            throw new RuntimeException(sprintf(
                'Tetto di spesa AI raggiunto: %s su %s di questo mese. '
                .'Alza AI_MONTHLY_LIMIT nel .env, oppure aspetta il mese prossimo.',
                self::dollari($speso),
                self::dollari($limite),
            ));
        }
    }

    public static function record(string $kind, string $model, object $usage, ?int $userId = null): AiUsage
    {
        $input = (int) ($usage->inputTokens ?? 0);
        $output = (int) ($usage->outputTokens ?? 0);
        $cacheRead = (int) ($usage->cacheReadInputTokens ?? 0);
        // Non è dentro inputTokens: senza questa riga la cache sembrerebbe gratis.
        $cacheWrite = (int) ($usage->cacheCreationInputTokens ?? 0);

        return AiUsage::create([
            'user_id' => $userId ?? auth()->id(),
            'kind' => $kind,
            'model' => $model,
            'input_tokens' => $input,
            'output_tokens' => $output,
            'cache_read_tokens' => $cacheRead,
            'cache_write_tokens' => $cacheWrite,
            'cost' => Pricing::cost($model, $input, $output, $cacheRead, $cacheWrite),
        ]);
    }

    public static function dollari(float $importo): string
    {
        return '$'.number_format($importo, 2, ',', '.');
    }
}
