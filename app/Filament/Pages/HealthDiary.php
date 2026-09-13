<?php

namespace App\Filament\Pages;

use App\Health\DayRecalculator;
use App\Health\Diary;
use App\Models\BodyMetric;
use App\Models\DailyLog;
use App\Models\SleepLog;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Il diario: una riga per giorno, da guardare, da correggere e da portare via.
 *
 * Il pannello serve a registrare e a guardare oggi; questa pagina serve a
 * guardare indietro tutto insieme — cosa che una dashboard non fa mai — e a
 * sistemare quello che si scopre guardando.
 *
 * La griglia è editabile perché è lì che i buchi si vedono: una colonna di
 * passi con dentro cinque celle vuote si legge in un colpo d'occhio, e
 * riempirle da qui costa un clic invece di cinque schermate. Le celle
 * modificabili sono solo quelle che hanno UN valore al giorno — sonno, peso,
 * passi, acqua, aderenza. Pasti e allenamenti sono elenchi, non caselle: si
 * vedono in sola lettura e si correggono dalla loro pagina, perché un pasto
 * dentro una cella vorrebbe dire scegliere quale dei tre mostrare.
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

    /**
     * Quante righe si disegnano al massimo.
     *
     * Il PDF di un anno è un file; una griglia di un anno sono tremila caselle
     * che il browser deve tenere insieme. Il tetto è dichiarato sotto la
     * tabella invece di essere applicato in silenzio: una tabella che mostra
     * un pezzo spacciandolo per l'insieme è il modo in cui nasce un conto
     * sbagliato.
     */
    private const MASSIMO_RIGHE = 90;

    /**
     * Le celle che si possono correggere, con l'intervallo ammesso.
     *
     * Solo roba che ha un valore solo al giorno: le tre tabelle sotto hanno
     * tutte un indice unico sulla data, quindi scrivere qui corregge invece di
     * duplicare. Gli estremi non sono decorazione — fermano un peso di 940 kg
     * battuto di fretta, che in un grafico si vede per sempre.
     *
     * @var array<string, array{0: string, 1: string, 2: float, 3: float}>
     */
    private const CAMPI = [
        'minuti' => ['sonno', 'minutes', 0, 1440],
        'qualita' => ['sonno', 'quality', 1, 5],
        'risvegli' => ['sonno', 'awakenings', 0, 50],
        'peso' => ['corpo', 'weight_kg', 20, 400],
        'grasso' => ['corpo', 'body_fat_pct', 1, 80],
        'battito' => ['corpo', 'resting_hr', 25, 220],
        'passi' => ['giornata', 'steps', 0, 200000],
        'acqua' => ['giornata', 'water_litres', 0, 20],
        'aderenza' => ['giornata', 'nutrition_adherence', 1, 10],
    ];

    /** @var array<string, mixed> */
    public array $data = [];

    /** @var array<int, array<string, mixed>> */
    public array $righe = [];

    /** Quanti giorni ha l'intervallo scelto, anche quando la griglia ne mostra meno. */
    public int $giorniNellIntervallo = 0;

    public ?string $salvatoAlle = null;

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

        $this->carica();
    }

    /**
     * Rilegge le righe dell'intervallo.
     *
     * Si richiama dopo ogni cella salvata, e non è uno spreco: correggere i
     * passi cambia il fabbisogno, e una riga che mostra il numero vecchio
     * accanto a quello appena scritto è peggio di una che non lo mostra.
     */
    public function carica(): void
    {
        $dati = $this->form->getState();

        $dal = CarbonImmutable::parse($dati['dal'] ?? 'today')->startOfDay();
        $al = CarbonImmutable::parse($dati['al'] ?? 'today')->startOfDay();

        if ($dal->greaterThan($al)) {
            $this->righe = [];
            $this->giorniNellIntervallo = 0;

            return;
        }

        /** @var User $utente */
        $utente = Auth::user();

        $tutte = Diary::between($utente, $dal, $al, (bool) ($dati['solo_con_dati'] ?? false));

        $this->giorniNellIntervallo = count($tutte);

        // Si tengono gli ULTIMI, non i primi: se qualcosa va corretto è quasi
        // sempre in fondo, ed è dove si guarda per primo.
        $this->righe = array_slice($tutte, -self::MASSIMO_RIGHE);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Che periodo')
                    ->description('Una riga per giorno, dal più vecchio al più recente, con dentro tutto quello che di quel giorno è registrato.')
                    ->schema([
                        // live(): la griglia sotto segue l'intervallo senza che
                        // ci sia un pulsante «mostra» da ricordarsi di premere.
                        DatePicker::make('dal')->label('Dal')->native(false)->displayFormat('d/m/Y')->required()
                            ->live()->afterStateUpdated(fn () => $this->carica()),
                        DatePicker::make('al')->label('Al')->native(false)->displayFormat('d/m/Y')->required()
                            ->live()->afterStateUpdated(fn () => $this->carica()),
                        Toggle::make('solo_con_dati')
                            ->live()->afterStateUpdated(fn () => $this->carica())
                            ->label('Salta i giorni senza niente dentro')
                            ->helperText('Di norma restano: un giorno vuoto racconta un\'interruzione, e toglierlo fa sembrare continuo un tracciamento che non lo è stato.'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    /**
     * Scrive una cella.
     *
     * Fallisce parlando invece di lanciare: un intervallo sbagliato qui è una
     * distrazione di chi digita, non un bug, e una pagina che va in errore
     * perde anche le altre celle aperte.
     */
    public function salva(string $giorno, string $campo, ?string $valore = null): void
    {
        $data = CarbonImmutable::parse($giorno)->startOfDay();
        $grezzo = trim((string) $valore);
        $vuoto = $grezzo === '';

        if ($campo === 'note') {
            $this->scrivi(DailyLog::class, 'logged_on', $data, 'notes', $vuoto ? null : $grezzo);
            $this->fatto();

            return;
        }

        if (! array_key_exists($campo, self::CAMPI)) {
            return;
        }

        [$gruppo, $colonna, $min, $max] = self::CAMPI[$campo];

        $numero = null;

        if (! $vuoto) {
            $numero = (float) str_replace(',', '.', $grezzo);

            if (! is_numeric(str_replace(',', '.', $grezzo)) || $numero < $min || $numero > $max) {
                Notification::make()
                    ->title("Valore fuori intervallo: {$campo} sta fra {$min} e {$max}")
                    ->warning()
                    ->send();

                // Si ricarica per rimettere in cella il valore che c'era: la
                // casella non deve restare con dentro una cifra che il
                // database non ha.
                $this->carica();

                return;
            }

            // Le colonne intere vogliono interi: un peso resta decimale,
            // i passi no.
            $numero = in_array($campo, ['peso', 'grasso', 'acqua'], true) ? $numero : (int) round($numero);
        }

        [$modello, $chiave] = match ($gruppo) {
            // Una notte appartiene alla sera in cui è cominciata: stessa
            // convenzione della colonna nel PDF, e sbagliarla sposta ogni
            // valore di un giorno.
            'sonno' => [SleepLog::class, 'night_of'],
            'corpo' => [BodyMetric::class, 'measured_on'],
            'giornata' => [DailyLog::class, 'logged_on'],
        };

        $this->scrivi($modello, $chiave, $data, $colonna, $numero);

        /*
         * Passi e peso entrano nel fabbisogno, quindi la copia salvata sulla
         * giornata va rimessa in pari — è quello che fa `registra_giornata`
         * quando i passi arrivano dalla chat, e questa è la stessa scrittura
         * da un'altra porta.
         *
         * Tocca solo il giorno che si sta correggendo: non è il ricalcolo
         * dello storico, è un giorno che qualcuno ha appena messo le mani
         * sopra.
         */
        if (in_array($campo, ['passi', 'peso'], true)) {
            DayRecalculator::for($data);
        }

        $this->fatto();
    }

    /**
     * @param  class-string<Model>  $modello
     */
    private function scrivi(string $modello, string $chiave, CarbonImmutable $giorno, string $colonna, float|int|string|null $valore): void
    {
        $esistente = $modello::query()->firstWhere($chiave, $giorno->toDateString());

        // Svuotare una cella di un giorno che non ha una riga non deve
        // crearne una vuota: una tabella piena di righe che non dicono niente
        // fa smettere di significare qualcosa a «quanti giorni ho tracciato».
        if ($esistente === null && $valore === null) {
            return;
        }

        $modello::updateOrCreate([$chiave => $giorno->toDateString()], [$colonna => $valore]);
    }

    private function fatto(): void
    {
        $this->salvatoAlle = CarbonImmutable::now()->format('H:i:s');

        $this->carica();
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
