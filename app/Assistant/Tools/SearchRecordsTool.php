<?php

namespace App\Assistant\Tools;

use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Models\Meal;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Serve a una cosa sola: trovare l'id di qualcosa da correggere.
 *
 * Senza, gli strumenti di modifica sarebbero inutilizzabili — o peggio, il
 * modello proverebbe a indovinare un numero.
 */
class SearchRecordsTool implements Tool
{
    public function name(): string
    {
        return 'cerca_registrazioni';
    }

    public function description(): string
    {
        return 'Elenca pasti e allenamenti registrati in un intervallo, con il loro id. Usalo prima di modifica_pasto o modifica_allenamento.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'dal' => ['type' => 'string', 'description' => 'AAAA-MM-GG'],
                'al' => ['type' => ['string', 'null'], 'description' => 'AAAA-MM-GG; se manca, solo il giorno indicato in "dal"'],
            ],
            'required' => ['dal'],
        ];
    }

    public function run(array $input): ToolResult
    {
        $dal = CarbonImmutable::parse($input['dal'])->toDateString();
        $al = isset($input['al']) ? CarbonImmutable::parse($input['al'])->toDateString() : $dal;

        $pasti = Meal::query()->whereBetween('eaten_on', [$dal, $al])->orderBy('eaten_on')->get();
        $allenamenti = Workout::query()->whereBetween('performed_on', [$dal, $al])->orderBy('performed_on')->get();

        if ($pasti->isEmpty() && $allenamenti->isEmpty()) {
            return ToolResult::ok("Nessuna registrazione fra il {$dal} e il {$al}.", 'niente trovato');
        }

        $nomi = ['breakfast' => 'colazione', 'lunch' => 'pranzo', 'dinner' => 'cena', 'snack' => 'spuntino'];
        $righe = [];

        foreach ($pasti as $m) {
            $righe[] = sprintf('%s #%d | %s | %s | %s%s',
                $m->kind === 'planned' ? 'PASTO PREVISTO' : 'PASTO',
                $m->id, $m->eaten_on->format('d/m/Y'), $nomi[$m->moment] ?? $m->moment,
                Str::limit((string) $m->description, 50),
                $m->calories ? " | {$m->calories} kcal".($m->nutrition_estimated ? ' (stimate)' : '') : ' | calorie non indicate');
        }

        foreach ($allenamenti as $w) {
            $righe[] = sprintf('ALLENAMENTO #%d | %s | %s%s%s',
                $w->id, $w->performed_on->format('d/m/Y'), $w->activity,
                $w->minutes ? " | {$w->minutes} min" : '',
                $w->calories ? " | {$w->calories} kcal" : '');
        }

        return ToolResult::ok(implode("\n", $righe), count($righe).' registrazioni');
    }
}
