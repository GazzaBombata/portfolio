<?php

namespace App\Console\Commands;

use App\Finance\AiCategoriser;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class AiCategoriseCommand extends Command
{
    protected $signature = 'finance:ai-categorise {--user=} {--limit= : Quanti esercenti al massimo}';

    protected $description = 'Fa classificare a Claude gli esercenti che le regole non coprono, e ne ricava regole';

    public function handle(AiCategoriser $categoriser): int
    {
        $user = $this->option('user') !== null
            ? User::where('email', $this->option('user'))->first()
            : User::query()->orderBy('id')->first();

        if ($user === null) {
            $this->error('Nessun utente trovato.');

            return self::FAILURE;
        }

        Auth::setUser($user);

        $esito = $categoriser->run($this->option('limit') !== null ? (int) $this->option('limit') : null);

        $this->line("  Esercenti esaminati: {$esito['merchants']}");
        $this->line("  Regole create: {$esito['rules']}");
        $this->line("  Movimenti classificati: {$esito['categorised']}");
        // Non è un fallimento: è il modello che dice "non lo so" invece di
        // indovinare, ed è la risposta giusta su un bonifico.
        $this->line("  Lasciati a te: {$esito['undecided']} esercenti non deducibili dal nome");
        $this->line('  Ancora da classificare: '.Transaction::whereNull('category_id')->count().' movimenti');

        return self::SUCCESS;
    }
}
