<?php

namespace App\Filament\Resources\Meals\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
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
                    Select::make('kind')
                        ->label('Che cos\'è')
                        ->options(['eaten' => 'Un pasto che ho mangiato', 'planned' => 'Un pasto previsto dal piano'])
                        ->default('eaten')
                        ->native(false)
                        ->required()
                        ->helperText('Solo i pasti mangiati entrano nel conto delle calorie assunte.'),

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

            /*
             * Gli ingredienti stanno dentro il pasto, come gli esercizi dentro
             * una seduta. Finché il pasto aveva un numero solo, quel numero lo
             * decideva il modello guardando una frase e non c'era niente da
             * controllare: una cifra sola non ha parti. Con le righe si vede
             * dove sbaglia — un pranzo da 640 kcal con dentro tre cucchiai
             * d'olio che da soli ne fanno 270.
             */
            Section::make('Ingredienti')
                ->description('Se li elenchi, calorie e macro del pasto diventano la loro somma e non si scrivono più a mano.')
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->label('')
                        ->addActionLabel('Aggiungi un ingrediente')
                        ->orderColumn('position')
                        ->defaultItems(0)
                        ->schema([
                            TextInput::make('name')->label('Ingrediente')->required()->placeholder('Olio extravergine'),
                            // La quantità è testo: «3 cucchiai» è come si mangia
                            // davvero, e convertirlo in grammi per poterlo
                            // scrivere vorrebbe dire inventare una precisione.
                            TextInput::make('quantity')->label('Quanto')->placeholder('3 cucchiai · 200 g · mezza pizza'),
                            TextInput::make('calories')->label('Calorie')->numeric()->suffix('kcal'),
                            TextInput::make('protein_g')->label('Proteine')->numeric()->suffix('g'),
                            TextInput::make('carbs_g')->label('Carboidrati')->numeric()->suffix('g'),
                            TextInput::make('fat_g')->label('Grassi')->numeric()->suffix('g'),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ])
                ->collapsed(),

            Section::make('Valori nutrizionali')
                ->description('Opzionali, e da riempire solo se NON hai elencato gli ingredienti: con quelli il totale è la loro somma, e questi campi vengono soprascritti.')
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
