<?php

namespace App\Assistant\Tools;

use App\Assistant\Tool;
use App\Assistant\ToolResult;
use App\Health\Energy;
use App\Models\DailyLog;
use App\Models\Meal;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;

class EnergyBalanceTool implements Tool
{
    public function name(): string
    {
        return 'bilancio_calorico';
    }

    public function description(): string
    {
        return 'Il conto calorico di un giorno: fabbisogno stimato, quanto è stato mangiato, quanto bruciato con l\'attività, e la differenza. Usalo prima di rispondere a domande su come sta andando la dieta.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'giorno' => ['type' => 'string', 'description' => 'AAAA-MM-GG'],
            ],
            'required' => ['giorno'],
        ];
    }

    /**
     * Previsto contro consumato, voce per voce.
     *
     * È il confronto per cui il piano esiste: un totale che torna può nascondere
     * una colazione saltata e una cena doppia, e sono due giornate diverse.
     *
     * @return array<int, string>
     */
    private static function confrontoColPiano(CarbonImmutable $giorno): array
    {
        $previsti = Meal::query()->planned()->whereDate('eaten_on', $giorno)->get()->keyBy('moment');
        $mangiati = Meal::query()->eaten()->whereDate('eaten_on', $giorno)->get()->groupBy('moment');

        if ($previsti->isEmpty()) {
            return [];
        }

        $nomi = ['breakfast' => 'Colazione', 'lunch' => 'Pranzo', 'dinner' => 'Cena', 'snack' => 'Spuntino'];
        $righe = ['Confronto col piano:'];

        foreach ($nomi as $chiave => $nome) {
            $piano = $previsti->get($chiave);
            $vero = $mangiati->get($chiave);

            if ($piano === null && ($vero === null || $vero->isEmpty())) {
                continue;
            }

            if ($piano === null) {
                $righe[] = "  - {$nome}: NON era in programma, mangiato «".$vero->pluck('description')->implode(' + ').'»';

                continue;
            }

            if ($vero === null || $vero->isEmpty()) {
                // Un pasto previsto e non registrato: o è saltato, o è stato
                // dimenticato. Sono due cose diverse e solo una persona lo sa.
                $righe[] = "  - {$nome}: previsto «{$piano->description}», NIENTE registrato";

                continue;
            }

            $righe[] = sprintf('  - %s: previsto «%s»%s → mangiato «%s»%s',
                $nome,
                $piano->description,
                $piano->calories ? " ({$piano->calories} kcal)" : '',
                $vero->pluck('description')->implode(' + '),
                ($k = $vero->sum('calories')) ? " ({$k} kcal)" : '');
        }

        $totalePiano = Energy::planned($giorno);

        if ($totalePiano > 0) {
            $righe[] = "  Totale previsto: {$totalePiano} kcal";
        }

        return $righe;
    }

    public function run(array $input): ToolResult
    {
        $giorno = CarbonImmutable::parse($input['giorno']);
        $user = Auth::user();

        $fabbisogno = Energy::dailyNeed($user, $giorno);
        $bruciate = Energy::activityBurn($user, $giorno);
        $assunte = Energy::intake($giorno);
        $log = DailyLog::query()->firstWhere('logged_on', $giorno->toDateString());

        $righe = ['Giorno '.$giorno->format('d/m/Y').':'];

        if ($fabbisogno === null) {
            // Detto, non aggirato con una media: un fabbisogno calcolato su un
            // dato inventato ha l'aria di un numero vero.
            $righe[] = '- Fabbisogno: NON CALCOLABILE. Mancano dati nel profilo (data di nascita, altezza, sesso) oppure non c\'è nessuna misurazione del peso.';
        } else {
            $righe[] = "- Fabbisogno stimato: {$fabbisogno} kcal (basale + vita quotidiana + attività del giorno)";
        }

        $obiettivo = $log?->target_calories;
        if ($obiettivo !== null) {
            $righe[] = "- Obiettivo del piano: {$obiettivo} kcal".($log->targets_manual ? ' (impostato a mano)' : '');
        }

        $righe[] = $assunte > 0
            ? "- Mangiate: {$assunte} kcal, da ".Meal::query()->eaten()->whereDate('eaten_on', $giorno)->count().' pasti registrati'
            : '- Mangiate: nessuna caloria registrata (i pasti potrebbero esserci senza i valori nutrizionali)';

        $allenamenti = Workout::query()->whereDate('performed_on', $giorno)->get();
        $righe[] = $allenamenti->isEmpty()
            ? '- Attività: nessuna'
            : "- Attività: {$bruciate} kcal da ".$allenamenti->pluck('activity')->implode(', ');

        $riferimento = $obiettivo ?? $fabbisogno;

        if ($riferimento !== null && $assunte > 0) {
            $delta = $assunte - $riferimento;
            $righe[] = $delta >= 0
                ? '- Bilancio: **+'.$delta.' kcal** rispetto a '.($obiettivo !== null ? "l'obiettivo" : 'quanto stimato serva')
                : '- Bilancio: **'.$delta.' kcal** rispetto a '.($obiettivo !== null ? "l'obiettivo" : 'quanto stimato serva');
        }

        $righe = array_merge($righe, static::confrontoColPiano($giorno));

        $righe[] = 'Nota: sono stime. Il metabolismo basale viene da una formula di popolazione e le calorie di un allenamento dipendono da come lo si è fatto; servono a vedere una tendenza, non a decidere una singola cena.';

        return ToolResult::ok(implode("\n", $righe), 'Bilancio del '.$giorno->format('d/m'));
    }
}
