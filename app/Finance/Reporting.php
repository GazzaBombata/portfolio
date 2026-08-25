<?php

namespace App\Finance;

use App\Models\Category;
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
            )
            ->when(
                filled($filters['exclude_categories'] ?? null),
                fn (Builder $q) => $q->whereNotIn('category_id', static::withChildren((array) $filters['exclude_categories'])),
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

    /**
     * Le categorie indicate più le loro sottocategorie.
     *
     * Chi esclude «Lavoro» non intende tenere dentro «Lavoro · Software»: un
     * filtro che lascia passare i figli produce un totale che nessuno ha
     * chiesto e che sembra comunque plausibile.
     *
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    private static function withChildren(array $ids): array
    {
        $ids = array_map('intval', $ids);

        $figli = Category::query()->whereIn('parent_id', $ids)->pluck('id')->all();

        return array_values(array_unique([...$ids, ...$figli]));
    }

    /**
     * I nomi delle categorie escluse, per poterlo scrivere sui riquadri.
     *
     * Un totale filtrato che sembra completo è il modo più facile di leggere
     * male i propri numeri: l'esclusione va detta dove si legge il numero, non
     * solo dove si imposta il filtro.
     *
     * @param  array<string, mixed>|null  $filters
     */
    public static function excludedLabel(?array $filters): string
    {
        if (blank($filters['exclude_categories'] ?? null)) {
            return '';
        }

        $nomi = Category::query()
            ->whereIn('id', (array) $filters['exclude_categories'])
            ->orderBy('name')
            ->pluck('name');

        return $nomi->isEmpty() ? '' : ' Escluse: '.$nomi->implode(', ').'.';
    }

    public static function euro(float $amount): string
    {
        return number_format($amount, 2, ',', '.').' €';
    }
}
