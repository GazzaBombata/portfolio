<?php

namespace App\Console\Commands;

use App\Finance\TransferMatcher;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class MatchTransfersCommand extends Command
{
    protected $signature = 'finance:transfers {--user= : Email dell\'utente}';

    protected $description = 'Riconosce i movimenti che spostano soldi fra conti propri e li marca come giroconti';

    public function handle(TransferMatcher $matcher): int
    {
        $user = $this->option('user') !== null
            ? User::where('email', $this->option('user'))->first()
            : User::query()->orderBy('id')->first();

        if ($user === null) {
            $this->error('Nessun utente trovato.');

            return self::FAILURE;
        }

        Auth::setUser($user);

        $esito = $matcher->run();

        $this->line("  Giroconti riconosciuti: {$esito['paired']} coppie");

        if ($esito['ambiguous'] > 0) {
            // Detto, non taciuto: sono movimenti che restano nei totali e che
            // solo una persona può decidere.
            $this->line("  Da controllare a mano: {$esito['ambiguous']} movimenti con più di un abbinamento possibile");
        }

        return self::SUCCESS;
    }
}
