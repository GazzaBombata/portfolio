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
                // minimum/maximum oltre alla descrizione: il modello legge lo
                // schema, e un numero fuori scala qui dentro è già arrivato in
                // tabella una volta.
                'qualita' => ['type' => ['integer', 'null'], 'description' => 'Da 1 (pessima) a 5 (ottima). Se te la dicono su dieci, dividila per due prima di scriverla.', 'minimum' => 1, 'maximum' => 5],
                'risvegli' => ['type' => ['integer', 'null']],
                'note' => ['type' => ['string', 'null']],
            ],
            'required' => ['notte_del'],
        ];
    }

    public function run(array $input): ToolResult
    {
        $notte = CarbonImmutable::parse($input['notte_del'])->toDateString();

        $qualita = $input['qualita'] ?? null;

        /*
         * Fuori scala si torna indietro invece di registrare.
         *
         * Il modello sente «ho dormito otto su dieci» e la scala qui è
         * un'altra: dimezzare al posto suo vorrebbe dire indovinare, e
         * lasciar passare l'otto rimetterebbe in tabella esattamente il dato
         * che questa versione è servita a togliere. Chiedere costa un giro.
         */
        if ($qualita !== null && ((int) $qualita < 1 || (int) $qualita > 5)) {
            return ToolResult::error(
                "La qualità del sonno va da 1 (pessima) a 5 (ottima), e {$qualita} è fuori scala. "
                .'Se te l\'ha detta su dieci, chiedigli conferma di quanto vale su cinque prima di registrarla.'
            );
        }

        $log = SleepLog::updateOrCreate(
            ['night_of' => $notte],
            array_filter([
                'minutes' => $input['minuti'] ?? null,
                'quality' => $qualita,
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
