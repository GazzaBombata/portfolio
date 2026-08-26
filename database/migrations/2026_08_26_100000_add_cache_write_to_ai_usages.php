<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La scrittura in cache si paga (1,25× l'input) e finora non era contata da
 * nessuna parte: `inputTokens` non la comprende. Con la cache accesa il tetto
 * mensile avrebbe guardato una spesa più bassa di quella vera.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_usages', function (Blueprint $table): void {
            $table->unsignedInteger('cache_write_tokens')->default(0)->after('cache_read_tokens');
        });
    }

    public function down(): void
    {
        Schema::table('ai_usages', function (Blueprint $table): void {
            $table->dropColumn('cache_write_tokens');
        });
    }
};
