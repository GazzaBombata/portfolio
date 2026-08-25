<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Previsto e consumato nella stessa tabella.
 *
 * Prima il piano era una stringa su `daily_logs` e i pasti veri erano righe
 * qui: due forme diverse per la stessa cosa, che si possono mettere una
 * accanto all'altra solo a occhio. Con lo stesso schema si confrontano voce
 * per voce — pranzo previsto contro pranzo mangiato — e le calorie del piano
 * si sommano come quelle vere.
 *
 * `kind` ha un valore predefinito perché tutto quello che c'era prima era, per
 * definizione, cibo mangiato.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meals', function (Blueprint $table) {
            $table->enum('kind', ['eaten', 'planned'])->default('eaten')->after('user_id');
            $table->index(['user_id', 'eaten_on', 'kind']);
        });

        /*
         * Il piano scritto a mano finora non si butta: diventa una nota della
         * giornata. Non lo trasformo in pasti previsti perché era una frase
         * unica — spezzarla a indovinare produrrebbe righe che nessuno ha mai
         * scritto.
         */
        foreach (DB::table('daily_logs')->whereNotNull('planned_meals')->get() as $log) {
            DB::table('daily_logs')->where('id', $log->id)->update([
                'notes' => trim(($log->notes ? $log->notes."\n\n" : '').'Piano (dalla versione precedente): '.$log->planned_meals),
            ]);
        }

        Schema::table('daily_logs', function (Blueprint $table) {
            $table->dropColumn('planned_meals');
        });
    }

    public function down(): void
    {
        Schema::table('daily_logs', function (Blueprint $table) {
            $table->text('planned_meals')->nullable();
        });

        Schema::table('meals', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'eaten_on', 'kind']);
            $table->dropColumn('kind');
        });
    }
};
