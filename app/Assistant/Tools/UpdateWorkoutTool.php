<?php

namespace App\Assistant\Tools;

use App\Assistant\ChangesSomething;
use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Models\Workout;
use Carbon\CarbonImmutable;

class UpdateWorkoutTool implements ChangesSomething, Tool
{
    public function name(): string
    {
        return 'modifica_allenamento';
    }

    public function description(): string
    {
        return 'Corregge una seduta, per id. Usa prima cerca_registrazioni. Passa solo i campi da cambiare. Serve anche a segnare come FATTA una seduta che era in programma. Il fabbisogno di quel giorno si riaggiorna da solo.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'attivita' => ['type' => ['string', 'null']],
                'tipo' => [
                    'type' => ['string', 'null'],
                    'enum' => ['fatta', 'prevista', null],
                    'description' => 'Per segnare fatta una seduta in programma. Da qui in poi conta calorie.',
                ],
                'giorno' => ['type' => ['string', 'null'], 'description' => 'AAAA-MM-GG'],
                'minuti' => ['type' => ['integer', 'null']],
                'distanza_km' => ['type' => ['number', 'null']],
                'intensita' => ['type' => ['integer', 'null']],
                'calorie' => ['type' => ['integer', 'null']],
                'note' => ['type' => ['string', 'null']],
                'esercizi' => [
                    'type' => ['array', 'null'],
                    'description' => 'SOSTITUISCE tutti gli esercizi della seduta: passali tutti, non solo quelli cambiati.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'nome' => ['type' => 'string'],
                            'serie' => ['type' => ['integer', 'null']],
                            'ripetizioni' => ['type' => ['integer', 'null']],
                            'carico_kg' => ['type' => ['number', 'null']],
                            'secondi' => ['type' => ['integer', 'null']],
                            'note' => ['type' => ['string', 'null']],
                        ],
                        'required' => ['nome'],
                    ],
                ],
            ],
            'required' => ['id'],
        ];
    }

    public function run(array $input): ToolResult
    {
        $workout = Workout::find($input['id']);

        if ($workout === null) {
            return ToolResult::error("Nessun allenamento con id {$input['id']}. Cercalo con cerca_registrazioni.");
        }

        $modifiche = array_filter([
            'activity' => $input['attivita'] ?? null,
            'kind' => match ($input['tipo'] ?? null) {
                'fatta' => 'done',
                'prevista' => 'planned',
                default => null,
            },
            'performed_on' => isset($input['giorno']) ? CarbonImmutable::parse($input['giorno'])->toDateString() : null,
            'minutes' => $input['minuti'] ?? null,
            'distance_km' => $input['distanza_km'] ?? null,
            'intensity' => $input['intensita'] ?? null,
            'calories' => $input['calorie'] ?? null,
            'notes' => $input['note'] ?? null,
        ], fn ($v): bool => $v !== null);

        $nuoviEsercizi = $input['esercizi'] ?? null;

        if ($modifiche === [] && $nuoviEsercizi === null) {
            return ToolResult::error('Non mi hai detto cosa cambiare.');
        }

        if ($modifiche !== []) {
            $workout->update($modifiche);
        }

        /*
         * Gli esercizi si sostituiscono in blocco invece di correggerli uno a
         * uno: un id per esercizio vorrebbe dire un giro di ricerca in più a
         * ogni correzione, e una scheda si detta per intero — «no aspetta, la
         * panca era 4×8» si dice rifacendo la lista, non citando una riga.
         */
        if (is_array($nuoviEsercizi)) {
            $workout->exercises()->delete();
            LogWorkoutTool::salvaEsercizi($workout, $nuoviEsercizi);
        }

        $workout->refresh();

        $dettaglio = collect([
            $workout->minutes ? "{$workout->minutes} minuti" : null,
            $workout->distance_km ? rtrim(rtrim((string) $workout->distance_km, '0'), '.').' km' : null,
        ])->filter()->implode(', ');

        $righe = ["Seduta #{$workout->id} aggiornata: {$workout->activity} il {$workout->performed_on->format('d/m/Y')}"
            .($dettaglio ? " ({$dettaglio})" : '')
            .($workout->kind === 'planned' ? ' — in programma, non conta calorie.' : '. Il fabbisogno di quel giorno è stato ricalcolato.')];

        foreach ($workout->exercises as $e) {
            $righe[] = '  - '.$e->summary();
        }

        return ToolResult::ok(implode("\n", $righe), "corretto: {$workout->activity}");
    }
}
