<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * I dati che non cambiano, o cambiano piano.
 *
 * Servono al calcolo del metabolismo basale, che ha bisogno di età, altezza,
 * peso e sesso. Il peso non sta qui: quello si legge dall'ultima misurazione,
 * perché è il dato che cambia ed è già registrato giorno per giorno.
 *
 * La data di nascita e non l'età: un'età scritta in una riga di configurazione
 * è giusta il giorno in cui la scrivi e sbagliata per sempre dopo. Da una data
 * l'età si ricava, e resta vera fra dieci anni.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('birth_date')->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->enum('sex', ['male', 'female'])->nullable();

            /*
             * Quanto si muove la giornata, sport escluso.
             *
             * 1.2 è il valore per una vita sedentaria — lavoro da scrivania,
             * spostamenti brevi. Gli allenamenti NON stanno qui: vengono
             * sommati a parte da quello che è stato registrato davvero, così
             * una settimana ferma e una di corse non danno lo stesso numero.
             */
            $table->decimal('activity_factor', 3, 2)->default(1.20);

            // Quello che una persona vorrebbe fosse tenuto presente. Testo
            // libero: non è una cartella clinica, è un promemoria.
            $table->text('health_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['birth_date', 'height_cm', 'sex', 'activity_factor', 'health_notes']);
        });
    }
};
