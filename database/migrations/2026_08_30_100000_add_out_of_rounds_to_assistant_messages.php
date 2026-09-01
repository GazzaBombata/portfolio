<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quali turni hanno sbattuto contro il tetto dei giri.
 *
 * Finché la risposta di fine giri era una frase fissa, contarli era un
 * `where content like`. Adesso quella frase la scrive il modello ed è diversa
 * ogni volta: senza questa colonna non si saprebbe più quanto spesso sei
 * passaggi non bastano — cioè proprio il numero che dice se il tetto va alzato
 * o se le domande vanno strette.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistant_messages', function (Blueprint $table) {
            $table->boolean('out_of_rounds')->default(false)->after('steps');
        });
    }

    public function down(): void
    {
        Schema::table('assistant_messages', function (Blueprint $table) {
            $table->dropColumn('out_of_rounds');
        });
    }
};
