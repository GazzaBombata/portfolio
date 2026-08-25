<?php

namespace App\Filament\Pages;

use App\Health\Energy;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * I dati che servono per calcolare il fabbisogno calorico.
 *
 * Sono pochi e cambiano di rado, ma senza di loro il conto delle calorie non
 * si fa — e l'assistente lo dice invece di inventarne uno.
 */
class Profile extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|\UnitEnum|null $navigationGroup = 'Salute';

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'Il tuo profilo';

    protected static ?string $title = 'Il tuo profilo';

    protected string $view = 'filament.pages.profile';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill(Auth::user()->only([
            'birth_date', 'height_cm', 'sex', 'activity_factor', 'health_notes',
        ]));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Chi sei')
                    ->description('Servono al calcolo del metabolismo basale. Il peso no: quello viene letto dall\'ultima misurazione.')
                    ->schema([
                        DatePicker::make('birth_date')
                            ->label('Data di nascita')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->maxDate(now()->subYears(10))
                            // La data e non l'età: un'età scritta a mano è
                            // giusta oggi e sbagliata da domani.
                            ->helperText('Da qui si ricava l\'età, che resta corretta anche fra dieci anni.'),

                        TextInput::make('height_cm')
                            ->label('Altezza')
                            ->numeric()
                            ->suffix('cm')
                            ->minValue(100)
                            ->maxValue(250),

                        Select::make('sex')
                            ->label('Sesso biologico')
                            ->options(['male' => 'Maschile', 'female' => 'Femminile'])
                            ->native(false)
                            ->helperText('La formula del metabolismo basale usa coefficienti diversi.'),

                        Select::make('activity_factor')
                            ->label('Quanto ti muovi in una giornata normale')
                            ->options([
                                '1.20' => 'Sedentario — lavoro da scrivania',
                                '1.35' => 'Poco attivo — qualche spostamento a piedi',
                                '1.50' => 'Attivo — in piedi buona parte del giorno',
                                '1.70' => 'Molto attivo — lavoro fisico',
                            ])
                            ->native(false)
                            // Gli allenamenti non stanno qui: si sommano a
                            // parte da quelli registrati, così una settimana
                            // ferma e una di corse non danno lo stesso numero.
                            ->helperText('Senza contare gli allenamenti: quelli vengono sommati a parte, giorno per giorno.'),
                    ])
                    ->columns(2),

                Section::make('Da tenere presente')
                    ->schema([
                        Textarea::make('health_notes')
                            ->label('Note di salute')
                            ->rows(3)
                            ->placeholder('Nessuna patologia nota.')
                            ->helperText('Testo libero, lo legge anche l\'assistente. Non è una cartella clinica.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        Auth::user()->update($this->form->getState());

        $basale = Energy::basalRate(Auth::user()->fresh());

        Notification::make()
            ->title('Profilo salvato')
            ->body($basale !== null
                ? "Metabolismo basale stimato: {$basale} kcal al giorno."
                : 'Per il calcolo delle calorie manca ancora qualcosa: controlla che ci siano data di nascita, altezza, sesso e almeno una misurazione del peso.')
            ->success()
            ->send();
    }

    /** Quello che il calcolo produce adesso, per vedere subito se ha senso. */
    public function getStimaProperty(): ?array
    {
        $user = Auth::user();
        $basale = Energy::basalRate($user);

        if ($basale === null) {
            return null;
        }

        return [
            'eta' => Energy::age($user),
            'basale' => $basale,
            'fabbisogno' => Energy::dailyNeed($user, CarbonImmutable::now()),
            'attivita' => Energy::activityBurn($user, CarbonImmutable::now()),
        ];
    }
}
