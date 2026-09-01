<?php

namespace App\Assistant\Tools;

use App\Assistant\ChangesSomething;
use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use Carbon\CarbonImmutable;

class LogWorkoutTool implements ChangesSomething, Tool
{
    public function name(): string
    {
        return 'registra_allenamento';
    }

    public function description(): string
    {
        return 'Registra una seduta: fatta oppure in programma. Gli esercizi vanno dentro la seduta, non in sedute separate — cinque righe per una palestra sola conterebbero cinque volte le stesse calorie.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'giorno' => ['type' => 'string', 'description' => 'AAAA-MM-GG'],
                'attivita' => ['type' => 'string', 'description' => 'Palestra, corsa, nuoto, bici…'],
                'tipo' => [
                    'type' => 'string',
                    'enum' => ['fatta', 'prevista'],
                    'description' => 'CHIEDI se non è chiaro. Una seduta prevista non brucia calorie finché non diventa fatta.',
                ],
                'proposta_da' => [
                    'type' => 'string',
                    'enum' => ['giorgio', 'te'],
                    'description' => '«te» solo se la scheda l\'hai scritta tu ed è stata approvata in chat. Se te l\'ha dettata la persona, è «lei».',
                ],
                'minuti' => ['type' => ['integer', 'null']],
                'distanza_km' => ['type' => ['number', 'null']],
                'intensita' => ['type' => ['integer', 'null'], 'description' => 'Da 1 (leggera) a 5 (massimale)'],
                'esercizi' => [
                    'type' => ['array', 'null'],
                    'description' => 'Gli esercizi della seduta, in ordine.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'nome' => ['type' => 'string', 'description' => 'Panca piana, squat, lat machine…'],
                            'serie' => ['type' => ['integer', 'null']],
                            'ripetizioni' => ['type' => ['integer', 'null']],
                            'carico_kg' => ['type' => ['number', 'null'], 'description' => 'Null a corpo libero.'],
                            'secondi' => ['type' => ['integer', 'null'], 'description' => 'Per plank e simili, che non si contano a ripetizioni.'],
                            'note' => ['type' => ['string', 'null']],
                        ],
                        'required' => ['nome'],
                    ],
                ],
                'note' => ['type' => ['string', 'null']],
            ],
            'required' => ['giorno', 'attivita', 'tipo', 'proposta_da'],
        ];
    }

    public function run(array $input): ToolResult
    {
        $giorno = CarbonImmutable::parse($input['giorno']);
        $prevista = ($input['tipo'] ?? 'fatta') === 'prevista';

        /*
         * Una scheda proposta dal modello resta marcata come sua anche quando
         * Giorgio l'ha approvata. L'approvazione dice che è d'accordo, non che
         * l'idea fosse sua — e fra un mese, guardando indietro per capire cosa
         * ha funzionato, è esattamente la differenza che serve.
         */
        $daAssistente = ($input['proposta_da'] ?? 'giorgio') === 'te';

        if ($daAssistente && ! $prevista) {
            return ToolResult::error(
                'Una seduta già fatta non puoi averla proposta tu: quello che è successo lo racconta chi c\'era. '
                .'Se stai registrando un allenamento fatto, proposta_da è «lei».',
            );
        }

        $workout = Workout::create(array_filter([
            'kind' => $prevista ? 'planned' : 'done',
            'authored_by' => $daAssistente ? 'assistant' : 'person',
            'performed_on' => $giorno->toDateString(),
            'activity' => $input['attivita'],
            'minutes' => $input['minuti'] ?? null,
            'distance_km' => $input['distanza_km'] ?? null,
            'intensity' => $input['intensita'] ?? null,
            'notes' => $input['note'] ?? null,
        ], fn ($v): bool => $v !== null));

        $esercizi = static::salvaEsercizi($workout, $input['esercizi'] ?? []);

        $dettaglio = collect([
            $workout->minutes ? "{$workout->minutes} minuti" : null,
            $workout->distance_km ? rtrim(rtrim((string) $workout->distance_km, '0'), '.').' km' : null,
        ])->filter()->implode(', ');

        $testa = $prevista ? 'In programma' : 'Registrato';
        $righe = ["{$testa}: {$workout->activity} il ".$giorno->format('d/m/Y').($dettaglio ? " ({$dettaglio})" : '').'.'];

        foreach ($esercizi as $e) {
            $righe[] = '  - '.$e->summary();
        }

        if ($prevista) {
            $righe[] = 'Non conta calorie finché non mi dici che l\'hai fatta.';
        }

        return ToolResult::ok(
            implode("\n", $righe),
            "{$workout->activity} · ".$giorno->format('d/m')
                .($prevista ? ' · in programma' : '')
                .($esercizi === [] ? '' : ' · '.count($esercizi).' esercizi'),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $esercizi
     * @return array<int, WorkoutExercise>
     */
    public static function salvaEsercizi(Workout $workout, array $esercizi): array
    {
        $salvati = [];

        foreach (array_values($esercizi) as $i => $e) {
            if (! is_array($e) || blank($e['nome'] ?? null)) {
                continue;
            }

            $salvati[] = WorkoutExercise::create(array_filter([
                'workout_id' => $workout->id,
                'position' => $i,
                'name' => $e['nome'],
                'sets' => $e['serie'] ?? null,
                'reps' => $e['ripetizioni'] ?? null,
                'load_kg' => $e['carico_kg'] ?? null,
                'seconds' => $e['secondi'] ?? null,
                'notes' => $e['note'] ?? null,
            ], fn ($v): bool => $v !== null));
        }

        return $salvati;
    }
}
