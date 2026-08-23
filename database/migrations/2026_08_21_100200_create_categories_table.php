<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spending categories, one tree per user — so the two people sharing this app
 * can name and group their spending differently without arguing about it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('color', 7)->nullable();
            $table->string('icon')->nullable();
            // Income and expense live in the same tree but must never be summed
            // together: a salary in the same total as the groceries makes the
            // month look free.
            $table->enum('kind', ['expense', 'income', 'transfer'])->default('expense');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'kind']);
            $table->unique(['user_id', 'parent_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
