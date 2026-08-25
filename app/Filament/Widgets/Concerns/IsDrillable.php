<?php

namespace App\Filament\Widgets\Concerns;

/**
 * Un grafico su cui si può cliccare per vedere cosa c'è dentro.
 *
 * Il grafico dice «Bollette: 1.855 €» e la domanda successiva è sempre la
 * stessa: quali bollette. Senza il clic bisogna scorrere fino alla tabella e
 * rifare a mano il filtro che si è appena guardato.
 *
 * Chi lo usa deve esporre, per ogni fetta o barra, quali categorie contiene:
 * l'ordine deve corrispondere esattamente a quello delle etichette, perché il
 * browser sa solo che è stato cliccato l'ennesimo elemento.
 */
trait IsDrillable
{
    /**
     * Le categorie dietro ogni elemento del grafico, nello stesso ordine delle
     * etichette. Una fetta che ne raccoglie molte le elenca tutte.
     *
     * @return array<int, array<int, int>>
     */
    abstract public function drillTargets(): array;

    /** L'elemento cliccato: la tabella in fondo si filtra su queste categorie. */
    public function drillInto(int $index): void
    {
        $categorie = $this->drillTargets()[$index] ?? [];

        if ($categorie === []) {
            return;
        }

        $this->dispatch('drill-into-categories', categories: $categorie);
    }
}
