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
        return 'Corregge un allenamento già registrato, per id. Usa prima cerca_registrazioni. Passa solo i campi da cambiare. Il fabbisogno calorico di quel giorno si riaggiorna da solo.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'attivita' => ['type' => ['string', 'null']],
                'giorno' => ['type' => ['string', 'null'], 'description' => 'AAAA-MM-GG'],
                'minuti' => ['type' => ['integer', 'null']],
                'distanza_km' => ['type' => ['number', 'null']],
                'intensita' => ['type' => ['integer', 'null']],
                'calorie' => ['type' => ['integer', 'null']],
                'note' => ['type' => ['string', 'null']],
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
            'performed_on' => isset($input['giorno']) ? CarbonImmutable::parse($input['giorno'])->toDateString() : null,
            'minutes' => $input['minuti'] ?? null,
            'distance_km' => $input['distanza_km'] ?? null,
            'intensity' => $input['intensita'] ?? null,
            'calories' => $input['calorie'] ?? null,
            'notes' => $input['note'] ?? null,
        ], fn ($v): bool => $v !== null);

        if ($modifiche === []) {
            return ToolResult::error('Non mi hai detto cosa cambiare.');
        }

        $workout->update($modifiche);

        $dettaglio = collect([
            $workout->minutes ? "{$workout->minutes} minuti" : null,
            $workout->distance_km ? rtrim(rtrim((string) $workout->distance_km, '0'), '.').' km' : null,
        ])->filter()->implode(', ');

        return ToolResult::ok(
            "Allenamento #{$workout->id} aggiornato: {$workout->activity} il {$workout->performed_on->format('d/m/Y')}"
            .($dettaglio ? " ({$dettaglio})" : '').'. Il fabbisogno di quel giorno è stato ricalcolato.',
            "corretto: {$workout->activity}",
        );
    }
}
