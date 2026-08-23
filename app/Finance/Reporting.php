<?php

namespace App\Finance;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;

/**
 * Le query su cui poggiano i riquadri della dashboard.
 *
 * Stanno insieme per una ragione sola: ogni cifra mostrata deve escludere i
 * giroconti. Ripetere quella condizione in ogni widget è il modo in cui, prima
 * o poi, un riquadro finisce per contare i pagamenti delle carte e mostra un
 * numero diverso da quello accanto.
 */
class Reporting
{
    /**
     * Movimenti che rappresentano denaro davvero speso o incassato, dentro il
     * periodo e sui conti che si stanno guardando.
     *
     * @param  array<string, mixed>|null  $filters
     */
    public static function realMovements(?array $filters = null): Builder
    {
        $period = Period::fromFilters($filters);

        return Transaction::query()
            ->whereDoesntHave('category', fn (Builder $q) => $q->where('kind', 'transfer'))
            ->when($period->from, fn (Builder $q, $from) => $q->where('booked_on', '>=', $from))
            ->when($period->to, fn (Builder $q, $to) => $q->where('booked_on', '<=', $to))
            ->when(
                filled($filters['accounts'] ?? null),
                fn (Builder $q) => $q->whereIn('account_id', (array) $filters['accounts']),
            );
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    public static function expenses(?array $filters = null): Builder
    {
        return static::realMovements($filters)->where('amount', '<', 0);
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    public static function income(?array $filters = null): Builder
    {
        return static::realMovements($filters)->where('amount', '>', 0);
    }

    public static function euro(float $amount): string
    {
        return number_format($amount, 2, ',', '.').' €';
    }
}
