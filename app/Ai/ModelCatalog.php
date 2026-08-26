<?php

namespace App\Ai;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * L'elenco dei modelli davvero disponibili sull'account, letto da
 * `GET /v1/models` e tenuto in cache un giorno.
 *
 * Serve a due cose: dare un nome leggibile a un id nel menu della chat, e far
 * vedere quando esce un modello nuovo — SENZA accenderlo da solo. Un modello
 * diventa scegliibile quando qualcuno gli mette un prezzo in App\Ai\Pricing,
 * che resta il cancello con una persona davanti.
 *
 * Senza chiave o senza rete torna una lista vuota: il menu funziona lo stesso
 * con i soli modelli prezzati, e i test non toccano la rete.
 */
class ModelCatalog
{
    private const ENDPOINT = 'https://api.anthropic.com/v1/models';

    private const API_VERSION = '2023-06-01';

    private const CACHE_KEY = 'anthropic.models.catalog';

    /**
     * `claude-haiku-4-5` e `claude-haiku-4-5-20251001` sono lo stesso modello.
     *
     * Nella configurazione mettiamo l'alias, che segue da solo le versioni
     * nuove; il catalogo di Anthropic riporta anche la variante datata. Il
     * confronto per stringa non lo sa, e si vedrebbe in due punti: due voci
     * identiche nel menu, e un avviso «modelli nuovi» che segnala per sempre
     * un modello che stiamo già usando — cioè grida al lupo, che è il modo di
     * far ignorare anche la volta in cui il lupo c'è.
     */
    public static function isDatedVariantOf(string $candidate, string $alias): bool
    {
        return (bool) preg_match('/^'.preg_quote($alias, '/').'-\d{8}$/', $candidate);
    }

    /** @param  array<int, string>  $aliases */
    public static function isKnownAs(string $candidate, array $aliases): bool
    {
        foreach ($aliases as $alias) {
            if ($candidate === $alias
                || self::isDatedVariantOf($candidate, $alias)
                || self::isDatedVariantOf($alias, $candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * I modelli disponibili come [id => nome]. Vuoto se non raggiungibili.
     *
     * @return array<string, string>
     */
    public function names(): array
    {
        $chiave = (string) config('ai.key', '');

        if ($chiave === '') {
            return [];
        }

        return Cache::remember(self::CACHE_KEY, now()->addDay(), function () use ($chiave): array {
            try {
                $risposta = Http::withHeaders([
                    'x-api-key' => $chiave,
                    'anthropic-version' => self::API_VERSION,
                ])->timeout(10)->get(self::ENDPOINT, ['limit' => 1000]);

                if (! $risposta->successful()) {
                    return [];
                }

                $modelli = [];
                foreach ((array) $risposta->json('data', []) as $modello) {
                    $id = (string) ($modello['id'] ?? '');
                    if ($id !== '') {
                        $modelli[$id] = (string) ($modello['display_name'] ?? $id);
                    }
                }

                return $modelli;
            } catch (Throwable) {
                return [];
            }
        });
    }
}
