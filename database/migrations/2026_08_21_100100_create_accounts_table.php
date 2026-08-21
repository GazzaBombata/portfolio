<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bank accounts a user imports statements from. One row per real account, so a
 * transaction always knows where it came from and the same CSV can never be
 * attributed to the wrong account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('bank')->nullable();
            // Never the full IBAN: the last four digits are enough to tell two
            // accounts apart, and this table is not where account numbers live.
            $table->string('iban_last4', 4)->nullable();
            $table->char('currency', 3)->default('EUR');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
