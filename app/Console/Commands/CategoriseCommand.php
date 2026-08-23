<?php

namespace App\Console\Commands;

use App\Finance\Categoriser;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class CategoriseCommand extends Command
{
    protected $signature = 'finance:categorise {--user=} {--all : Riclassifica anche quelle già classificate automaticamente}';

    protected $description = 'Applica le regole di categorizzazione ai movimenti';

    public function handle(Categoriser $categoriser): int
    {
        $user = $this->option('user') !== null
            ? User::where('email', $this->option('user'))->first()
            : User::query()->orderBy('id')->first();

        if ($user === null) {
            $this->error('Nessun utente trovato.');

            return self::FAILURE;
        }

        Auth::setUser($user);

        $esito = $categoriser->run(onlyUncategorised: ! $this->option('all'));
        $restanti = Transaction::whereNull('category_id')->count();

        $this->line("  Classificati: {$esito['categorised']}");
        $this->line("  Ancora da classificare: {$restanti}");

        return self::SUCCESS;
    }
}
