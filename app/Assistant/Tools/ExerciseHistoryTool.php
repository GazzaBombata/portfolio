<?php

namespace App\Assistant\Tools;

use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Models\WorkoutExercise;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Come si è mosso un carico nel tempo.
 *
 * È l'unica domanda che un allenatore fa davvero, ed è anche l'unica a cui
 * gli altri strumenti non sanno rispondere: `riepilogo_salute` conta le
 * sedute, `bilancio_calorico` conta le calorie, e nessuno dei due sa che la
 * panca è ferma a 60 kg da tre settimane.
 *
 * Guarda **solo le sedute fatte**. Una scheda in programma dice cosa si
 * vorrebbe sollevare, e una progressione costruita su quella misurerebbe le
 * intenzioni.
 */
class ExerciseHistoryTool implements Tool
{
    public function name(): string
    {
        return 'storico_esercizi';
    }

    public function description(): string
    {
        return 'Come sono andati i carichi nel tempo, esercizio per esercizio. Usalo PRIMA di proporre una scheda o di dire se conviene salire di carico.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'esercizio' => ['type' => ['string', 'null'], 'description' => 'Anche parziale ("panca"). Se manca, li elenca tutti.'],
                'dal' => ['type' => ['string', 'null'], 'description' => 'AAAA-MM-GG; se manca, gli ultimi 90 giorni.'],
                'al' => ['type' => ['string', 'null'], 'description' => 'AAAA-MM-GG'],
            ],
            'required' => [],
        ];
    }

    public function run(array $input): ToolResult
    {
        $al = isset($input['al']) ? CarbonImmutable::parse($input['al']) : CarbonImmutable::now();
        $dal = isset($input['dal']) ? CarbonImmutable::parse($input['dal']) : $al->subDays(90);

        $query = WorkoutExercise::query()
            ->whereHas('workout', fn (Builder $q) => $q->done()
                ->whereBetween('performed_on', [$dal->toDateString(), $al->toDateString()]))
            ->with('workout');

        if (filled($input['esercizio'] ?? null)) {
            $query->where('name', 'like', '%'.$input['esercizio'].'%');
        }

        $esercizi = $query->get();

        if ($esercizi->isEmpty()) {
            return ToolResult::ok(
                'Nessun esercizio registrato fra il '.$dal->format('d/m/Y').' e il '.$al->format('d/m/Y')
                .(filled($input['esercizio'] ?? null) ? ' per «'.$input['esercizio'].'».' : '.')
                .' Senza carichi registrati non si può dire se conviene salire: non tirare a indovinare.',
                'nessuno storico',
            );
        }

        $righe = ['Dal '.$dal->format('d/m/Y').' al '.$al->format('d/m/Y').', solo sedute fatte:'];

        foreach ($esercizi->groupBy(fn (WorkoutExercise $e): string => mb_strtolower($e->name)) as $gruppo) {
            $righe[] = static::riga($gruppo->sortBy(fn (WorkoutExercise $e): string => $e->workout->performed_on->toDateString()));
        }

        return ToolResult::ok(implode("\n", $righe), 'storico di '.$esercizi->pluck('name')->unique()->count().' esercizi');
    }

    /** @param  Collection<int, WorkoutExercise>  $storico */
    private static function riga(Collection $storico): string
    {
        $primo = $storico->first();
        $ultimo = $storico->last();
        $sedute = $storico->pluck('workout.performed_on')->map(fn ($d): string => $d->toDateString())->unique()->count();

        $riga = "- {$primo->name}: {$sedute} ".($sedute === 1 ? 'seduta' : 'sedute');

        $conCarico = $storico->filter(fn (WorkoutExercise $e): bool => $e->load_kg !== null);

        if ($conCarico->isEmpty()) {
            // A corpo libero il carico non c'è, e inventarne uno per avere una
            // curva sarebbe peggio che non avere la curva.
            return $riga.'. A corpo libero, nessun carico da confrontare. Ultima: '.$ultimo->summary()
                .' il '.$ultimo->workout->performed_on->format('d/m/Y').'.';
        }

        $primoCarico = (float) $conCarico->first()->load_kg;
        $ultimoCarico = (float) $conCarico->last()->load_kg;
        $delta = round($ultimoCarico - $primoCarico, 1);

        if ($delta == 0.0) {
            /*
             * Da quanti giorni non si muove. È il numero che fa scattare la
             * domanda «conviene salire?», e conviene che sia un fatto e non
             * un'impressione ricavata leggendo un elenco di date.
             */
            $daQuando = $conCarico->reverse()
                ->takeWhile(fn (WorkoutExercise $e): bool => (float) $e->load_kg === $ultimoCarico)
                ->last();

            $giorni = (int) $daQuando->workout->performed_on->diffInDays(CarbonImmutable::now());

            $riga .= ', fermo a '.WorkoutExercise::kg($ultimoCarico).' kg'
                .($giorni > 0 ? " da {$giorni} giorni" : '');
        } else {
            $riga .= sprintf(', da %s a %s kg (%s)',
                WorkoutExercise::kg($primoCarico), WorkoutExercise::kg($ultimoCarico),
                ($delta > 0 ? '+' : '').WorkoutExercise::kg($delta));
        }

        $riga .= '. Ultima: '.$ultimo->summary().' il '.$ultimo->workout->performed_on->format('d/m/Y');

        if (($volume = $ultimo->volumeKg()) !== null) {
            $riga .= ', volume '.number_format($volume, 0, ',', '.').' kg';
        }

        return $riga.'.';
    }
}
