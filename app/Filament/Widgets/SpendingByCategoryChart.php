<?php

namespace App\Filament\Widgets;

use App\Finance\Reporting;
use App\Models\Category;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Dove sono finiti i soldi quest'anno, dalla voce più pesante in giù.
 *
 * Barre orizzontali perché i nomi delle categorie sono parole, e in verticale
 * finirebbero ruotati di novanta gradi o troncati. Una tinta sola: è una serie
 * sola — "quanto per categoria" — e dare un colore diverso a ogni barra
 * suggerirebbe una distinzione che non c'è, oltre a costringere a inventare
 * tinte oltre l'ottava.
 */
class SpendingByCategoryChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Spesa per categoria';

    public function getDescription(): ?string
    {
        return 'Nel periodo selezionato, giroconti esclusi.'.Reporting::excludedLabel($this->pageFilters);
    }

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $righe = Reporting::expenses($this->pageFilters)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, SUM(amount) as totale')
            ->groupBy('category_id')
            ->orderByRaw('SUM(amount)')
            ->limit(12)
            ->get();

        $categorie = Category::query()
            ->whereIn('id', $righe->pluck('category_id'))
            ->with('parent')
            ->get()
            ->keyBy('id');

        return [
            'datasets' => [
                [
                    'label' => 'Speso',
                    'data' => $righe->map(fn ($r): float => round(abs((float) $r->totale), 2))->all(),
                    'backgroundColor' => '#2a78d6',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $righe->map(fn ($r): string => $categorie[$r->category_id]?->fullName() ?? '—')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                // Una serie sola: il titolo la nomina già, e un riquadro di
                // legenda con dentro una voce è rumore.
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => ['beginAtZero' => true, 'grid' => ['color' => 'rgba(148,163,184,0.15)']],
                'y' => ['grid' => ['display' => false]],
            ],
        ];
    }
}
