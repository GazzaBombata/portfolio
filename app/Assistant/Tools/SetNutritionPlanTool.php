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
        return "Registra cosa era previsto mangiare in un giorno e l'obiettivo calorico. Se l'obiettivo non viene indicato, lo calcola dal fabbisogno stimato.";
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'giorno' => ['type' => 'string', 'description' => 'AAAA-MM-GG'],
                'previsto' => ['type' => ['string', 'null'], 'description' => 'Cosa prevedeva il piano, a parole'],
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
            $obiettivo = Energy::dailyNeed(Auth::user(), $giorno);

            if ($obiettivo === null) {
                return ToolResult::error(
                    'Non posso calcolare il fabbisogno: nel profilo mancano data di nascita, altezza o sesso, '
                    .'oppure non c\'è nessuna misurazione del peso. Chiedi a Giorgio di completarli, o dammi tu un obiettivo.'
                );
            }
        }

        $log = DailyLog::updateOrCreate(
            ['logged_on' => $giorno->toDateString()],
            array_filter([
                'target_calories' => $obiettivo,
                'target_protein_g' => $input['obiettivo_proteine_g'] ?? null,
                'planned_meals' => $input['previsto'] ?? null,
                'targets_manual' => $aMano,
                'activity_calories' => Energy::activityBurn(Auth::user(), $giorno),
            ], fn ($v): bool => $v !== null),
        );

        return ToolResult::ok(
            "Piano del {$giorno->format('d/m/Y')}: obiettivo {$log->target_calories} kcal"
            .($aMano ? ' (come mi hai detto tu)' : ' (calcolato dal fabbisogno)')
            .(filled($log->planned_meals) ? '. Previsto: '.$log->planned_meals : '.'),
            $giorno->format('d/m').' · obiettivo '.$log->target_calories.' kcal',
        );
    }
}
