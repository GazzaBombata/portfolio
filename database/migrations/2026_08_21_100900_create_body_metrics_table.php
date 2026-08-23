<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Weight and the other numbers measured on a given morning. One row per day at
 * most — weighing twice before breakfast is noise, not data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('body_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('measured_on');
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('body_fat_pct', 4, 1)->nullable();
            $table->decimal('muscle_mass_kg', 5, 2)->nullable();
            $table->unsignedSmallInteger('resting_hr')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'measured_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('body_metrics');
    }
};
