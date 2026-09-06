<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gli ingredienti di un pasto, uno per riga.
 *
 * Fino a qui le calorie di un pasto erano UN numero, e quel numero lo decideva
 * il modello guardando una frase: «200 g secondo magro, 200 g verdure cotte,
 * 40 g pane integrale, 3 cucchiai olio → 640 kcal». Il codice lo salvava senza
 * poterlo verificare, perché non c'era niente da verificare — una cifra sola
 * non ha parti.
 *
 * Con le parti si vede dove sbaglia. Quel pranzo dichiarava 36 g di grassi:
 * tre cucchiai d'olio da soli li esauriscono, quindi alla carne ne restava
 * zero. Il totale non era un errore di somma, era una stima ottimistica — e a
 * riga singola non era smontabile. Sommati uno per uno, quegli stessi
 * ingredienti fanno circa 700 kcal, e l'errore si legge invece di doverlo
 * sospettare.
 *
 * Stessa forma di `workout_exercises` dentro una seduta, per la stessa
 * ragione: la cosa che si guarda ha delle parti, e le parti stanno in righe.
 * Il totale sul pasto resta, ma diventa una SOMMA — la calcola un observer,
 * non il modello.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meal_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('name');
            /*
             * La quantità è testo, non un numero di grammi.
             *
             * «3 cucchiai», «mezza pizza», «un piatto fondo» sono come si
             * mangia davvero, e costringerli in grammi vorrebbe dire
             * convertirli a indovinare per poterli scrivere — cioè inventare
             * una precisione al posto di registrare quello che si sa.
             */
            $table->string('quantity')->nullable();
            $table->unsignedSmallInteger('calories')->nullable();
            $table->unsignedSmallInteger('protein_g')->nullable();
            $table->unsignedSmallInteger('carbs_g')->nullable();
            $table->unsignedSmallInteger('fat_g')->nullable();
            $table->timestamps();

            $table->index(['meal_id', 'position']);
            $table->index(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_items');
    }
};
