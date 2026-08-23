<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The once-a-day numbers that are not events: how much water, how closely the
 * nutrition plan was followed. One row per day, so filling it in twice corrects
 * rather than duplicates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('logged_on');
            $table->decimal('water_litres', 4, 2)->nullable();
            // 1 = ignorato del tutto … 10 = seguito alla lettera.
            $table->unsignedTinyInteger('nutrition_adherence')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'logged_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_logs');
    }
};
