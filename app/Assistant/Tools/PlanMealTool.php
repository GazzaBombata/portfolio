<?php

namespace App\Assistant\Tools;

use App\Assistant\ChangesSomething;
use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Models\Meal;
use Carbon\CarbonImmutable;

/**
 * Registra un pasto PREVISTO, non uno mangiato.
 *
 * Uno strumento separato invece di un parametro su registra_pasto: fra due
 * strumenti con nomi diversi il modello non si confonde, mentre un flag
 * booleano lasciato al valore predefinito trasformerebbe un piano in cibo
 * consumato — e il bilancio della giornata risulterebbe rispettato senza che
 * nessuno abbia mangiato niente.
 */
class PlanMealTool implements ChangesSomething, Tool
{
    public function name(): string
    {
        return 'pianifica_pasto';
    }

    public function description(): string
    {
        return 'Registra cosa era PREVISTO mangiare in un certo pasto di un certo giorno. Non è cibo consumato: serve a confrontare il piano con quello che è stato mangiato davvero. Per registrare un pasto vero usa registra_pasto.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'giorno' => ['type' => 'string', 'description' => 'AAAA-MM-GG'],
                'momento' => ['type' => 'string', 'enum' => ['breakfast', 'lunch', 'dinner', 'snack']],
                'descrizione' => ['type' => 'string', 'description' => 'Cosa prevede il piano per questo pasto'],
                'calorie' => ['type' => ['integer', 'null']],
                'proteine_g' => ['type' => ['integer', 'null']],
            ],
            'required' => ['giorno', 'momento', 'descrizione'],
        ];
    }

    public function run(array $input): ToolResult
    {
        $giorno = CarbonImmutable::parse($input['giorno']);

        // Un pasto previsto per volta, per momento: ripianificare il pranzo
        // corregge il piano invece di affiancargliene un secondo.
        $meal = Meal::updateOrCreate(
            [
                'kind' => 'planned',
                'eaten_on' => $giorno->toDateString(),
                'moment' => $input['momento'],
            ],
            array_filter([
                'description' => $input['descrizione'],
                'calories' => $input['calorie'] ?? null,
                'protein_g' => $input['proteine_g'] ?? null,
                'nutrition_estimated' => filled($input['calorie'] ?? null),
            ], fn ($v): bool => $v !== null),
        );

        $nomi = ['breakfast' => 'Colazione', 'lunch' => 'Pranzo', 'dinner' => 'Cena', 'snack' => 'Spuntino'];

        return ToolResult::ok(
            sprintf('Piano del %s, %s: «%s»%s.',
                $giorno->format('d/m/Y'),
                mb_strtolower($nomi[$meal->moment] ?? $meal->moment),
                $meal->description,
                $meal->calories ? " ({$meal->calories} kcal previste)" : ''),
            sprintf('piano %s · %s', $giorno->format('d/m'), $nomi[$meal->moment] ?? $meal->moment),
        );
    }
}
