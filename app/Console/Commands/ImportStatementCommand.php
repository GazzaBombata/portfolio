<?php

namespace App\Console\Commands;

use App\Finance\StatementImporter;
use App\Models\ImportProfile;
use App\Models\StatementImport;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

/**
 * Imports a statement from the command line. The panel will do the same thing
 * through an upload; this is what makes a year of back-files loadable in one go
 * without clicking through twelve uploads.
 */
class ImportStatementCommand extends Command
{
    protected $signature = 'finance:import
                            {file : Percorso del file da importare}
                            {--profile= : ID del profilo di importazione}
                            {--user= : Email dell\'utente (default: il primo)}';

    protected $description = 'Importa un estratto conto usando un profilo di importazione';

    public function handle(StatementImporter $importer): int
    {
        $user = $this->option('user') !== null
            ? User::where('email', $this->option('user'))->first()
            : User::query()->orderBy('id')->first();

        if ($user === null) {
            $this->error('Nessun utente trovato.');

            return self::FAILURE;
        }

        Auth::setUser($user);

        $profile = ImportProfile::find((int) $this->option('profile'));

        if ($profile === null) {
            $this->error('Profilo non trovato. Profili disponibili:');
            foreach (ImportProfile::with('account')->get() as $p) {
                $this->line("  [{$p->id}] {$p->name} → {$p->account->name}");
            }

            return self::FAILURE;
        }

        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("File non trovato: {$path}");

            return self::FAILURE;
        }

        $record = StatementImport::create([
            'account_id' => $profile->account_id,
            'filename' => basename($path),
            'disk_path' => $path,
        ]);

        $record = $importer->import($path, $profile, $record);

        if ($record->status === 'failed') {
            $this->error("Import fallito: {$record->error}");

            return self::FAILURE;
        }

        $this->line(sprintf(
            '  %-30s %4d righe → %4d importate, %3d duplicate, %3d scartate   [%s → %s]',
            basename($path),
            $record->rows_total,
            $record->rows_imported,
            $record->rows_duplicate,
            $record->rows_failed,
            $record->period_start?->format('d/m/Y') ?? '—',
            $record->period_end?->format('d/m/Y') ?? '—',
        ));

        return self::SUCCESS;
    }
}
