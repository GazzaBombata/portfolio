<?php

namespace App\Filament\Widgets;

use App\Finance\Reporting;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * I quattro numeri del mese in corso.
 *
 * L'ultimo — quanti movimenti non hanno ancora una categoria — è lì di
 * proposito accanto agli altri tre: dice quanto ci si può fidare dei primi
 * due. Un totale di spesa calcolato su metà dei movimenti classificati non è
 * sbagliato, ma non è nemmeno la risposta alla domanda che si stava facendo.
 */
class FinanceOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $dal = now()->startOfMonth();
        $al = now()->endOfMonth();

        $entrate = (float) Reporting::income()->whereBetween('booked_on', [$dal, $al])->sum('amount');
        $uscite = (float) Reporting::expenses()->whereBetween('booked_on', [$dal, $al])->sum('amount');
        $scoperti = Transaction::query()->whereNull('category_id')->count();

        $meseScorso = (float) Reporting::expenses()
            ->whereBetween('booked_on', [$dal->copy()->subMonth(), $dal->copy()->subDay()])
            ->sum('amount');

        return [
            Stat::make('Entrate del mese', Reporting::euro($entrate))
                ->description(now()->translatedFormat('F Y'))
                ->color('info'),

            Stat::make('Uscite del mese', Reporting::euro(abs($uscite)))
                ->description($this->confronto($uscite, $meseScorso))
                ->color('warning'),

            Stat::make('Saldo del mese', Reporting::euro($entrate + $uscite))
                ->description($entrate + $uscite >= 0 ? 'in attivo' : 'in passivo')
                ->color($entrate + $uscite >= 0 ? 'success' : 'danger'),

            Stat::make('Da classificare', (string) $scoperti)
                ->description($scoperti === 0 ? 'tutto classificato' : 'movimenti senza categoria')
                ->color($scoperti === 0 ? 'success' : 'gray'),
        ];
    }

    /** Il confronto col mese scorso, detto in parole invece che con una freccia. */
    private function confronto(float $questoMese, float $meseScorso): string
    {
        if ($meseScorso === 0.0) {
            return 'nessun confronto disponibile';
        }

        $differenza = abs($questoMese) - abs($meseScorso);
        $percentuale = (int) round($differenza / abs($meseScorso) * 100);

        if (abs($percentuale) < 5) {
            return 'come il mese scorso';
        }

        return $percentuale > 0
            ? "+{$percentuale}% sul mese scorso"
            : "{$percentuale}% sul mese scorso";
    }
}
