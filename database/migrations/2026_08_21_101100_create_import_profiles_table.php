<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How to read one bank's export, decided once from the interface and reused
 * every month.
 *
 * The point of this table is that no bank is named anywhere in the code. Five
 * institutions export five different shapes — header on row 12, American dates,
 * debits and credits in separate columns, expenses stored positive — and all of
 * it is data here rather than a parser per bank. When a bank changes its layout
 * the fix is editing a row from the panel, not a deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('name');

            // Where the real table starts. Statements put the account holder,
            // the IBAN and the balances above it — for one of these accounts,
            // nineteen rows of it.
            $table->unsignedSmallInteger('header_row')->default(1);
            $table->string('sheet_name')->nullable();
            $table->char('delimiter', 1)->default(';');

            // Explicit, never guessed: 05/06/2026 is a valid date read either
            // way, and guessing it wrong is silent.
            $table->string('date_format')->default('d/m/Y');
            $table->char('decimal_separator', 1)->default(',');
            $table->char('thousands_separator', 1)->nullable();

            /*
             * How the amount is written:
             *  - signed   → one column, minus means money out (ING)
             *  - inverted → one column, a PLUS means money out (Amex)
             *  - split    → two columns, debits and credits (BancoPosta)
             */
            $table->enum('amount_mode', ['signed', 'inverted', 'split'])->default('signed');

            // Which source column feeds which field. A map, so adding a column
            // to the file is a re-mapping and not a broken import.
            $table->json('columns');

            $table->timestamps();

            $table->index(['user_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_profiles');
    }
};
