<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * I passi della giornata.
 *
 * Non sono un allenamento — non hanno un inizio, una durata né un'intensità —
 * ma sono la parte di movimento che una persona fa senza accorgersene, ed è
 * quella che distingue una giornata da 5.000 passi da una da 21.000 anche
 * quando entrambe non hanno nessun allenamento registrato.
 *
 * NON entrano nel calcolo calorico: il fattore di attività del profilo li
 * copre già in modo grossolano, e sommarli sarebbe contarli due volte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_logs', function (Blueprint $table) {
            $table->unsignedMediumInteger('steps')->nullable()->after('water_litres');
        });
    }

    public function down(): void
    {
        Schema::table('daily_logs', function (Blueprint $table) {
            $table->dropColumn('steps');
        });
    }
};
