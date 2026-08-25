<?php

namespace App\Observers;

use App\Health\DayRecalculator;
use App\Models\Workout;
use Carbon\CarbonImmutable;

/**
 * Un allenamento cambia il fabbisogno del giorno in cui è stato fatto, quindi
 * ogni volta che ne nasce, cambia o sparisce uno, quel giorno va rifatto.
 */
class WorkoutObserver
{
    public function saved(Workout $workout): void
    {
        DayRecalculator::for(CarbonImmutable::parse($workout->performed_on), $workout->user);

        /*
         * Spostato di data: vanno rifatti tutti e due i giorni.
         *
         * Senza questa riga il giorno da cui è stato tolto continuerebbe a
         * contare le calorie di un allenamento che non c'è più — e nessuno
         * andrebbe a guardarlo, perché la modifica riguardava un altro giorno.
         */
        $primaEra = $workout->getOriginal('performed_on');

        if ($primaEra !== null && $primaEra !== $workout->performed_on->toDateString()) {
            DayRecalculator::for(CarbonImmutable::parse($primaEra), $workout->user);
        }
    }

    public function deleted(Workout $workout): void
    {
        DayRecalculator::for(CarbonImmutable::parse($workout->performed_on), $workout->user);
    }
}
