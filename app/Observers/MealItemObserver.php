<?php

namespace App\Observers;

use App\Models\MealItem;

/**
 * Rimette in pari il totale del pasto ogni volta che un ingrediente cambia.
 *
 * Il totale non si chiede e non si ridigita: è la somma delle righe, e vale
 * per le calorie come per i tre macro. Un totale scritto a mano accanto a
 * delle righe è un secondo posto per la stessa cosa, cioè quello che prima o
 * poi le contraddice — ed è esattamente la contraddizione che ha reso
 * invisibile una stima sbagliata per settimane.
 */
class MealItemObserver
{
    public function saved(MealItem $item): void
    {
        $item->meal?->recalculateFromItems();
    }

    public function deleted(MealItem $item): void
    {
        $item->meal?->recalculateFromItems();
    }
}
