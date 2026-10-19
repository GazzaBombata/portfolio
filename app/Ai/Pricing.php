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
    /**
     * Prezzo per milione di token. La scrittura in cache costa 1,25× l'input e
     * la lettura di solito un decimo: è il conto che decide se conviene
     * cachare, quindi sta scritto qui invece che dedotto ogni volta — e infatti
     * non è sempre un decimo.
     *
     * @var array<string, array{input: float, output: float, cache_read: float, cache_write: float}>
     */
    private const LISTINO = [
        // La lettura in cache di Opus 5.5 è un ventesimo dell'input, non un decimo.
        'claude-opus-5-5' => ['input' => 4.00, 'output' => 20.00, 'cache_read' => 0.20, 'cache_write' => 5.00],
        'claude-sonnet-5-5' => ['input' => 2.00, 'output' => 10.00, 'cache_read' => 0.20, 'cache_write' => 2.50],
        'claude-haiku-5-5' => ['input' => 0.10, 'output' => 0.50, 'cache_read' => 0.01, 'cache_write' => 0.125],
    ];

    /**
     * Oltre questa soglia di prompt alcuni modelli cambiano listino per
     * l'INTERA chiamata, non solo per i token in più.
     *
     * Haiku 5.5 costa un decimo di Haiku 4.5 sotto i 100K token e la metà
     * sopra. La chat non ci arriva — la storia è tagliata a dodici messaggi —
     * ma un conteggio che lo ignorasse sbaglierebbe di cinque volte proprio
     * nella chiamata più cara, cioè dove il tetto mensile serve.
     *
     * @var array<string, array{soglia: int, input: float, output: float, cache_read: float, cache_write: float}>
     */
    private const PROMPT_LUNGO = [
        'claude-haiku-5-5' => ['soglia' => 100_000, 'input' => 0.50, 'output' => 2.50, 'cache_read' => 0.05, 'cache_write' => 0.625],
    ];

    /**
     * Gli id in listino, cioè quelli che si possono scegliere nella chat.
     *
     * @return array<int, string>
     */
    public static function models(): array
    {
        return array_keys(self::LISTINO);
    }

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

    public static function cost(string $model, int $input, int $output, int $cacheRead = 0, int $cacheWrite = 0): float
    {
        self::ensurePriced($model);

        $p = self::LISTINO[$model];

        $lungo = self::PROMPT_LUNGO[$model] ?? null;
        if ($lungo !== null && $input + $cacheRead + $cacheWrite > $lungo['soglia']) {
            $p = $lungo;
        }

        return round(
            $input / 1_000_000 * $p['input']
            + $output / 1_000_000 * $p['output']
            + $cacheRead / 1_000_000 * $p['cache_read']
            + $cacheWrite / 1_000_000 * $p['cache_write'],
            6,
        );
    }
}
