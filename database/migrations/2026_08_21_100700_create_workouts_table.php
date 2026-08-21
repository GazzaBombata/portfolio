<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Physical activity: what kind, how long, how hard, how much of it.
 *
 * `activity` is free text rather than an enum because the list of things a
 * person does changes faster than a migration does, and an unknown activity
 * must be recordable the first time it happens, not after a deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('performed_on');
            $table->time('started_at')->nullable();
            $table->string('activity');
            $table->unsignedSmallInteger('minutes')->nullable();
            // Only meaningful for some activities; null is normal, not missing.
            $table->decimal('distance_km', 6, 2)->nullable();
            $table->unsignedSmallInteger('sets')->nullable();
            $table->unsignedSmallInteger('reps')->nullable();
            $table->decimal('load_kg', 6, 2)->nullable();
            // 1 easy … 5 all-out. Same coarse scale as sleep quality, on purpose.
            $table->unsignedTinyInteger('intensity')->nullable();
            $table->unsignedSmallInteger('calories')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'performed_on']);
            $table->index(['user_id', 'activity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workouts');
    }
};
