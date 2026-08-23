<?php

namespace App\Assistant\Tools;

use App\Assistant\ChangesSomething;
use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Models\Workout;
use Carbon\CarbonImmutable;

class LogWorkoutTool implements ChangesSomething, Tool
{
    public function name(): string
    {
        return 'registra_allenamento';
    }

    public function description(): string
    {
        return 'Registra un\'attività fisica: che cosa, quando, quanto è durata. Ogni allenamento è una riga a sé, anche due nello stesso giorno.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'giorno' => ['type' => 'string', 'description' => 'AAAA-MM-GG'],
                'attivita' => ['type' => 'string', 'description' => 'Corsa, palestra, nuoto, bici, camminata…'],
                'minuti' => ['type' => ['integer', 'null']],
                'distanza_km' => ['type' => ['number', 'null']],
                'intensita' => ['type' => ['integer', 'null'], 'description' => 'Da 1 (leggera) a 5 (massimale)'],
                'serie' => ['type' => ['integer', 'null']],
                'ripetizioni' => ['type' => ['integer', 'null']],
                'carico_kg' => ['type' => ['number', 'null']],
                'note' => ['type' => ['string', 'null']],
            ],
            'required' => ['giorno', 'attivita'],
        ];
    }

    public function run(array $input): ToolResult
    {
        $giorno = CarbonImmutable::parse($input['giorno']);

        $workout = Workout::create(array_filter([
            'performed_on' => $giorno->toDateString(),
            'activity' => $input['attivita'],
            'minutes' => $input['minuti'] ?? null,
            'distance_km' => $input['distanza_km'] ?? null,
            'intensity' => $input['intensita'] ?? null,
            'sets' => $input['serie'] ?? null,
            'reps' => $input['ripetizioni'] ?? null,
            'load_kg' => $input['carico_kg'] ?? null,
            'notes' => $input['note'] ?? null,
        ], fn ($v): bool => $v !== null));

        $dettaglio = collect([
            $workout->minutes ? "{$workout->minutes} minuti" : null,
            $workout->distance_km ? rtrim(rtrim((string) $workout->distance_km, '0'), '.').' km' : null,
        ])->filter()->implode(', ');

        return ToolResult::ok(
            "Registrato: {$workout->activity} il {$giorno->format('d/m/Y')}".($dettaglio ? " ({$dettaglio})" : '').'.',
            "{$workout->activity} · {$giorno->format('d/m')}".($dettaglio ? " · {$dettaglio}" : ''),
        );
    }
}
