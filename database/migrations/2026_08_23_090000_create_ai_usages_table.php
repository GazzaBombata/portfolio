<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ogni chiamata al modello, con quanto è costata.
 *
 * Serve a due cose che senza questa tabella non si possono fare: sapere quanto
 * si sta spendendo, e fermarsi prima di spendere troppo. Il costo è calcolato
 * al momento della chiamata e salvato: i listini cambiano, e ricalcolare a
 * posteriori la spesa di sei mesi fa con i prezzi di oggi darebbe un numero
 * che non è mai stato vero.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind');
            $table->string('model');
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('cache_read_tokens')->default(0);
            $table->decimal('cost', 10, 6)->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usages');
    }
};
