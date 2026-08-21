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
    /** Movimenti che rappresentano denaro davvero speso o incassato. */
    public static function realMovements(): Builder
    {
        return Transaction::query()
            ->whereDoesntHave('category', fn (Builder $q) => $q->where('kind', 'transfer'));
    }

    public static function expenses(): Builder
    {
        return static::realMovements()->where('amount', '<', 0);
    }

    public static function income(): Builder
    {
        return static::realMovements()->where('amount', '>', 0);
    }

    public static function euro(float $amount): string
    {
        return number_format($amount, 2, ',', '.').' €';
    }
}
