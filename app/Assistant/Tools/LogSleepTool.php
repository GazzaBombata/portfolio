<?php

namespace App\Assistant\Tools;

use App\Assistant\ChangesSomething;
use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Models\SleepLog;
use Carbon\CarbonImmutable;

class LogSleepTool implements ChangesSomething, Tool
{
    public function name(): string
    {
        return 'registra_sonno';
    }

    public function description(): string
    {
        return 'Registra una notte di sonno. Se per quella notte c\'è già una riga, la aggiorna invece di crearne una seconda.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'notte_del' => ['type' => 'string', 'description' => 'La sera in cui è andato a dormire, in formato AAAA-MM-GG'],
                'minuti' => ['type' => ['integer', 'null'], 'description' => 'Quanto ha dormito in minuti'],
                'qualita' => ['type' => ['integer', 'null'], 'description' => 'Da 1 (pessima) a 5 (ottima)'],
                'risvegli' => ['type' => ['integer', 'null']],
                'note' => ['type' => ['string', 'null']],
            ],
            'required' => ['notte_del'],
        ];
    }

    public function run(array $input): ToolResult
    {
        $notte = CarbonImmutable::parse($input['notte_del'])->toDateString();

        $log = SleepLog::updateOrCreate(
            ['night_of' => $notte],
            array_filter([
                'minutes' => $input['minuti'] ?? null,
                'quality' => $input['qualita'] ?? null,
                'awakenings' => $input['risvegli'] ?? null,
                'notes' => $input['note'] ?? null,
            ], fn ($v): bool => $v !== null),
        );

        $ore = $log->minutes !== null ? sprintf('%dh %02dm', intdiv($log->minutes, 60), $log->minutes % 60) : 'durata non indicata';

        return ToolResult::ok(
            "Notte del {$notte} registrata: {$ore}".($log->quality ? ", qualità {$log->quality}/5" : '').'.',
            'Notte del '.CarbonImmutable::parse($notte)->format('d/m').": {$ore}",
        );
    }
}
