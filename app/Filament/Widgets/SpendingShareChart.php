<?php

namespace App\Filament\Widgets;

use App\Finance\Reporting;
use App\Models\Category;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Quanto pesa ogni categoria sul totale speso.
 *
 * A ciambella e non a barre perché la domanda è un'altra: le barre accanto
 * dicono «quanto» in euro, questa dice «quanta parte» del totale. Sono due
 * modi di guardare gli stessi numeri, e capita di volerli tutti e due.
 *
 * QUATTRO categorie più «Altro», e non è una scelta estetica: sono le uniche
 * quantità di tinte che superano la verifica sui daltonismi in entrambi i temi
 * confrontando OGNI coppia di fette — che in una torta è ciò che serve, perché
 * l'occhio confronta anche spicchi lontani fra loro. A cinque tinte, viola e
 * blu collassano a ΔE 1,9 nel tema scuro: per un protanope sarebbero la stessa
 * fetta.
 */
class SpendingShareChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Come si distribuiscono le uscite';

    protected ?string $description = 'Quanto pesa ogni categoria sul totale del periodo. Giroconti esclusi.';

    /** Quante fette prima di raccogliere il resto sotto «Altro». */
    private const FETTE = 4;

    public static function canView(): bool
    {
        return Category::query()->where('kind', 'expense')->exists();
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $righe = Reporting::expenses($this->pageFilters)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, SUM(amount) as totale')
            ->groupBy('category_id')
            ->orderByRaw('SUM(amount)')
            ->get();

        if ($righe->isEmpty()) {
            return ['datasets' => [['data' => []]], 'labels' => []];
        }

        $categorie = Category::query()->whereIn('id', $righe->pluck('category_id'))->with('parent')->get()->keyBy('id');

        $principali = $righe->take(self::FETTE);
        $resto = $righe->slice(self::FETTE);

        $etichette = $principali->map(fn ($r): string => $categorie[$r->category_id]?->fullName() ?? '—')->all();
        $valori = $principali->map(fn ($r): float => round(abs((float) $r->totale), 2))->all();

        // Il resto raccolto in una fetta sola, che dice quante voci contiene:
        // «Altro» senza un numero accanto sembra una categoria, e invece è un
        // mucchio.
        if ($resto->isNotEmpty()) {
            $etichette[] = 'Altre '.$resto->count().' categorie';
            $valori[] = round(abs((float) $resto->sum('totale')), 2);
        }

        return [
            'datasets' => [[
                'data' => $valori,
                'backgroundColor' => [
                    '#2a78d6',  // blu
                    '#eda100',  // giallo
                    '#e87ba4',  // magenta
                    '#008300',  // verde
                    '#9a9a94',  // il grigio del «resto»: non compete per identità
                ],
                // Un anello del colore della pagina fra le fette: senza, due
                // spicchi adiacenti si toccano e il confine si perde.
                'borderColor' => 'rgba(255,255,255,0.9)',
                'borderWidth' => 2,
            ]],
            'labels' => $etichette,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '55%',
            'plugins' => [
                'legend' => [
                    // La legenda di lato e non sotto: i nomi delle categorie
                    // sono parole lunghe, e in orizzontale vanno a capo.
                    'position' => 'right',
                    'labels' => ['boxWidth' => 12, 'padding' => 12],
                ],
            ],
        ];
    }
}
