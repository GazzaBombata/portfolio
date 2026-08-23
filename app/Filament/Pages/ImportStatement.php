<?php

namespace App\Filament\Pages;

use App\Finance\StatementImporter;
use App\Models\ImportProfile;
use App\Models\StatementImport;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Carica un estratto conto e importalo con un profilo salvato.
 *
 * Il file resta su disco dopo l'import: se un mese torna sbagliato, la prima
 * domanda è sempre «cosa c'era davvero nel file», e senza l'originale non c'è
 * modo di rispondere.
 */
class ImportStatement extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|\UnitEnum|null $navigationGroup = 'Spese';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Importa estratto';

    protected static ?string $title = 'Importa un estratto conto';

    protected string $view = 'filament.pages.import-statement';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('import_profile_id')
                            ->label('Profilo')
                            ->options(fn (): array => ImportProfile::query()
                                ->with('account')
                                ->get()
                                ->mapWithKeys(fn (ImportProfile $p): array => [
                                    $p->id => $p->name.' → '.$p->account->name,
                                ])
                                ->all())
                            ->searchable()
                            ->required()
                            ->helperText('Dice come leggere il file. Se la banca ha cambiato tracciato, correggi il profilo invece di rinunciare.'),

                        FileUpload::make('file')
                            ->label('File')
                            ->acceptedFileTypes([
                                'text/csv', 'text/plain', 'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->disk('local')
                            ->directory('statements')
                            ->visibility('private')
                            ->required()
                            ->helperText('CSV o Excel, così come lo scarichi dalla banca.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function import(): void
    {
        $dati = $this->form->getState();

        $profilo = ImportProfile::find($dati['import_profile_id']);

        if ($profilo === null) {
            Notification::make()->title('Profilo non trovato')->danger()->send();

            return;
        }

        // FileUpload restituisce un array quando è multiplo e una stringa
        // quando non lo è: normalizzato qui, una volta.
        $percorso = is_array($dati['file']) ? reset($dati['file']) : $dati['file'];
        $assoluto = Storage::disk('local')->path($percorso);

        $record = StatementImport::create([
            'account_id' => $profilo->account_id,
            'filename' => basename($percorso),
            'disk_path' => $percorso,
        ]);

        try {
            $record = app(StatementImporter::class)->import($assoluto, $profilo, $record);
        } catch (Throwable $e) {
            $record->update(['status' => 'failed', 'error' => $e->getMessage()]);
        }

        if ($record->status === 'failed') {
            Notification::make()
                ->title('Import non riuscito')
                ->body($record->error ?? 'Errore sconosciuto.')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title('Import completato')
            ->body($this->riepilogo($record))
            ->success()
            ->persistent()
            ->send();

        $this->form->fill();
    }

    /**
     * Il conto di com'è andata, comprese le righe scartate.
     *
     * Le duplicate vanno dette e non nascoste: reimportare lo stesso mese è
     * una cosa che si fa per sbaglio, e vedere «0 importate» senza spiegazione
     * sembra un guasto invece del comportamento corretto.
     */
    private function riepilogo(StatementImport $record): string
    {
        $parti = ["{$record->rows_imported} movimenti importati"];

        if ($record->rows_duplicate > 0) {
            $parti[] = "{$record->rows_duplicate} già presenti (saltati)";
        }

        if ($record->rows_failed > 0) {
            $parti[] = "{$record->rows_failed} righe non leggibili (di solito intestazioni o saldi)";
        }

        if ($record->period_start !== null) {
            $parti[] = 'dal '.$record->period_start->format('d/m/Y').' al '.$record->period_end->format('d/m/Y');
        }

        return implode(' · ', $parti);
    }

    /** @return Collection<int, StatementImport> */
    public function getRecentImportsProperty(): Collection
    {
        return StatementImport::query()->with('account')->latest('id')->limit(8)->get();
    }
}
