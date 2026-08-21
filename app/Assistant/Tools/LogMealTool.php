<?php

namespace App\Assistant\Tools;

use App\Assistant\ChangesSomething;
use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Models\Meal;
use Carbon\CarbonImmutable;

class LogMealTool implements ChangesSomething, Tool
{
    public function name(): string
    {
        return 'registra_pasto';
    }

    public function description(): string
    {
        return 'Registra un pasto descritto a parole. I valori nutrizionali sono facoltativi: se li stimi, dichiaralo con stimati=true.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'giorno' => ['type' => 'string', 'description' => 'AAAA-MM-GG'],
                'momento' => ['type' => 'string', 'enum' => ['breakfast', 'lunch', 'dinner', 'snack']],
                'descrizione' => ['type' => 'string'],
                'calorie' => ['type' => ['integer', 'null']],
                'proteine_g' => ['type' => ['integer', 'null']],
                'carboidrati_g' => ['type' => ['integer', 'null']],
                'grassi_g' => ['type' => ['integer', 'null']],
                'stimati' => ['type' => ['boolean', 'null'], 'description' => 'true se i valori sopra sono una tua stima e non pesati'],
                'fuori_casa' => ['type' => ['boolean', 'null']],
            ],
            'required' => ['giorno', 'momento', 'descrizione'],
        ];
    }

    public function run(array $input): ToolResult
    {
        $giorno = CarbonImmutable::parse($input['giorno']);
        $haNumeri = filled($input['calorie'] ?? null);

        $meal = Meal::create(array_filter([
            'eaten_on' => $giorno->toDateString(),
            'moment' => $input['momento'],
            'description' => $input['descrizione'],
            'calories' => $input['calorie'] ?? null,
            'protein_g' => $input['proteine_g'] ?? null,
            'carbs_g' => $input['carboidrati_g'] ?? null,
            'fat_g' => $input['grassi_g'] ?? null,
            // Una stima segnata come tale resta una stima anche fra sei mesi.
            'nutrition_estimated' => $haNumeri ? (bool) ($input['stimati'] ?? true) : false,
            'eaten_out' => (bool) ($input['fuori_casa'] ?? false),
        ], fn ($v): bool => $v !== null));

        $nomi = ['breakfast' => 'Colazione', 'lunch' => 'Pranzo', 'dinner' => 'Cena', 'snack' => 'Spuntino'];
        $nome = $nomi[$meal->moment] ?? $meal->moment;

        return ToolResult::ok(
            "{$nome} del {$giorno->format('d/m/Y')} registrato."
            .($meal->calories ? " Circa {$meal->calories} kcal".($meal->nutrition_estimated ? ' (stimate)' : '').'.' : ''),
            "{$nome} · {$giorno->format('d/m')}",
        );
    }
}
