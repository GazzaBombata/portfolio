<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every line of every statement. Amounts are signed: negative is money out,
 * positive is money in, so a period is summed by adding rather than by knowing
 * which column the bank happened to use.
 *
 * DECIMAL, not float: 0.1 + 0.2 must be 0.3 when the number is a euro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('statement_import_id')->nullable()->constrained()->nullOnDelete();

            $table->date('booked_on');
            // What the bank calls the value date. Kept because interest and some
            // reconciliations use it, but every report groups by booked_on.
            $table->date('valued_on')->nullable();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('EUR');

            // The bank's own wording, stored untouched: it is the evidence. The
            // cleaned-up version used for matching and display lives alongside.
            $table->text('raw_description');
            $table->string('description')->nullable();
            $table->string('counterparty')->nullable();

            /*
             * Deduplication.
             *
             * `fingerprint` hashes account + date + amount + normalised text.
             * That alone would be wrong: two identical coffees on the same day
             * are two real transactions, not a duplicate. So identical rows are
             * numbered within their day — `occurrence` 1, 2, 3 — and the unique
             * key covers both. Re-importing a month then matches row for row and
             * changes nothing; a month with one extra coffee inserts exactly one.
             */
            $table->char('fingerprint', 40);
            $table->unsignedSmallInteger('occurrence')->default(1);

            // Set when a human (or the assistant on their instruction) picked the
            // category, so the automatic pass never overwrites a real decision.
            $table->boolean('category_locked')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'fingerprint', 'occurrence']);
            // The shape of every report: this user, this window, newest first.
            $table->index(['user_id', 'booked_on']);
            $table->index(['user_id', 'category_id', 'booked_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
