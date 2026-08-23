<?php

namespace App\Assistant\Tools;

use App\Assistant\ChangesSomething;
use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Models\BodyMetric;
use Carbon\CarbonImmutable;

class LogBodyMetricTool implements ChangesSomething, Tool
{
    public function name(): string
    {
        return 'registra_peso';
    }

    public function description(): string
    {
        return 'Registra peso e misure corporee di un giorno. Una misurazione per giorno: se c\'è già, la aggiorna.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'giorno' => ['type' => 'string', 'description' => 'AAAA-MM-GG'],
                'peso_kg' => ['type' => ['number', 'null']],
                'massa_grassa_pct' => ['type' => ['number', 'null']],
                'massa_muscolare_kg' => ['type' => ['number', 'null']],
                'battito_riposo' => ['type' => ['integer', 'null']],
            ],
            'required' => ['giorno'],
        ];
    }

    public function run(array $input): ToolResult
    {
        $giorno = CarbonImmutable::parse($input['giorno']);

        $m = BodyMetric::updateOrCreate(
            ['measured_on' => $giorno->toDateString()],
            array_filter([
                'weight_kg' => $input['peso_kg'] ?? null,
                'body_fat_pct' => $input['massa_grassa_pct'] ?? null,
                'muscle_mass_kg' => $input['massa_muscolare_kg'] ?? null,
                'resting_hr' => $input['battito_riposo'] ?? null,
            ], fn ($v): bool => $v !== null),
        );

        $peso = $m->weight_kg !== null ? number_format((float) $m->weight_kg, 1, ',', '.').' kg' : 'misurazione registrata';

        return ToolResult::ok(
            "Peso del {$giorno->format('d/m/Y')}: {$peso}.",
            "{$giorno->format('d/m')} · {$peso}",
        );
    }
}
