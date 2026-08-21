<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deterministic categorisation, tried before anything is sent to a model.
 *
 * Most of a bank statement is the same twenty merchants every month. Matching
 * them with a stored rule is instant and free; the model is for the tail. Rules
 * are also what makes a correction stick: recategorise ESSELUNGA once and the
 * rule catches it forever.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('pattern');
            $table->enum('match_type', ['contains', 'starts_with', 'exact', 'regex'])->default('contains');
            // Lower runs first, so a specific rule can sit in front of a broad one.
            $table->unsignedSmallInteger('priority')->default(100);
            // Learned from a correction rather than written by hand. Kept apart so
            // the guessed ones can be reviewed or wiped without touching the rest.
            $table->boolean('auto_learned')->default(false);
            $table->unsignedInteger('times_applied')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_rules');
    }
};
