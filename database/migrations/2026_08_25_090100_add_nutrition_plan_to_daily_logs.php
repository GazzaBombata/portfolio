<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Il piano della giornata: cosa avrebbe dovuto mangiare, e quanto.
 *
 * Sta accanto a quello che ha mangiato davvero perché la domanda interessante
 * non è nessuna delle due da sola, ma la distanza fra le due.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_logs', function (Blueprint $table) {
            // Il fabbisogno del giorno. Salvato invece che ricalcolato ogni
            // volta: dipende dal peso di quel giorno e dall'attività di quel
            // giorno, e ricalcolarlo a distanza di mesi con i dati di oggi
            // darebbe un numero che non è mai stato vero.
            $table->unsignedSmallInteger('target_calories')->nullable();
            $table->unsignedSmallInteger('target_protein_g')->nullable();

            // Cosa prevedeva il piano, a parole.
            $table->text('planned_meals')->nullable();

            // Quanto è stato bruciato con l'attività, sommato dagli
            // allenamenti registrati quel giorno.
            $table->unsignedSmallInteger('activity_calories')->nullable();

            // Come sono stati ottenuti i numeri qui sopra: calcolati dalle
            // formule, oppure scritti a mano da una persona che sa qualcosa
            // che le formule non sanno.
            $table->boolean('targets_manual')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('daily_logs', function (Blueprint $table) {
            $table->dropColumn([
                'target_calories', 'target_protein_g', 'planned_meals',
                'activity_calories', 'targets_manual',
            ]);
        });
    }
};
