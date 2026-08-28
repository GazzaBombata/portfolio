<?php

namespace App\Filament\Resources\Workouts\Schemas;

use App\Models\Workout;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WorkoutForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Select::make('kind')
                        ->label('Che seduta è')
                        ->options(['done' => 'Fatta', 'planned' => 'In programma'])
                        ->default('done')
                        ->native(false)
                        ->required()
                        // Una seduta in programma non ha bruciato niente: se
                        // contasse, il bilancio di giovedì annuncerebbe un
                        // margine guadagnato con un allenamento non ancora
                        // fatto.
                        ->helperText('Quelle in programma non contano calorie finché non le segni fatte.'),

                    DatePicker::make('performed_on')
                        ->label('Giorno')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->default(now())
                        ->required(),

                    /*
                     * Scelta libera con i suggerimenti di quello che hai già
                     * fatto: l'elenco delle attività di una persona cambia più
                     * in fretta di una migrazione, e una nuova dev'essere
                     * registrabile la prima volta che la fai.
                     */
                    Select::make('activity')
                        ->label('Attività')
                        ->options(fn (): array => Workout::query()
                            ->select('activity')
                            ->distinct()
                            ->orderBy('activity')
                            ->pluck('activity', 'activity')
                            ->all())
                        ->searchable()
                        ->allowHtml(false)
                        ->createOptionForm([TextInput::make('activity')->label('Nome')->required()])
                        ->createOptionUsing(fn (array $data): string => $data['activity'])
                        ->required(),

                    TimePicker::make('started_at')->label('Ora di inizio')->native(false)->seconds(false),

                    TextInput::make('minutes')->label('Durata')->numeric()->suffix('minuti')->minValue(0),

                    Select::make('intensity')
                        ->label('Intensità')
                        ->options([1 => '1 · leggera', 2 => '2 · moderata', 3 => '3 · sostenuta', 4 => '4 · dura', 5 => '5 · massimale'])
                        ->native(false),
                ])
                ->columns(2),

            Section::make('Numeri, se ci sono')
                ->description('Riempi solo quelli che hanno senso per questa attività.')
                ->schema([
                    TextInput::make('distance_km')->label('Distanza')->numeric()->suffix('km')->step('0.01'),
                    TextInput::make('calories')->label('Calorie')->numeric()->suffix('kcal')
                        ->helperText('Solo se le hai lette da un cardiofrequenzimetro: se le lasci vuote, vengono stimate.'),
                ])
                ->columns(2)
                ->collapsed(),

            /*
             * Gli esercizi stanno dentro la seduta, non accanto: serie,
             * ripetizioni e carico erano tre colonne sulla seduta, cioè un
             * posto solo per una palestra che di esercizi ne ha cinque.
             */
            Section::make('Esercizi')
                ->description('Uno per riga, nell\'ordine in cui li hai fatti. La progressione dei carichi si legge da qui.')
                ->schema([
                    Repeater::make('exercises')
                        ->hiddenLabel()
                        ->relationship()
                        ->orderColumn('position')
                        ->addActionLabel('Aggiungi esercizio')
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                        ->collapsible()
                        ->defaultItems(0)
                        ->schema([
                            TextInput::make('name')->label('Esercizio')->required()->columnSpan(2),
                            TextInput::make('sets')->label('Serie')->numeric()->minValue(0),
                            TextInput::make('reps')->label('Ripetizioni')->numeric()->minValue(0),
                            TextInput::make('load_kg')->label('Carico')->numeric()->suffix('kg')->step('0.5')
                                ->helperText('Vuoto a corpo libero.'),
                            TextInput::make('seconds')->label('Secondi')->numeric()->minValue(0)
                                ->helperText('Per plank e simili.'),
                        ])
                        ->columns(2),
                ]),

            Textarea::make('notes')->label('Note')->rows(2)->columnSpanFull(),
        ]);
    }
}
