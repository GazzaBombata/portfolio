<?php

namespace App\Filament\Widgets;

use App\Ai\Budget;
use App\Models\AiUsage;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Quanto è costato il modello questo mese.
 *
 * Compare solo dopo la prima chiamata: prima non c'è niente da dire, e un
 * riquadro a zero occupa spazio per annunciare che non è successo niente.
 */
class AiSpendOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 20;

    protected ?string $heading = 'Assistente';

    public static function canView(): bool
    {
        return AiUsage::query()->where('created_at', '>=', now()->startOfMonth())->exists();
    }

    protected function getStats(): array
    {
        $speso = Budget::spentThisMonth();
        $limite = Budget::limit();
        $chiamate = AiUsage::query()->where('created_at', '>=', now()->startOfMonth())->count();

        $perTipo = AiUsage::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('kind, SUM(cost) as totale')
            ->groupBy('kind')
            ->orderByDesc('totale')
            ->get();

        return [
            Stat::make('Speso questo mese', Budget::dollari($speso))
                ->description($limite > 0
                    ? 'su un tetto di '.Budget::dollari($limite)
                    : 'nessun tetto impostato')
                ->color(match (true) {
                    $limite <= 0 => 'gray',
                    $speso >= $limite => 'danger',
                    $speso >= $limite * 0.8 => 'warning',
                    default => 'success',
                }),

            Stat::make('Chiamate', (string) $chiamate)
                ->description($perTipo->map(
                    fn ($r): string => $r->kind.' '.Budget::dollari((float) $r->totale)
                )->implode(' · '))
                ->color('gray'),
        ];
    }
}
