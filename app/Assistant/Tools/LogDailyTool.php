<?php

namespace App\Assistant\Tools;

use App\Assistant\ChangesSomething;
use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Models\DailyLog;
use Carbon\CarbonImmutable;

class LogDailyTool implements ChangesSomething, Tool
{
    public function name(): string
    {
        return 'registra_giornata';
    }

    public function description(): string
    {
        return 'Registra acqua bevuta e quanto è stato seguito il piano nutrizionale in un giorno. Una riga per giorno: se c\'è già, la aggiorna.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'giorno' => ['type' => 'string', 'description' => 'AAAA-MM-GG'],
                'acqua_litri' => ['type' => ['number', 'null']],
                'aderenza_piano' => ['type' => ['integer', 'null'], 'description' => 'Da 1 (per niente) a 10 (alla lettera)'],
                'note' => ['type' => ['string', 'null']],
            ],
            'required' => ['giorno'],
        ];
    }

    public function run(array $input): ToolResult
    {
        $giorno = CarbonImmutable::parse($input['giorno']);

        $log = DailyLog::updateOrCreate(
            ['logged_on' => $giorno->toDateString()],
            array_filter([
                'water_litres' => $input['acqua_litri'] ?? null,
                'nutrition_adherence' => $input['aderenza_piano'] ?? null,
                'notes' => $input['note'] ?? null,
            ], fn ($v): bool => $v !== null),
        );

        $parti = collect([
            $log->water_litres !== null ? rtrim(rtrim((string) $log->water_litres, '0'), '.').' litri d\'acqua' : null,
            $log->nutrition_adherence !== null ? "piano {$log->nutrition_adherence}/10" : null,
        ])->filter()->implode(', ');

        return ToolResult::ok(
            "Giornata del {$giorno->format('d/m/Y')}: {$parti}.",
            "{$giorno->format('d/m')} · {$parti}",
        );
    }
}
