<?php

namespace App\Health;

use App\Models\BodyMetric;
use App\Models\DailyLog;
use App\Models\Meal;
use App\Models\SleepLog;
use App\Models\Workout;
use Carbon\CarbonImmutable;

/**
 * Quello che oggi e domani non hanno ancora dentro.
 *
 * Serve all'assistente per ricordare quello che manca. Il conto si fa qui e
 * non chiedendolo al modello per la stessa ragione per cui il link alla
 * dashboard lo mette la pagina: un buco dedotto a parole da un riepilogo è un
 * buco che ogni tanto viene inventato, e un promemoria sbagliato è peggio di
 * nessun promemoria — dopo due si smette di leggerli.
 *
 * **Un buco non è un errore.** Un giorno senza allenamento può essere riposo,
 * una cena non registrata può essere una cena saltata. Qui si dice solo che il
 * dato non c'è; cosa voglia dire lo sa una persona sola, ed è per questo che
 * il prompt chiede di domandare invece di correggere.
 *
 * Oggi e domani chiedono cose diverse: di oggi si registra quello che è
 * successo, di domani si decide quello che si vuole fare. Chiedere a domani i
 * pasti mangiati vorrebbe dire elencare tutti i giorni un buco che non si può
 * chiudere.
 */
class Gaps
{
    /** @return array<int, string> */
    public static function today(CarbonImmutable $giorno): array
    {
        $mancano = [];

        $log = DailyLog::query()->firstWhere('logged_on', $giorno->toDateString());

        if ($log?->steps === null) {
            $mancano[] = 'passi';
        }

        if ($log?->water_litres === null) {
            $mancano[] = 'acqua';
        }

        // Una notte appartiene alla sera in cui si è andati a dormire: quella
        // appena passata è datata ieri, non oggi.
        if (! SleepLog::query()->whereDate('night_of', $giorno->subDay())->exists()) {
            $mancano[] = 'sonno della notte scorsa';
        }

        if (! BodyMetric::query()->whereDate('measured_on', $giorno)->whereNotNull('weight_kg')->exists()) {
            $mancano[] = 'peso';
        }

        if (! Workout::query()->done()->whereDate('performed_on', $giorno)->exists()) {
            $mancano[] = 'allenamenti';
        }

        if (! Meal::query()->planned()->whereDate('eaten_on', $giorno)->exists()) {
            $mancano[] = 'pasti previsti';
        }

        if (! Meal::query()->eaten()->whereDate('eaten_on', $giorno)->exists()) {
            $mancano[] = 'pasti mangiati';
        } elseif (($senza = Meal::query()->eaten()->whereDate('eaten_on', $giorno)->whereNull('calories')->count()) > 0) {
            // Un pasto registrato senza calorie non si vede come buco: sta nel
            // conto, e il conto risulta più basso di quello vero.
            $mancano[] = $senza === 1
                ? 'valori nutrizionali di 1 pasto già registrato'
                : "valori nutrizionali di {$senza} pasti già registrati";
        }

        return $mancano;
    }

    /** @return array<int, string> */
    public static function tomorrow(CarbonImmutable $giorno): array
    {
        $mancano = [];

        if (! Meal::query()->planned()->whereDate('eaten_on', $giorno)->exists()) {
            $mancano[] = 'pasti previsti';
        }

        if (! Workout::query()->planned()->whereDate('performed_on', $giorno)->exists()) {
            $mancano[] = 'allenamenti in programma';
        }

        return $mancano;
    }

    /**
     * La riga che finisce nel prompt, già pronta.
     *
     * Sta nel blocco variabile e non in quello in cache: cambia a ogni
     * registrazione, e metterla davanti al punto di cache la invaliderebbe
     * tutte le volte.
     */
    public static function line(CarbonImmutable $oggi): string
    {
        $domani = $oggi->addDay();

        $diOggi = static::today($oggi);
        $diDomani = static::tomorrow($domani);

        if ($diOggi === [] && $diDomani === []) {
            return 'Da completare: niente, oggi e domani sono a posto. Non ricordargli nulla.';
        }

        $parti = [];

        if ($diOggi !== []) {
            $parti[] = 'oggi ('.$oggi->format('d/m').') '.implode(', ', $diOggi);
        }

        if ($diDomani !== []) {
            $parti[] = 'domani ('.$domani->format('d/m').') '.implode(', ', $diDomani);
        }

        return 'Da completare: '.implode('; ', $parti).'.';
    }
}
