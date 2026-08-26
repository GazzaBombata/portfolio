<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Con quale modello è stata prodotta ogni risposta.
 *
 * Serve a due cose insieme: ricordare la scelta fatta nella chat (il menu
 * riparte da lì), e poter guardare a posteriori CHI ha risposto — se una
 * risposta è secca o sbagliata, la prima domanda utile è su quale modello
 * girava, e senza questa colonna non c'è modo di saperlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistant_messages', function (Blueprint $table): void {
            $table->string('model')->nullable()->after('topic');
        });
    }

    public function down(): void
    {
        Schema::table('assistant_messages', function (Blueprint $table): void {
            $table->dropColumn('model');
        });
    }
};
