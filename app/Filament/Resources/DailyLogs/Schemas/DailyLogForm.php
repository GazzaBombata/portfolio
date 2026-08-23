<?php

namespace App\Filament\Resources\DailyLogs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DailyLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('logged_on')
                ->label('Giorno')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->default(now())
                ->required()
                ->unique(ignoreRecord: true),

            TextInput::make('water_litres')
                ->label('Acqua bevuta')
                ->numeric()
                ->suffix('litri')
                ->step('0.25')
                ->minValue(0)
                ->maxValue(20),

            Select::make('nutrition_adherence')
                ->label('Quanto hai seguito il piano')
                ->options([
                    1 => '1 · per niente', 2 => '2', 3 => '3', 4 => '4',
                    5 => '5 · a metà', 6 => '6', 7 => '7', 8 => '8', 9 => '9',
                    10 => '10 · alla lettera',
                ])
                ->native(false),

            Textarea::make('notes')->label('Note')->rows(3)->columnSpanFull(),
        ])->columns(3);
    }
}
