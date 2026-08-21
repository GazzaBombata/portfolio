<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One night per row, keyed on the night it *started* — so "Friday night" is one
 * record even though most of it happens on Saturday.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sleep_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('night_of');
            $table->dateTime('fell_asleep_at')->nullable();
            $table->dateTime('woke_up_at')->nullable();
            // Stored rather than derived: it is often the only thing known ("about
            // seven hours"), and deriving it from two nullable times would lose it.
            $table->unsignedSmallInteger('minutes')->nullable();
            // 1 terrible … 5 excellent. Deliberately coarse — a finer scale invites
            // deliberation over a number nobody can report accurately anyway.
            $table->unsignedTinyInteger('quality')->nullable();
            $table->unsignedTinyInteger('awakenings')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'night_of']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sleep_logs');
    }
};
