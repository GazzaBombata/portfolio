<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gli esercizi di una seduta, uno per riga.
 *
 * Prima serie, ripetizioni e carico erano tre colonne sulla seduta, cioè un
 * posto solo per una palestra che di esercizi ne ha cinque. Restavano due
 * strade e sbagliavano entrambe: una riga sola perdeva i carichi, cinque righe
 * li tenevano ma facevano contare cinque volte le calorie della stessa ora,
 * perché il conto somma MET per minuti riga per riga.
 *
 * Da qui viene l'unica cosa che un allenatore guarda davvero: come si muove un
 * carico nel tempo. «La panca è ferma a 60 kg da tre settimane» non è
 * un'opinione, è una query — ma solo se l'esercizio ha una riga sua.
 *
 * `user_id` è ridondante (si arriverebbe alla persona passando dalla seduta)
 * ma c'è lo stesso: `BelongsToUser` fallisce chiuso, e una tabella che dipende
 * da una join per sapere di chi sono i dati è una tabella che prima o poi
 * viene interrogata senza quella join.
 *
 * Il carico è nullable perché a corpo libero non c'è, e le ripetizioni perché
 * un plank si misura in secondi: un esercizio va registrabile anche quando non
 * riempie tutte le caselle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            // L'ordine in cui sono stati fatti: una scheda lo prescrive, e
            // rileggerla in ordine alfabetico non vuol dire niente.
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('name');
            $table->unsignedSmallInteger('sets')->nullable();
            $table->unsignedSmallInteger('reps')->nullable();
            $table->decimal('load_kg', 6, 2)->nullable();
            $table->unsignedSmallInteger('seconds')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'name']);
            $table->index(['workout_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_exercises');
    }
};
