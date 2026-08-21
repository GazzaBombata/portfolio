<?php

namespace App\Filament\Resources\SleepLogs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SleepLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('night_of')
                ->label('Notte del')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->default(now()->subDay())
                ->required()
                // Una notte per riga: registrarla due volte corregge la prima
                // invece di creare una seconda notte che non esiste.
                ->unique(ignoreRecord: true)
                ->helperText('La sera in cui sei andato a dormire, anche se ti sei svegliato il giorno dopo.'),

            TextInput::make('minutes')
                ->label('Quanto hai dormito')
                ->numeric()
                ->suffix('minuti')
                ->minValue(0)
                ->maxValue(1440)
                ->helperText('Se non sai gli orari esatti, basta questo.'),

            DateTimePicker::make('fell_asleep_at')->label('Addormentato alle')->native(false)->seconds(false),
            DateTimePicker::make('woke_up_at')->label('Sveglio alle')->native(false)->seconds(false),

            Select::make('quality')
                ->label('Com\'è andata')
                ->options([
                    1 => '1 · pessima',
                    2 => '2 · scarsa',
                    3 => '3 · normale',
                    4 => '4 · buona',
                    5 => '5 · ottima',
                ])
                ->native(false),

            TextInput::make('awakenings')->label('Risvegli')->numeric()->minValue(0)->maxValue(50),

            Textarea::make('notes')->label('Note')->rows(2)->columnSpanFull(),
        ])->columns(2);
    }
}
