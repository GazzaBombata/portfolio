<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La conversazione con l'assistente. Una riga per messaggio, con gli strumenti
 * che ha usato per rispondere: senza quelli, "l'ho registrato" è una frase da
 * credere sulla parola.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant']);
            $table->longText('content')->nullable();
            $table->json('steps')->nullable();
            $table->enum('status', ['pending', 'done', 'failed'])->default('done');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_messages');
    }
};
