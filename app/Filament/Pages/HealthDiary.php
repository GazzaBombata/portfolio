<?php

namespace App\Filament\Pages;

use App\Health\Diary;
use App\Models\User;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Il diario da portare via: un PDF con una riga per giorno.
 *
 * Il pannello serve a registrare e a guardare oggi; questa pagina serve a
 * guardare indietro fuori dal pannello — dal nutrizionista, dal medico, o
 * semplicemente per rileggere tre mesi tutti insieme, che è la cosa che una
 * dashboard non fa mai.
 */
class HealthDiary extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|\UnitEnum|null $navigationGroup = 'Salute';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Diario';

    protected static ?string $title = 'Diario';

    protected static ?string $slug = 'diario';

    protected string $view = 'filament.pages.health-diary';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $primo = Diary::firstDay();

        // Predefinito: tutto quello che c'è. «Da quando ho cominciato» è più
        // facile da offrire che da far ricordare.
        $this->form->fill([
            'dal' => ($primo ?? CarbonImmutable::today())->toDateString(),
            'al' => CarbonImmutable::today()->toDateString(),
            'solo_con_dati' => false,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Che periodo')
                    ->description('Una riga per giorno, dal più vecchio al più recente, con dentro tutto quello che di quel giorno è registrato.')
                    ->schema([
                        DatePicker::make('dal')->label('Dal')->native(false)->displayFormat('d/m/Y')->required(),
                        DatePicker::make('al')->label('Al')->native(false)->displayFormat('d/m/Y')->required(),
                        Toggle::make('solo_con_dati')
                            ->label('Salta i giorni senza niente dentro')
                            ->helperText('Di norma restano: un giorno vuoto racconta un\'interruzione, e toglierlo fa sembrare continuo un tracciamento che non lo è stato.'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function scarica(): ?StreamedResponse
    {
        $dati = $this->form->getState();

        $dal = CarbonImmutable::parse($dati['dal'])->startOfDay();
        $al = CarbonImmutable::parse($dati['al'])->startOfDay();

        if ($dal->greaterThan($al)) {
            Notification::make()->title('Il primo giorno viene dopo l\'ultimo')->warning()->send();

            return null;
        }

        /** @var User $utente */
        $utente = Auth::user();

        $righe = Diary::between($utente, $dal, $al, (bool) ($dati['solo_con_dati'] ?? false));

        $pdf = Pdf::loadView('pdf.diario', [
            'righe' => $righe,
            'dal' => $dal,
            'al' => $al,
            'utente' => $utente->name,
            'stampato' => CarbonImmutable::now(),
        ])->setPaper('a4', 'landscape');

        $nome = sprintf('diario-%s_%s.pdf', $dal->format('Y-m-d'), $al->format('Y-m-d'));

        return response()->streamDownload(fn () => print $pdf->output(), $nome);
    }
}
