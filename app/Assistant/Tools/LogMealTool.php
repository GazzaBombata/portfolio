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
        return 'Registra un pasto descritto a parole. Smontalo in «ingredienti» quando ne ha più di uno: il totale lo somma il codice. Se i valori sono una tua stima, dichiaralo con stimati=true.';
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
                'ingredienti' => [
                    'type' => ['array', 'null'],
                    'description' => 'Il pasto smontato voce per voce. USALO SEMPRE quando il pasto ha più di un ingrediente: '
                        .'il totale lo somma il codice, quindi la stima si controlla riga per riga invece di doverla credere. '
                        .'Conta anche i condimenti: l\'olio di tre cucchiai vale più della carne che condisce.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'nome' => ['type' => 'string', 'description' => 'Petto di pollo, olio extravergine, pane integrale…'],
                            'quantita' => ['type' => ['string', 'null'], 'description' => 'Come si dice davvero: «200 g», «3 cucchiai», «mezza pizza».'],
                            'calorie' => ['type' => ['integer', 'null']],
                            'proteine_g' => ['type' => ['integer', 'null']],
                            'carboidrati_g' => ['type' => ['integer', 'null']],
                            'grassi_g' => ['type' => ['integer', 'null']],
                        ],
                        'required' => ['nome'],
                    ],
                ],
                'fuori_casa' => ['type' => ['boolean', 'null']],
            ],
            'required' => ['giorno', 'momento', 'descrizione'],
        ];
    }

    public function run(array $input): ToolResult
    {
        $giorno = CarbonImmutable::parse($input['giorno']);
        $ingredienti = $input['ingredienti'] ?? [];
        $haNumeri = filled($input['calorie'] ?? null) || $ingredienti !== [];

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

        // Le calorie passate insieme agli ingredienti vengono soprascritte dalla
        // somma: il totale è delle righe, e due totali diversi per lo stesso
        // pasto sono il modo in cui uno dei due diventa vecchio.
        $quanti = $meal->replaceItems($ingredienti);

        $nomi = ['breakfast' => 'Colazione', 'lunch' => 'Pranzo', 'dinner' => 'Cena', 'snack' => 'Spuntino'];
        $nome = $nomi[$meal->moment] ?? $meal->moment;

        $senza = $meal->itemsWithoutCalories();

        return ToolResult::ok(
            "{$nome} del {$giorno->format('d/m/Y')} registrato."
            .($quanti > 0 ? " {$quanti} ingredienti, totale sommato dalle righe." : '')
            .($meal->calories ? " Circa {$meal->calories} kcal".($meal->nutrition_estimated ? ' (stimate)' : '').'.' : '')
            .($senza > 0 ? " ATTENZIONE: {$senza} ingredienti sono senza calorie, quindi il totale è più basso del pasto vero." : ''),
            "{$nome} · {$giorno->format('d/m')}",
        );
    }
}
