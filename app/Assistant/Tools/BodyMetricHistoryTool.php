<?php

namespace App\Assistant\Tools;

use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Models\BodyMetric;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Il peso: quello di un giorno, o l'andamento di un periodo.
 *
 * Prima l'unico modo di leggerlo era `riepilogo_salute`, che del peso dice
 * «da X a Y kg» — cioè la prima e l'ultima misurazione dell'intervallo. Su una
 * domanda come «quanto pesavo a ferragosto» quella riga non risponde, e su
 * «qual è stata la media di agosto» risponde con due numeri che la media non
 * la contengono: fra 82 e 80 kg ci può stare qualunque cosa.
 *
 * Il caso che conta di più è il giorno senza misurazione. Non ci si pesa tutti
 * i giorni, quindi la risposta onesta non è «non lo so» e non è un numero
 * inventato: sono le due misurazioni intorno, con la loro distanza in giorni.
 * Chi legge decide se bastano.
 */
class BodyMetricHistoryTool implements Tool
{
    public function name(): string
    {
        return 'storico_peso';
    }

    public function description(): string
    {
        return 'Il peso e le altre misure del corpo: quello di un giorno preciso, oppure media, minimo, massimo e andamento di un periodo. Usalo per QUALSIASI domanda sul peso.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'giorno' => ['type' => ['string', 'null'], 'description' => 'AAAA-MM-GG, per il peso di un giorno preciso.'],
                'dal' => ['type' => ['string', 'null'], 'description' => 'AAAA-MM-GG; senza giorno né dal, gli ultimi 90 giorni.'],
                'al' => ['type' => ['string', 'null'], 'description' => 'AAAA-MM-GG'],
            ],
            'required' => [],
        ];
    }

    public function run(array $input): ToolResult
    {
        if (filled($input['giorno'] ?? null)) {
            return $this->unGiorno(CarbonImmutable::parse($input['giorno']));
        }

        $al = filled($input['al'] ?? null) ? CarbonImmutable::parse($input['al']) : CarbonImmutable::now();
        $dal = filled($input['dal'] ?? null) ? CarbonImmutable::parse($input['dal']) : $al->subDays(90);

        return $this->unPeriodo($dal, $al);
    }

    private function unGiorno(CarbonImmutable $giorno): ToolResult
    {
        $esatta = BodyMetric::query()
            ->whereDate('measured_on', $giorno)
            ->whereNotNull('weight_kg')
            ->first();

        if ($esatta !== null) {
            return ToolResult::ok(
                'Il '.$giorno->format('d/m/Y').': '.$this->riga($esatta),
                'peso del '.$giorno->format('d/m'),
            );
        }

        /*
         * Nessuna misurazione quel giorno. Non ci si pesa ogni giorno, quindi
         * non è un buco nei dati: è la normalità. Si danno i due estremi e la
         * loro distanza, e chi legge decide se rispondono alla domanda.
         */
        $prima = BodyMetric::query()->whereNotNull('weight_kg')
            ->whereDate('measured_on', '<', $giorno)->orderByDesc('measured_on')->first();

        $dopo = BodyMetric::query()->whereNotNull('weight_kg')
            ->whereDate('measured_on', '>', $giorno)->orderBy('measured_on')->first();

        if ($prima === null && $dopo === null) {
            return ToolResult::ok(
                'Nessuna misurazione del peso registrata, né il '.$giorno->format('d/m/Y').' né in nessun altro giorno.',
                'nessun peso',
            );
        }

        $righe = ['Il '.$giorno->format('d/m/Y').' non c\'è nessuna misurazione. Le più vicine:'];

        if ($prima !== null) {
            $righe[] = '- prima: '.$this->riga($prima).' ('
                .(int) $prima->measured_on->diffInDays($giorno).' giorni prima)';
        }

        if ($dopo !== null) {
            $righe[] = '- dopo: '.$this->riga($dopo).' ('
                .(int) $giorno->diffInDays($dopo->measured_on).' giorni dopo)';
        }

        $righe[] = 'Non stimare un valore intermedio: riporta queste, e di\' che quel giorno non si è pesato.';

        return ToolResult::ok(implode("\n", $righe), 'nessun peso il '.$giorno->format('d/m'));
    }

    private function unPeriodo(CarbonImmutable $dal, CarbonImmutable $al): ToolResult
    {
        $misure = BodyMetric::query()
            ->whereNotNull('weight_kg')
            ->whereBetween('measured_on', [$dal->toDateString(), $al->toDateString()])
            ->orderBy('measured_on')
            ->get();

        $periodo = 'Dal '.$dal->format('d/m/Y').' al '.$al->format('d/m/Y');

        if ($misure->isEmpty()) {
            return ToolResult::ok("{$periodo}: nessuna misurazione del peso.", 'nessun peso nel periodo');
        }

        $pesi = $misure->map(fn (BodyMetric $m): float => (float) $m->weight_kg);
        $primo = $misure->first();
        $ultimo = $misure->last();
        $delta = round((float) $ultimo->weight_kg - (float) $primo->weight_kg, 1);

        $min = $misure->sortBy(fn (BodyMetric $m): float => (float) $m->weight_kg)->first();
        $max = $misure->sortByDesc(fn (BodyMetric $m): float => (float) $m->weight_kg)->first();

        $righe = [
            "{$periodo}, {$misure->count()} ".($misure->count() === 1 ? 'misurazione' : 'misurazioni').':',
            '- Media: '.$this->kg($pesi->avg()).' kg',
            '- Prima: '.$this->kg((float) $primo->weight_kg).' kg il '.$primo->measured_on->format('d/m/Y'),
            '- Ultima: '.$this->kg((float) $ultimo->weight_kg).' kg il '.$ultimo->measured_on->format('d/m/Y'),
            '- Variazione: '.($delta > 0 ? '+' : '').$this->kg($delta).' kg',
            '- Minimo: '.$this->kg((float) $min->weight_kg).' kg il '.$min->measured_on->format('d/m/Y')
                .' · Massimo: '.$this->kg((float) $max->weight_kg).' kg il '.$max->measured_on->format('d/m/Y'),
        ];

        if (($grasso = $misure->whereNotNull('body_fat_pct'))->isNotEmpty()) {
            $righe[] = '- Massa grassa: media '.number_format($grasso->avg('body_fat_pct'), 1, ',', '').'%'
                .' su '.$grasso->count().' misurazioni';
        }

        if (($muscolo = $misure->whereNotNull('muscle_mass_kg'))->isNotEmpty()) {
            $righe[] = '- Massa muscolare: media '.$this->kg((float) $muscolo->avg('muscle_mass_kg')).' kg'
                .' su '.$muscolo->count().' misurazioni';
        }

        // Tutte le misurazioni, non un campione: se il modello deve ragionare
        // su un andamento, un elenco tagliato produce una tendenza che non c'è.
        $righe[] = 'Tutte le misurazioni: '.$this->elenco($misure);

        return ToolResult::ok(implode("\n", $righe), 'peso '.$dal->format('d/m').' → '.$al->format('d/m'));
    }

    private function riga(BodyMetric $m): string
    {
        $parti = [$this->kg((float) $m->weight_kg).' kg'];

        if ($m->body_fat_pct !== null) {
            $parti[] = 'massa grassa '.number_format((float) $m->body_fat_pct, 1, ',', '').'%';
        }

        if ($m->muscle_mass_kg !== null) {
            $parti[] = 'massa muscolare '.$this->kg((float) $m->muscle_mass_kg).' kg';
        }

        if ($m->resting_hr !== null) {
            $parti[] = 'battito a riposo '.$m->resting_hr;
        }

        return implode(', ', $parti);
    }

    /** @param  Collection<int, BodyMetric>  $misure */
    private function elenco(Collection $misure): string
    {
        return $misure->map(fn (BodyMetric $m): string => $m->measured_on->format('d/m')
            .' '.$this->kg((float) $m->weight_kg))->implode(' · ');
    }

    private function kg(float $n): string
    {
        return number_format($n, 1, ',', '.');
    }
}
