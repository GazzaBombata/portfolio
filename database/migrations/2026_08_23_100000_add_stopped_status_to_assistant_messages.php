<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Uno stato per i turni fermati a metà.
 *
 * Un turno che gira non si può uccidere da fuori: il worker sta aspettando una
 * risposta dalla rete. Quello che si può fare è dirgli di non proseguire — lo
 * legge fra un giro di strumenti e l'altro — e non scrivere la risposta se nel
 * frattempo è stato fermato.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE assistant_messages MODIFY COLUMN status ENUM('pending','done','failed','stopped') NOT NULL DEFAULT 'done'");
    }

    public function down(): void
    {
        DB::statement("UPDATE assistant_messages SET status = 'failed' WHERE status = 'stopped'");
        DB::statement("ALTER TABLE assistant_messages MODIFY COLUMN status ENUM('pending','done','failed') NOT NULL DEFAULT 'done'");
    }
};
