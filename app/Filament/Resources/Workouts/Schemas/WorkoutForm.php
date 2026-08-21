<?php

namespace App\Filament\Resources\Workouts\Schemas;

use App\Models\Workout;
use Filament\Forms\Components\DatePicker;
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
                    TextInput::make('sets')->label('Serie')->numeric()->minValue(0),
                    TextInput::make('reps')->label('Ripetizioni')->numeric()->minValue(0),
                    TextInput::make('load_kg')->label('Carico')->numeric()->suffix('kg')->step('0.5'),
                    TextInput::make('calories')->label('Calorie')->numeric()->suffix('kcal'),
                ])
                ->columns(3)
                ->collapsed(),

            Textarea::make('notes')->label('Note')->rows(2)->columnSpanFull(),
        ]);
    }
}
