<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per uploaded CSV. Keeps the counts the import reported, so a month
 * that looks wrong can be traced back to the file that produced it — and the
 * whole batch can be rolled back without hunting for its rows by date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statement_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('disk_path')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->unsignedInteger('rows_total')->default(0);
            $table->unsignedInteger('rows_imported')->default(0);
            // Rows recognised as already present. Re-uploading the same month is
            // a normal thing to do by accident, and it must be a no-op.
            $table->unsignedInteger('rows_duplicate')->default(0);
            $table->unsignedInteger('rows_failed')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statement_imports');
    }
};
