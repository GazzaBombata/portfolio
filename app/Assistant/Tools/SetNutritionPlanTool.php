<?php

namespace App\Assistant\Tools;

use App\Assistant\ChangesSomething;
use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Health\Energy;
use App\Models\DailyLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;

class SetNutritionPlanTool implements ChangesSomething, Tool
{
    public function name(): string
    {
        return 'imposta_piano';
    }

    public function description(): string
    {
        return "Forza l'obiettivo calorico di un giorno a un valore deciso dalla persona che ti scrive. "
            ."NON SERVE per l'uso normale: se i pasti previsti del giorno sono registrati con pianifica_pasto, "
            ."l'obiettivo è già la loro somma e lo calcola il sistema da solo — non chiederglielo. "
            .'Usalo solo quando ti dà lui un numero esplicito diverso dal piano.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'giorno' => ['type' => 'string', 'description' => 'AAAA-MM-GG'],
                'obiettivo_calorie' => ['type' => ['integer', 'null']],
                'obiettivo_proteine_g' => ['type' => ['integer', 'null']],
            ],
            'required' => ['giorno'],
        ];
    }

    public function run(array $input): ToolResult
    {
        $giorno = CarbonImmutable::parse($input['giorno']);
        $obiettivo = $input['obiettivo_calorie'] ?? null;
        $aMano = $obiettivo !== null;

        if ($obiettivo === null) {
            /*
             * Senza un numero esplicito si prende il piano — la somma dei pasti
             * previsti — e NON il fabbisogno.
             *
             * Sono due cose diverse: l'obiettivo è quanto ho deciso di mangiare,
             * il fabbisogno è quanto brucio. Mettere il secondo dove ci si
             * aspetta il primo dava un obiettivo di 3.000 kcal a una giornata da
             * 1.575 di piano, cioè diceva che c'era margine dove non ce n'era.
             */
            $obiettivo = Energy::planned($giorno) ?: null;

            if ($obiettivo === null) {
                return ToolResult::error(
                    'Non c\'è nessun pasto previsto per quel giorno, quindi non posso ricavare un obiettivo dal piano. '
                    .'Registra i pasti previsti con pianifica_pasto, oppure dammi tu il numero.'
                );
            }
        }

        $log = DailyLog::updateOrCreate(
            ['logged_on' => $giorno->toDateString()],
            array_filter([
                'target_calories' => $obiettivo,
                'target_protein_g' => $input['obiettivo_proteine_g'] ?? null,
                'targets_manual' => $aMano,
                'activity_calories' => Energy::activityBurn(Auth::user(), $giorno),
            ], fn ($v): bool => $v !== null),
        );

        return ToolResult::ok(
            "Piano del {$giorno->format('d/m/Y')}: obiettivo {$log->target_calories} kcal"
            .($aMano ? ' (come mi hai detto tu).' : ' (somma dei pasti previsti).'),
            $giorno->format('d/m').' · obiettivo '.$log->target_calories.' kcal',
        );
    }
}
