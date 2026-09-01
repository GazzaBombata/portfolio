<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Il contesto che ogni persona dà ai propri assistenti.
 *
 * Prima le cose che valevano per una persona sola stavano scritte nel prompt:
 * il nome, e quel poco di situazione che serviva a rispondere con criterio.
 * Funziona finché l'utente è uno. Al secondo, o si scrive un prompt per
 * ciascuno — e allora ogni riga di dominio va tenuta allineata in due copie —
 * oppure il secondo si prende addosso il profilo del primo.
 *
 * Da qui in poi il prompt è impersonale e i fatti arrivano da queste tre
 * colonne. C'è anche un guadagno che non si vede: un prompt uguale per tutti è
 * lo stesso prefisso in cache per tutti.
 *
 * Tre campi e non uno perché il consulente delle spese non ha motivo di sapere
 * quanto pesi, e quello della salute non ha motivo di sapere quanto guadagni.
 * Sono le stesse due conversazioni che non si vedono fra loro: mescolare qui
 * il contesto annullerebbe la divisione, e la pagherebbe in token a ogni
 * domanda.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Vale per tutti e due: chi sei, cosa fai, come ti si parla.
            $table->text('assistant_notes')->nullable()->after('health_notes');
            $table->text('finance_notes')->nullable()->after('assistant_notes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['assistant_notes', 'finance_notes']);
        });
    }
};
