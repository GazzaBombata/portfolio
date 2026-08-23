<?php

namespace App\Filament\Resources\Meals\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MealForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    DatePicker::make('eaten_on')->label('Giorno')->native(false)
                        ->displayFormat('d/m/Y')->default(now())->required(),

                    Select::make('moment')
                        ->label('Quando')
                        ->options([
                            'breakfast' => 'Colazione',
                            'lunch' => 'Pranzo',
                            'dinner' => 'Cena',
                            'snack' => 'Spuntino',
                        ])
                        ->default('lunch')
                        ->native(false)
                        ->required(),

                    TimePicker::make('eaten_at')->label('Ora')->native(false)->seconds(false),

                    Checkbox::make('eaten_out')->label('Fuori casa'),

                    /*
                     * A parole, non a ingredienti pesati. Quello che si smette
                     * di fare in un diario alimentare è pesare e cercare: una
                     * frase scritta è ciò che viene registrato davvero tutti i
                     * giorni.
                     */
                    Textarea::make('description')
                        ->label('Cosa hai mangiato')
                        ->rows(3)
                        ->required()
                        ->columnSpanFull()
                        ->placeholder('Pasta al pomodoro, insalata, una mela'),
                ])
                ->columns(2),

            Section::make('Valori nutrizionali')
                ->description('Opzionali. Se li lasci vuoti, può stimarli l\'assistente.')
                ->schema([
                    TextInput::make('calories')->label('Calorie')->numeric()->suffix('kcal'),
                    TextInput::make('protein_g')->label('Proteine')->numeric()->suffix('g'),
                    TextInput::make('carbs_g')->label('Carboidrati')->numeric()->suffix('g'),
                    TextInput::make('fat_g')->label('Grassi')->numeric()->suffix('g'),
                    Checkbox::make('nutrition_estimated')
                        ->label('Valori stimati, non pesati')
                        // Una stima mostrata come un dato certo diventa un dato
                        // certo nel giro di due giorni.
                        ->helperText('Segnalo quando i numeri sopra sono a occhio.'),
                ])
                ->columns(4)
                ->collapsed(),

            Textarea::make('notes')->label('Note')->rows(2)->columnSpanFull(),
        ]);
    }
}
