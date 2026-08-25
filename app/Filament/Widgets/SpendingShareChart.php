<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\IsDrillable;
use App\Finance\Reporting;
use App\Models\Category;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Collection;

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
    use IsDrillable;

    protected static ?int $sort = 3;

    protected string $view = 'filament.widgets.drillable-chart';

    protected ?string $heading = 'Come si distribuiscono le uscite';

    public function getDescription(): ?string
    {
        return 'Quanto pesa ogni categoria sul totale del periodo. Giroconti esclusi.'
            .Reporting::excludedLabel($this->pageFilters);
    }

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

    /**
     * Le categorie dietro ogni fetta.
     *
     * Ricalcolate con la stessa query e lo stesso ordine di getData(): il
     * browser sa solo di aver cliccato la terza fetta, e la corrispondenza fra
     * quel numero e una categoria esiste solo se le due liste sono costruite
     * allo stesso modo.
     *
     * @return array<int, array<int, int>>
     */
    public function drillTargets(): array
    {
        $righe = $this->righe();

        $target = $righe->take(self::FETTE)
            ->map(fn ($r): array => [(int) $r->category_id])
            ->values()
            ->all();

        $resto = $righe->slice(self::FETTE);

        if ($resto->isNotEmpty()) {
            // La fetta del resto porta dentro tutte le categorie che raccoglie.
            $target[] = $resto->pluck('category_id')->map(fn ($id): int => (int) $id)->all();
        }

        return $target;
    }

    /** @return Collection<int, object> */
    private function righe(): Collection
    {
        return Reporting::expenses($this->pageFilters)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, SUM(amount) as totale')
            ->groupBy('category_id')
            ->orderByRaw('SUM(amount)')
            ->get();
    }

    protected function getData(): array
    {
        $righe = $this->righe();

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
