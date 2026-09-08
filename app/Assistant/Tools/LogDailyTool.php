<?php

namespace App\Assistant\Tools;

use App\Assistant\ChangesSomething;
use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Health\DayRecalculator;
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
        return 'Registra i passi, l\'acqua bevuta e quanto è stato seguito il piano nutrizionale in un giorno. Una riga per giorno: se c\'è già, la aggiorna.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'giorno' => ['type' => 'string', 'description' => 'AAAA-MM-GG'],
                'passi' => ['type' => ['integer', 'null'], 'description' => 'I passi del giorno, come li legge il telefono. SEMPRE qui, mai nella nota: da lì non li conta nessuno.'],
                'acqua_litri' => ['type' => ['number', 'null']],
                'aderenza_piano' => ['type' => ['integer', 'null'], 'description' => 'Da 1 (per niente) a 10 (alla lettera)'],
                // Il campo senza descrizione è diventato il posto dove finiva
                // tutto: fra il 26 e il 29/08/2026 i passi di quattro giornate
                // sono stati scritti qui dentro come «7000 passi», e nel
                // fabbisogno hanno contato zero.
                'note' => [
                    'type' => ['string', 'null'],
                    'description' => 'Il contorno della giornata a parole: com\'è andata, cos\'è successo. NON i numeri — passi, acqua e aderenza hanno il loro campo, e scritti qui non entrano in nessun conto.',
                ],
            ],
            'required' => ['giorno'],
        ];
    }

    public function run(array $input): ToolResult
    {
        $giorno = CarbonImmutable::parse($input['giorno']);

        /*
         * Un numero di passi nella nota, e il campo vuoto: si torna indietro.
         *
         * Non è un'ipotesi. Dal 25 al 29/08/2026 i passi sono stati detti in
         * chat tutti i giorni e sono finiti quattro volte qui — «7000 passi»,
         * «Passi: 7000» — e una quinta nella descrizione di un allenamento.
         * La riga della giornata esisteva, con dentro l'acqua; il campo
         * `steps` era null. Risultato: `Energy::stepsBurn()` leggeva zero, e
         * il fabbisogno di quei giorni usciva più basso del vero senza che
         * niente lo dicesse — un dato raccolto, scritto, e perso in tabella.
         *
         * Correggerlo di nascosto sarebbe indovinare a quale numero della
         * frase si riferisce; chiedere costa un giro e lascia la scelta a chi
         * i passi li ha fatti.
         */
        $nota = (string) ($input['note'] ?? '');

        if (! filled($input['passi'] ?? null) && preg_match('/\d[\d.\s]*\s*passi|passi\s*[:=]?\s*\d/iu', $nota) === 1) {
            return ToolResult::error(
                'Nella nota c\'è un numero di passi ma il campo «passi» è vuoto: scritti lì dentro non li conta nessuno, '
                .'e il fabbisogno del giorno esce più basso del vero. Rifai la chiamata passando i passi nel loro campo.',
            );
        }

        $log = DailyLog::updateOrCreate(
            ['logged_on' => $giorno->toDateString()],
            array_filter([
                'steps' => $input['passi'] ?? null,
                'water_litres' => $input['acqua_litri'] ?? null,
                'nutrition_adherence' => $input['aderenza_piano'] ?? null,
                'notes' => $input['note'] ?? null,
            ], fn ($v): bool => $v !== null),
        );

        $parti = collect([
            $log->steps !== null ? number_format($log->steps, 0, ',', '.').' passi' : null,
            $log->water_litres !== null ? rtrim(rtrim((string) $log->water_litres, '0'), '.').' litri d\'acqua' : null,
            $log->nutrition_adherence !== null ? "piano {$log->nutrition_adherence}/10" : null,
        ])->filter()->implode(', ');

        /*
         * I passi entrano nel fabbisogno (`Energy::stepsBurn`), quindi la
         * copia salvata sulla giornata va rimessa in pari — è lo stesso
         * motivo per cui un observer lo fa a ogni allenamento.
         *
         * Il bilancio che leggi in chat non era sbagliato senza questa riga:
         * `bilancio_calorico` ricalcola tutto al momento. Sono i numeri
         * fermati sulla riga del giorno a restare indietro, e un numero
         * salvato che non corrisponde a quello calcolato è una discordanza che
         * salta fuori mesi dopo, quando nessuno ricorda quale dei due credere.
         */
        if (array_key_exists('passi', $input) && $input['passi'] !== null) {
            DayRecalculator::for($giorno);
        }

        return ToolResult::ok(
            "Giornata del {$giorno->format('d/m/Y')}: {$parti}.",
            "{$giorno->format('d/m')} · {$parti}",
        );
    }
}
