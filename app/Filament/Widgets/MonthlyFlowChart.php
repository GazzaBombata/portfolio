<?php

namespace App\Filament\Widgets;

use App\Finance\Reporting;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Entrate e uscite, mese per mese, sugli ultimi dodici mesi.
 *
 * A barre affiancate e non impilate: la domanda è "in quale mese ho speso più
 * di quanto è entrato", e impilandole quel confronto sparisce dentro l'altezza
 * totale. Le uscite sono mostrate in positivo — l'asse dice già di che si
 * tratta, e barre che scendono sotto lo zero rendono i due mesi accanto
 * difficili da confrontare a occhio.
 */
class MonthlyFlowChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Entrate e uscite, mese per mese';

    protected ?string $description = 'I giroconti fra conti tuoi sono esclusi.';

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $dal = $this->firstMonth();

        $entrate = $this->perMonth(Reporting::income(), $dal);
        $uscite = $this->perMonth(Reporting::expenses(), $dal);

        $mesi = [];
        $etichette = [];

        for ($m = $dal->copy(); $m->lte(now()); $m->addMonth()) {
            $chiave = $m->format('Y-m');
            $mesi[] = $chiave;
            $etichette[] = $m->translatedFormat('M y');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Entrate',
                    'data' => array_map(fn (string $m): float => round($entrate[$m] ?? 0, 2), $mesi),
                    // Slot 1 della palette categoriale: validato contro l'arancio
                    // in chiaro e in scuro (ΔE 24.7 protan, soglia 8).
                    'backgroundColor' => '#2a78d6',
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Uscite',
                    'data' => array_map(fn (string $m): float => round(abs($uscite[$m] ?? 0), 2), $mesi),
                    'backgroundColor' => '#eb6834',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $etichette,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => true, 'position' => 'bottom'],
            ],
            'scales' => [
                // Un asse solo: due scale diverse sullo stesso grafico fanno
                // sembrare confrontabili due grandezze che non lo sono.
                'y' => [
                    'beginAtZero' => true,
                    'grid' => ['color' => 'rgba(148,163,184,0.15)'],
                ],
                'x' => ['grid' => ['display' => false]],
            ],
        ];
    }

    /**
     * Da quando comincia il grafico.
     *
     * Non dodici mesi fissi: se i movimenti importati partono da gennaio, i
     * quattro mesi precedenti restano vuoti e si prendono un terzo della
     * larghezza per non dire niente. Parte dal primo movimento, con un tetto a
     * dodici mesi perché oltre le barre diventano troppo sottili da leggere.
     */
    private function firstMonth(): Carbon
    {
        $primo = Reporting::realMovements()->min('booked_on');
        $tetto = now()->copy()->subMonths(11)->startOfMonth();

        if ($primo === null) {
            return $tetto;
        }

        $inizio = Carbon::parse($primo)->startOfMonth();

        return $inizio->lt($tetto) ? $tetto : $inizio;
    }

    /**
     * @return array<string, float>
     */
    private function perMonth(Builder $query, Carbon $dal): array
    {
        return $query
            ->where('booked_on', '>=', $dal)
            ->selectRaw("DATE_FORMAT(booked_on, '%Y-%m') as mese, SUM(amount) as totale")
            ->groupBy('mese')
            ->pluck('totale', 'mese')
            ->map(fn ($v): float => (float) $v)
            ->all();
    }
}
