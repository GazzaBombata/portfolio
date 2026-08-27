<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * I dispositivi su cui il secondo fattore è già stato superato.
 *
 * Serve a non far digitare il codice tutti i giorni dallo stesso portatile.
 * Il token vive in un cookie e qui ne resta solo l'impronta: se qualcuno
 * legge questa tabella non ottiene niente con cui entrare.
 *
 * `expires_at` è una scadenza vera e non scorrevole: un accesso saltato non
 * allunga la fiducia, altrimenti «una volta a settimana» diventerebbe «mai
 * più» sul dispositivo che si usa tutti i giorni — cioè proprio quello da cui
 * conviene rubare una sessione.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trusted_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('label')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
    }
};
