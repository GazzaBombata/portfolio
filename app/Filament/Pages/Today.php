<?php

namespace App\Filament\Pages;

use App\Models\BodyMetric;
use App\Models\DailyLog;
use App\Models\Meal;
use App\Models\SleepLog;
use App\Models\Workout;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Tutto quello che si registra in un giorno, in una schermata sola.
 *
 * Le cinque risorse esistono e servono a correggere e a rivedere; questa serve
 * al gesto quotidiano, che è diverso: aprire, scrivere quattro numeri, uscire.
 * Passare da cinque schermate per registrare una giornata è il modo in cui si
 * smette di registrarla dopo una settimana.
 *
 * Ogni riquadro si salva per conto suo. Un modulo unico costringerebbe a
 * riempire il sonno per poter salvare il peso.
 */
class Today extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSun;

    protected static string|\UnitEnum|null $navigationGroup = 'Salute';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Oggi';

    protected static ?string $title = 'Oggi';

    protected string $view = 'filament.pages.today';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        // Precompilato con quello che c'è già: così questa schermata corregge
        // la giornata invece di duplicarla.
        $notte = SleepLog::query()->firstWhere('night_of', now()->subDay()->toDateString());
        $giornata = DailyLog::query()->firstWhere('logged_on', now()->toDateString());
        $peso = BodyMetric::query()->firstWhere('measured_on', now()->toDateString());

        $this->form->fill([
            'minutes' => $notte?->minutes,
            'quality' => $notte?->quality,
            'water_litres' => $giornata?->water_litres,
            'nutrition_adherence' => $giornata?->nutrition_adherence,
            'weight_kg' => $peso?->weight_kg,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Stanotte')
                    ->description('La notte appena passata: quella cominciata ieri sera.')
                    ->schema([
                        TextInput::make('minutes')->label('Minuti dormiti')->numeric()->minValue(0)->maxValue(1440),
                        Select::make('quality')
                            ->label('Com\'è andata')
                            ->options([1 => '1 · pessima', 2 => '2 · scarsa', 3 => '3 · normale', 4 => '4 · buona', 5 => '5 · ottima'])
                            ->native(false),
                    ])
                    ->columns(2),

                Section::make('Oggi')
                    ->schema([
                        TextInput::make('water_litres')->label('Acqua')->numeric()->suffix('litri')->step('0.25')->minValue(0),
                        Select::make('nutrition_adherence')
                            ->label('Piano nutrizionale')
                            ->options(array_combine(range(1, 10), array_map(
                                fn (int $n): string => match ($n) {
                                    1 => '1 · per niente', 5 => '5 · a metà', 10 => '10 · alla lettera',
                                    default => (string) $n,
                                },
                                range(1, 10),
                            )))
                            ->native(false),
                        TextInput::make('weight_kg')->label('Peso')->numeric()->suffix('kg')->step('0.1'),
                    ])
                    ->columns(3),

                Section::make('Ti sei mosso?')
                    ->schema([
                        TextInput::make('activity')->label('Attività')->placeholder('Corsa, palestra, camminata…'),
                        TextInput::make('workout_minutes')->label('Per quanto')->numeric()->suffix('minuti')->minValue(0),
                    ])
                    ->columns(2),

                Section::make('Hai mangiato qualcosa da segnare?')
                    ->schema([
                        Select::make('moment')
                            ->label('Quando')
                            ->options(['breakfast' => 'Colazione', 'lunch' => 'Pranzo', 'dinner' => 'Cena', 'snack' => 'Spuntino'])
                            ->native(false),
                        Textarea::make('meal')->label('Cosa')->rows(2)->placeholder('Pasta al pomodoro, insalata'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $d = $this->form->getState();
        $salvato = [];

        if (filled($d['minutes'] ?? null) || filled($d['quality'] ?? null)) {
            SleepLog::updateOrCreate(
                ['night_of' => now()->subDay()->toDateString()],
                array_filter(['minutes' => $d['minutes'] ?? null, 'quality' => $d['quality'] ?? null], fn ($v) => $v !== null),
            );
            $salvato[] = 'sonno';
        }

        if (filled($d['water_litres'] ?? null) || filled($d['nutrition_adherence'] ?? null)) {
            DailyLog::updateOrCreate(
                ['logged_on' => now()->toDateString()],
                array_filter([
                    'water_litres' => $d['water_litres'] ?? null,
                    'nutrition_adherence' => $d['nutrition_adherence'] ?? null,
                ], fn ($v) => $v !== null),
            );
            $salvato[] = 'giornata';
        }

        if (filled($d['weight_kg'] ?? null)) {
            BodyMetric::updateOrCreate(['measured_on' => now()->toDateString()], ['weight_kg' => $d['weight_kg']]);
            $salvato[] = 'peso';
        }

        // L'allenamento si aggiunge, non si sovrascrive: due allenamenti nello
        // stesso giorno sono due allenamenti.
        if (filled($d['activity'] ?? null)) {
            Workout::create(array_filter([
                'performed_on' => now()->toDateString(),
                'activity' => $d['activity'],
                'minutes' => $d['workout_minutes'] ?? null,
            ], fn ($v) => $v !== null));
            $salvato[] = 'allenamento';
        }

        if (filled($d['meal'] ?? null)) {
            Meal::create([
                'kind' => 'eaten',
                'eaten_on' => now()->toDateString(),
                'moment' => $d['moment'] ?? 'lunch',
                'description' => $d['meal'],
            ]);
            $salvato[] = 'pasto';
        }

        if ($salvato === []) {
            Notification::make()->title('Non c\'era niente da salvare')->warning()->send();

            return;
        }

        Notification::make()->title('Salvato: '.implode(', ', $salvato))->success()->send();

        // I campi che si accumulano si svuotano; quelli del giorno restano,
        // perché correggerli è normale e ricompilarli da capo no.
        $this->form->fill([
            'minutes' => $d['minutes'] ?? null,
            'quality' => $d['quality'] ?? null,
            'water_litres' => $d['water_litres'] ?? null,
            'nutrition_adherence' => $d['nutrition_adherence'] ?? null,
            'weight_kg' => $d['weight_kg'] ?? null,
        ]);
    }
}
