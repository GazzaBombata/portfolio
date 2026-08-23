<?php

namespace App\Ai;

use RuntimeException;

/**
 * Quanto costa un modello, in dollari per milione di token.
 *
 * Un modello senza prezzo qui non viene chiamato. È una scelta: una chiamata a
 * un modello non listato spende soldi che nessun conteggio vedrà, e il primo
 * momento in cui te ne accorgi è la fattura.
 */
class Pricing
{
    /** @var array<string, array{input: float, output: float, cache_read: float}> */
    private const LISTINO = [
        'claude-opus-5' => ['input' => 5.00, 'output' => 25.00, 'cache_read' => 0.50],
        'claude-sonnet-5' => ['input' => 3.00, 'output' => 15.00, 'cache_read' => 0.30],
        'claude-haiku-4-5' => ['input' => 1.00, 'output' => 5.00, 'cache_read' => 0.10],
    ];

    public static function isPriced(string $model): bool
    {
        return isset(self::LISTINO[$model]);
    }

    public static function ensurePriced(string $model): void
    {
        if (! self::isPriced($model)) {
            throw new RuntimeException(
                "Il modello «{$model}» non ha un prezzo configurato in App\\Ai\\Pricing. "
                .'Aggiungilo prima di usarlo, altrimenti la spesa non viene conteggiata.'
            );
        }
    }

    public static function cost(string $model, int $input, int $output, int $cacheRead = 0): float
    {
        self::ensurePriced($model);

        $p = self::LISTINO[$model];

        return round(
            $input / 1_000_000 * $p['input']
            + $output / 1_000_000 * $p['output']
            + $cacheRead / 1_000_000 * $p['cache_read'],
            6,
        );
    }
}
