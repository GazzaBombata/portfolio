<?php

namespace Database\Seeders;

use App\Models\BodyMetric;
use App\Models\DailyLog;
use App\Models\SleepLog;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * I giorni 15–21 del percorso, cioè dal 10 al 16 agosto 2026.
 *
 * Due convenzioni, dichiarate qui perché non si deducono dai numeri:
 *
 * - Il sonno riportato su un giorno è la notte **conclusa** quella mattina,
 *   quindi viene registrato sotto la sera precedente. È come lo riportano gli
 *   orologi, ed è la stessa convenzione del resto dell'applicazione.
 * - «sgarro» diventa aderenza 3/10 con la parola scritta nelle note: un valore
 *   nullo lo toglierebbe dalle medie, e una giornata storta che sparisce dalle
 *   statistiche le rende più belle di com'è andata.
 *
 * `updateOrCreate` ovunque: rilanciare questo seeder corregge invece di
 * duplicare.
 */
class AgostoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->orderBy('id')->first();

        if ($user === null) {
            return;
        }

        Auth::setUser($user);

        $giorni = [
            // data          peso    sonno_h  qualità  passi   attività        minuti  acqua  dieta  nota
            ['2026-08-10', 94.4, 8.5, 8, 7100, 'Cyclette', 45, 2.50, 7, null],
            ['2026-08-11', 94.2, 8.5, 8, 8600, 'Cyclette', 45, 2.50, 8, null],
            ['2026-08-12', 93.9, 8.0, 8, 4700, null, null, 3.00, 8, null],
            // Le camminate NON vengono registrate come allenamento: i passi
            // di quei giorni sono la camminata, e contarle entrambe conterebbe
            // due volte la stessa ora. La cyclette invece sì — non produce un
            // passo, quindi i passi non la vedono.
            ['2026-08-13', 93.9, 8.0, 3, 11300, null, null, 3.50, 3, 'Sgarro alimentare. Camminata di circa 2 h, contata nei passi.'],
            ['2026-08-14', 95.7, 8.5, 8, 21300, null, null, 2.50, 3, 'Sgarro alimentare. Camminata di circa 3 h, contata nei passi.'],
            ['2026-08-15', 95.7, 8.0, 8, 5700, 'Cyclette', 45, 3.00, 8, null],
            // Il 16 è stato registrato solo il peso: il resto resta vuoto
            // invece di essere riempito con una media, che sarebbe un dato
            // inventato in mezzo a dati veri.
            ['2026-08-16', 95.3, null, null, null, null, null, null, null, null],
        ];

        /*
         * Le camminate registrate da una versione precedente vanno tolte.
         *
         * `updateOrCreate` aggiorna e non cancella: senza questa riga, chi
         * rilancia il seeder dopo il cambiamento si ritrova le camminate
         * ancora lì, sommate ai passi che le contengono già — cioè la stessa
         * ora contata due volte, che è esattamente ciò che il cambiamento
         * voleva evitare.
         */
        Workout::query()
            ->whereBetween('performed_on', ['2026-08-10', '2026-08-16'])
            ->whereIn('activity', ['Camminata', 'camminata', 'Passeggiata'])
            ->delete();

        foreach ($giorni as [$data, $peso, $ore, $qualita, $passi, $attivita, $minuti, $acqua, $dieta, $nota]) {
            BodyMetric::updateOrCreate(['measured_on' => $data], ['weight_kg' => $peso]);

            if ($ore !== null) {
                SleepLog::updateOrCreate(
                    // La notte conclusa la mattina di $data è cominciata la sera prima.
                    ['night_of' => CarbonImmutable::parse($data)->subDay()->toDateString()],
                    ['minutes' => (int) round($ore * 60), 'quality' => $qualita],
                );
            }

            if ($acqua !== null || $passi !== null || $dieta !== null) {
                DailyLog::updateOrCreate(
                    ['logged_on' => $data],
                    array_filter([
                        'water_litres' => $acqua,
                        'steps' => $passi,
                        'nutrition_adherence' => $dieta,
                        'notes' => $nota,
                    ], fn ($v): bool => $v !== null),
                );
            }

            if ($attivita !== null) {
                // Qui non updateOrCreate su tutto: la chiave è giorno +
                // attività, così rilanciare non aggiunge una seconda cyclette
                // allo stesso giorno.
                Workout::updateOrCreate(
                    ['performed_on' => $data, 'activity' => $attivita],
                    ['minutes' => $minuti],
                );
            }
        }
    }
}
