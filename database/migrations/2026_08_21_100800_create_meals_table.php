<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What was eaten, described in words.
 *
 * No food database and no per-ingredient rows: the thing that gets abandoned in
 * a personal tracker is weighing and looking things up. A sentence typed (or
 * dictated to the assistant) is what actually gets recorded every day, and the
 * optional numbers can be filled in later — by the assistant, or never.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('eaten_on');
            $table->enum('moment', ['breakfast', 'lunch', 'dinner', 'snack'])->default('lunch');
            $table->time('eaten_at')->nullable();
            $table->text('description');
            $table->unsignedSmallInteger('calories')->nullable();
            $table->unsignedSmallInteger('protein_g')->nullable();
            $table->unsignedSmallInteger('carbs_g')->nullable();
            $table->unsignedSmallInteger('fat_g')->nullable();
            // Whether the numbers above were estimated by the model rather than
            // known. An estimate shown as a fact becomes a fact by Tuesday.
            $table->boolean('nutrition_estimated')->default(false);
            $table->boolean('eaten_out')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'eaten_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};
