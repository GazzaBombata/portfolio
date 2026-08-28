<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Previsto e fatto, e chi l'ha deciso.
 *
 * `kind` è il gemello di quello sui pasti, e serve alla stessa cosa: una
 * seduta in programma per giovedì non ha bruciato niente, e contarla nel
 * fabbisogno di giovedì annuncerebbe un margine guadagnato con un allenamento
 * che non è ancora stato fatto.
 *
 * `authored_by` invece non ha un gemello, perché il caso è diverso. Il piano
 * dei pasti viene da fuori — lo scrive un nutrizionista e l'assistente lo
 * trascrive — mentre una scheda di allenamento qui la propone l'assistente
 * stesso. Fra un mese la differenza fra «l'ho deciso io» e «me l'ha proposta
 * un modello» non si ricostruisce a memoria, e serve saperla proprio quando
 * si guarda indietro per capire cosa ha funzionato.
 *
 * Entrambe hanno un valore predefinito perché tutto quello che c'era prima era
 * fatto, e l'aveva scritto una persona.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->enum('kind', ['done', 'planned'])->default('done')->after('user_id');
            $table->enum('authored_by', ['person', 'assistant'])->default('person')->after('kind');
            $table->index(['user_id', 'performed_on', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'performed_on', 'kind']);
            $table->dropColumn(['kind', 'authored_by']);
        });
    }
};
