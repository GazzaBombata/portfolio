<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Due conversazioni invece di una.
 *
 * Separarle non è solo ordine mentale: ogni chat porta con sé soltanto i
 * propri strumenti, e le definizioni degli strumenti sono il grosso di quello
 * che si paga a ogni domanda — sedici schemi valgono da soli 2.400 token, che
 * viaggiano identici a ogni battuta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistant_messages', function (Blueprint $table) {
            // I messaggi che c'erano prima riguardavano un po' tutto: restano
            // nella chat delle spese, che è quella da cui erano nati.
            $table->enum('topic', ['finance', 'health'])->default('finance')->after('user_id');
            $table->index(['user_id', 'topic', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('assistant_messages', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'topic', 'id']);
            $table->dropColumn('topic');
        });
    }
};
