<?php

namespace App\Health;

use App\Models\BodyMetric;
use App\Models\Meal;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;

/**
 * Il conto delle calorie di una giornata: quante ne servono, quante ne sono
 * entrate, quante ne sono uscite.
 *
 * Sono **stime**, e vale la pena dirlo qui una volta perché il codice non può
 * ripeterlo a ogni riga. Il metabolismo basale viene da una formula di
 * popolazione che sul singolo sbaglia facilmente del 10%, e le calorie di un
 * allenamento dipendono da come lo si è fatto, non da come si chiama. Servono
 * a vedere una tendenza su settimane, non a decidere cosa mangiare stasera.
 */
class Energy
{
    /**
     * Costo energetico approssimato delle attività, in MET.
     *
     * Un MET è il consumo da fermi; correre a ritmo medio ne costa una decina.
     * La tabella copre quello che una persona fa di solito e per il resto
     * ripiega su un valore moderato — meglio un numero onesto e generico che
     * un elenco lunghissimo con dentro il canottaggio.
     */
    private const MET = [
        'corsa' => 9.8, 'running' => 9.8, 'jogging' => 7.0,
        'camminata' => 3.5, 'passeggiata' => 3.0, 'trekking' => 6.0,
        'bici' => 7.5, 'ciclismo' => 7.5, 'bicicletta' => 7.5, 'spinning' => 8.5,
        'nuoto' => 7.0, 'piscina' => 6.0,
        'palestra' => 5.0, 'pesi' => 5.0, 'sala pesi' => 5.0, 'crossfit' => 8.0,
        'calcio' => 7.0, 'calcetto' => 7.0, 'tennis' => 7.3, 'padel' => 6.5,
        'basket' => 6.5, 'pallavolo' => 4.0, 'sci' => 7.0,
        'yoga' => 3.0, 'pilates' => 3.5, 'stretching' => 2.3,
    ];

    private const MET_PREDEFINITO = 5.0;

    /**
     * Metabolismo basale secondo Mifflin-St Jeor, in kcal al giorno.
     *
     * È la formula più usata perché sbaglia meno delle altre su persone
     * normopeso. Restituisce null quando manca un dato invece di riempirlo con
     * una media: un fabbisogno calcolato su un'altezza inventata è peggio di
     * nessun fabbisogno, perché ha l'aria di essere un numero.
     */
    public static function basalRate(User $user, ?float $weightKg = null): ?float
    {
        $peso = $weightKg ?? static::lastWeight($user);
        $eta = static::age($user);

        if ($peso === null || $eta === null || $user->height_cm === null || $user->sex === null) {
            return null;
        }

        $base = 10 * $peso + 6.25 * $user->height_cm - 5 * $eta;

        return round($base + ($user->sex === 'male' ? 5 : -161), 0);
    }

    /**
     * Il fabbisogno di un giorno: basale, più la vita quotidiana, più quello
     * che è stato fatto di sport quel giorno.
     *
     * Lo sport si somma a parte e non è dentro il fattore di attività: un
     * fattore alto darebbe lo stesso fabbisogno a una settimana ferma e a una
     * di allenamenti, che è esattamente la differenza che si vuole vedere.
     */
    public static function dailyNeed(User $user, CarbonImmutable $day): ?int
    {
        $basale = static::basalRate($user, static::weightOn($user, $day));

        if ($basale === null) {
            return null;
        }

        return (int) round($basale * (float) $user->activity_factor + static::activityBurn($user, $day));
    }

    /** Le calorie bruciate con gli allenamenti registrati quel giorno. */
    public static function activityBurn(User $user, CarbonImmutable $day): int
    {
        $peso = static::weightOn($user, $day) ?? static::lastWeight($user);

        if ($peso === null) {
            return 0;
        }

        $totale = 0.0;

        foreach (Workout::query()->whereDate('performed_on', $day)->get() as $workout) {
            // Se le calorie sono state registrate, si usano quelle: chi le ha
            // scritte guardava un cardiofrequenzimetro, non una tabella.
            if ($workout->calories !== null) {
                $totale += $workout->calories;

                continue;
            }

            if ($workout->minutes === null) {
                continue;
            }

            $totale += static::metFor((string) $workout->activity) * $peso * ($workout->minutes / 60);
        }

        return (int) round($totale);
    }

    /** Le calorie mangiate quel giorno, per quanto sono state registrate. */
    public static function intake(CarbonImmutable $day): int
    {
        return (int) Meal::query()->eaten()->whereDate('eaten_on', $day)->sum('calories');
    }

    /** Le calorie che il piano prevedeva per quel giorno. */
    public static function planned(CarbonImmutable $day): int
    {
        return (int) Meal::query()->planned()->whereDate('eaten_on', $day)->sum('calories');
    }

    private static function metFor(string $activity): float
    {
        $nome = mb_strtolower(trim($activity));

        foreach (self::MET as $chiave => $met) {
            if (str_contains($nome, $chiave)) {
                return $met;
            }
        }

        return self::MET_PREDEFINITO;
    }

    public static function age(User $user): ?int
    {
        return $user->birth_date?->age;
    }

    private static function lastWeight(User $user): ?float
    {
        $peso = BodyMetric::query()
            ->where('user_id', $user->id)
            ->whereNotNull('weight_kg')
            ->orderByDesc('measured_on')
            ->value('weight_kg');

        return $peso === null ? null : (float) $peso;
    }

    /**
     * Il peso di quel giorno, o l'ultimo noto prima di allora.
     *
     * Guarda indietro e mai avanti: il fabbisogno di marzo calcolato con il
     * peso di agosto è un numero che a marzo non esisteva.
     */
    private static function weightOn(User $user, CarbonImmutable $day): ?float
    {
        $peso = BodyMetric::query()
            ->where('user_id', $user->id)
            ->whereNotNull('weight_kg')
            ->whereDate('measured_on', '<=', $day)
            ->orderByDesc('measured_on')
            ->value('weight_kg');

        return $peso === null ? static::lastWeight($user) : (float) $peso;
    }
}
