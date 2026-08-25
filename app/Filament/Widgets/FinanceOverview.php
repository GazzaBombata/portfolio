<?php

namespace App\Filament\Widgets;

use App\Finance\Period;
use App\Finance\Reporting;
use App\Models\Transaction;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
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
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $filtri = $this->pageFilters;
        $periodo = Period::fromFilters($filtri);

        $entrate = (float) Reporting::income($filtri)->sum('amount');
        $uscite = (float) Reporting::expenses($filtri)->sum('amount');

        // Il "prima" con cui confrontare: lo stesso intervallo spostato
        // indietro della propria durata, così un mese si confronta con un mese
        // e un trimestre con un trimestre.
        $precedente = $periodo->previous();
        $usciteprima = (float) Reporting::expenses([
            'periodo' => 'personalizzato',
            'dal' => $precedente->from?->toDateString(),
            'al' => $precedente->to?->toDateString(),
            'accounts' => $filtri['accounts'] ?? null,
        ])->sum('amount');

        $scoperti = Transaction::query()->whereNull('category_id')->count();

        return [
            Stat::make('Entrate', Reporting::euro($entrate))
                ->description($periodo->label)
                ->color('info'),

            Stat::make('Uscite', Reporting::euro(abs($uscite)))
                ->description(trim($this->confronto($uscite, $usciteprima).Reporting::excludedLabel($filtri)))
                ->color('warning'),

            Stat::make('Saldo', Reporting::euro($entrate + $uscite))
                ->description($entrate + $uscite >= 0 ? 'in attivo' : 'in passivo')
                ->color($entrate + $uscite >= 0 ? 'success' : 'danger'),

            Stat::make('Da classificare', (string) $scoperti)
                ->description($scoperti === 0 ? 'tutto classificato' : 'movimenti senza categoria')
                ->color($scoperti === 0 ? 'success' : 'gray'),
        ];
    }

    /** Il confronto col periodo precedente, detto in parole invece che con una freccia. */
    private function confronto(float $adesso, float $prima): string
    {
        if ($prima === 0.0) {
            return 'nessun confronto disponibile';
        }

        $percentuale = (int) round((abs($adesso) - abs($prima)) / abs($prima) * 100);

        if (abs($percentuale) < 5) {
            return 'come il periodo precedente';
        }

        return ($percentuale > 0 ? '+' : '').$percentuale.'% sul periodo precedente';
    }
}
