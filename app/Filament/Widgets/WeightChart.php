<?php

namespace App\Filament\Widgets;

use App\Models\BodyMetric;
use Filament\Widgets\ChartWidget;

/**
 * Il peso nel tempo.
 *
 * A linea e non a barre: fra due pesate c'è una continuità reale — il peso è
 * esistito anche nei giorni in cui non ti sei pesato — e le barre farebbero
 * sembrare ogni misurazione un evento a sé.
 *
 * L'asse NON parte da zero, ed è l'unico caso in cui è giusto: con lo zero in
 * fondo, tre chili di differenza su ottanta diventano una riga piatta, cioè
 * esattamente il contrario di quello che il grafico deve mostrare.
 */
class WeightChart extends ChartWidget
{
    protected static ?int $sort = 11;

    protected ?string $heading = 'Peso';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        // Con una misurazione sola non c'è un andamento da guardare.
        return BodyMetric::query()->whereNotNull('weight_kg')->count() >= 2;
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $misure = BodyMetric::query()
            ->whereNotNull('weight_kg')
            ->orderBy('measured_on')
            ->limit(180)
            ->get();

        return [
            'datasets' => [[
                'label' => 'Peso (kg)',
                'data' => $misure->map(fn (BodyMetric $m): float => round((float) $m->weight_kg, 1))->all(),
                'borderColor' => '#2a78d6',
                'backgroundColor' => 'rgba(42,120,214,0.08)',
                'borderWidth' => 2,
                'pointRadius' => 4,
                'pointBackgroundColor' => '#2a78d6',
                'fill' => true,
                // Nessuna curva morbida: interpolare fra due pesate inventa
                // un andamento che non è stato misurato.
                'tension' => 0,
            ]],
            'labels' => $misure->map(fn (BodyMetric $m): string => $m->measured_on->format('d/m'))->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'y' => [
                    'beginAtZero' => false,
                    'grid' => ['color' => 'rgba(148,163,184,0.15)'],
                    'ticks' => ['callback' => null],
                ],
                'x' => ['grid' => ['display' => false]],
            ],
        ];
    }
}
