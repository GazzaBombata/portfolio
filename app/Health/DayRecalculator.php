<?php

namespace App\Health;

use App\Models\DailyLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;

/**
 * Rimette in pari i numeri di una giornata dopo che qualcosa è cambiato.
 *
 * Girato da un observer e non chiesto a voce: registrare un pasto e poi dover
 * dire "adesso ricalcola" è il genere di passaggio che si salta, e un bilancio
 * che è aggiornato solo quando qualcuno si ricorda di chiederlo è peggio di
 * nessun bilancio — perché sembra aggiornato.
 */
class DayRecalculator
{
    public static function for(CarbonImmutable $day, ?User $user = null): ?DailyLog
    {
        $user ??= Auth::user();

        if ($user === null) {
            return null;
        }

        $log = DailyLog::query()->firstWhere('logged_on', $day->toDateString());

        $bruciate = Energy::activityBurn($user, $day);
        $fabbisogno = Energy::dailyNeed($user, $day);

        /*
         * La riga si crea solo se c'è qualcosa da dire.
         *
         * Un giorno senza allenamenti e senza obiettivo non ha bisogno di
         * esistere: creare una riga vuota per ogni pasto registrato
         * riempirebbe la tabella di giornate che non raccontano niente, e
         * "quanti giorni ho tracciato" smetterebbe di significare qualcosa.
         */
        if ($log === null && $bruciate === 0) {
            return null;
        }

        $valori = ['activity_calories' => $bruciate];

        // L'obiettivo scritto a mano non si tocca: chi l'ha messo sapeva
        // qualcosa che la formula non sa.
        if ($fabbisogno !== null && ! ($log?->targets_manual ?? false)) {
            $valori['target_calories'] = $fabbisogno;
        }

        return DailyLog::updateOrCreate(['logged_on' => $day->toDateString()], $valori);
    }
}
