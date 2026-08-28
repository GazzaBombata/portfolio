<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Porta serie, ripetizioni e carico dentro gli esercizi, e poi li toglie.
 *
 * Lasciarli sulla seduta «per compatibilità» sarebbe la scelta comoda e la
 * peggiore: resterebbero due posti per la stessa cosa, il form ne mostrerebbe
 * uno e il consulente leggerebbe l'altro. Chi compila i campi vecchi si
 * troverebbe una progressione che non si muove mai, senza nessun errore da
 * nessuna parte.
 *
 * Quello che c'era diventa un esercizio solo, intestato all'attività: è tutto
 * quello che quei tre numeri potevano voler dire.
 *
 * Le query girano su `DB` e non sui modelli di proposito: `BelongsToUser`
 * fallisce chiuso e in una migrazione non c'è nessun utente autenticato, quindi
 * un `Workout::query()` qui dentro non troverebbe niente — e la migrazione
 * passerebbe senza spostare una riga.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sedute = DB::table('workouts')
            ->where(function ($q) {
                $q->whereNotNull('sets')->orWhereNotNull('reps')->orWhereNotNull('load_kg');
            })
            ->get(['id', 'user_id', 'activity', 'sets', 'reps', 'load_kg']);

        foreach ($sedute as $seduta) {
            DB::table('workout_exercises')->insert([
                'user_id' => $seduta->user_id,
                'workout_id' => $seduta->id,
                'position' => 0,
                'name' => $seduta->activity,
                'sets' => $seduta->sets,
                'reps' => $seduta->reps,
                'load_kg' => $seduta->load_kg,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('workouts', function (Blueprint $table) {
            $table->dropColumn(['sets', 'reps', 'load_kg']);
        });
    }

    public function down(): void
    {
        // Le colonne tornano, i numeri no: stanno negli esercizi, ed è lì che
        // devono restare.
        Schema::table('workouts', function (Blueprint $table) {
            $table->unsignedSmallInteger('sets')->nullable();
            $table->unsignedSmallInteger('reps')->nullable();
            $table->decimal('load_kg', 6, 2)->nullable();
        });
    }
};
