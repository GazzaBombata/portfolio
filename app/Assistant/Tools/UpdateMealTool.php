<?php

namespace App\Assistant\Tools;

use App\Assistant\ChangesSomething;
use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Models\Meal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class UpdateMealTool implements ChangesSomething, Tool
{
    public function name(): string
    {
        return 'modifica_pasto';
    }

    public function description(): string
    {
        return 'Corregge un pasto già registrato, per id. Usa prima cerca_registrazioni per trovare l\'id giusto. Passa solo i campi da cambiare: quelli che ometti restano come sono.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'L\'id restituito da cerca_registrazioni'],
                'descrizione' => ['type' => ['string', 'null']],
                'momento' => ['type' => ['string', 'null'], 'enum' => ['breakfast', 'lunch', 'dinner', 'snack', null]],
                'giorno' => ['type' => ['string', 'null'], 'description' => 'AAAA-MM-GG, se il pasto era sul giorno sbagliato'],
                'calorie' => ['type' => ['integer', 'null']],
                'proteine_g' => ['type' => ['integer', 'null']],
                'carboidrati_g' => ['type' => ['integer', 'null']],
                'grassi_g' => ['type' => ['integer', 'null']],
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
                'stimati' => ['type' => ['boolean', 'null']],
            ],
            'required' => ['id'],
        ];
    }

    public function run(array $input): ToolResult
    {
        $meal = Meal::find($input['id']);

        if ($meal === null) {
            return ToolResult::error("Nessun pasto con id {$input['id']}. Cercalo con cerca_registrazioni.");
        }

        $prima = $meal->description;

        // Solo i campi ricevuti: un aggiornamento che azzera quello che non
        // gli è stato detto cancella dati che nessuno voleva toccare.
        $modifiche = array_filter([
            'description' => $input['descrizione'] ?? null,
            'moment' => $input['momento'] ?? null,
            'eaten_on' => isset($input['giorno']) ? CarbonImmutable::parse($input['giorno'])->toDateString() : null,
            'calories' => $input['calorie'] ?? null,
            'protein_g' => $input['proteine_g'] ?? null,
            'carbs_g' => $input['carboidrati_g'] ?? null,
            'fat_g' => $input['grassi_g'] ?? null,
        ], fn ($v): bool => $v !== null);

        if (array_key_exists('stimati', $input) && $input['stimati'] !== null) {
            $modifiche['nutrition_estimated'] = (bool) $input['stimati'];
        }

        $ingredienti = $input['ingredienti'] ?? [];

        if ($modifiche === [] && $ingredienti === []) {
            return ToolResult::error('Non mi hai detto cosa cambiare.');
        }

        if ($modifiche !== []) {
            $meal->update($modifiche);
        }

        $quanti = $meal->replaceItems($ingredienti);

        return ToolResult::ok(
            "Pasto #{$meal->id} del {$meal->eaten_on->format('d/m/Y')} aggiornato: «{$meal->description}»"
            .($quanti > 0 ? ", {$quanti} ingredienti" : '')
            .($meal->calories ? ", {$meal->calories} kcal" : '').'.',
            'corretto: '.Str::limit($prima, 24),
        );
    }
}
