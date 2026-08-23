<?php

namespace App\Filament\Widgets;

use App\Models\SleepLog;
use Filament\Widgets\ChartWidget;

/**
 * Le ultime trenta notti, in ore.
 *
 * A barre perché ogni notte è un fatto a sé: non c'è continuità fra una notte
 * e l'altra da suggerire con una linea. La riga delle sette ore è lì per dare
 * un metro: senza, «sei ore e mezza» è un numero che non dice se è tanto o
 * poco.
 */
class SleepChart extends ChartWidget
{
    protected static ?int $sort = 12;

    protected ?string $heading = 'Sonno, notte per notte';

    protected ?string $description = 'La linea segna le sette ore.';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return SleepLog::query()->whereNotNull('minutes')->count() >= 3;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $notti = SleepLog::query()
            ->whereNotNull('minutes')
            ->orderByDesc('night_of')
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

        return [
            'datasets' => [
                [
                    'label' => 'Ore dormite',
                    'data' => $notti->map(fn (SleepLog $n): float => round($n->minutes / 60, 2))->all(),
                    'backgroundColor' => '#2a78d6',
                    'borderRadius' => 4,
                ],
                [
                    // Un riferimento, non una seconda misura: stessa scala,
                    // stesso asse, nessun secondo asse da nessuna parte.
                    'label' => 'Sette ore',
                    'type' => 'line',
                    'data' => $notti->map(fn (): float => 7.0)->all(),
                    'borderColor' => 'rgba(148,163,184,0.7)',
                    'borderWidth' => 2,
                    'borderDash' => [5, 4],
                    'pointRadius' => 0,
                    'fill' => false,
                ],
            ],
            'labels' => $notti->map(fn (SleepLog $n): string => $n->night_of->format('d/m'))->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'y' => ['beginAtZero' => true, 'grid' => ['color' => 'rgba(148,163,184,0.15)']],
                'x' => ['grid' => ['display' => false]],
            ],
        ];
    }
}
