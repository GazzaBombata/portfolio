<?php

namespace App\Filament\Resources\BodyMetrics\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BodyMetricForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('measured_on')
                ->label('Giorno')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->default(now())
                ->required()
                // Una misurazione al giorno: pesarsi due volte prima di
                // colazione è rumore, non un secondo dato.
                ->unique(ignoreRecord: true),

            TextInput::make('weight_kg')->label('Peso')->numeric()->suffix('kg')->step('0.1'),
            TextInput::make('body_fat_pct')->label('Massa grassa')->numeric()->suffix('%')->step('0.1'),
            TextInput::make('muscle_mass_kg')->label('Massa muscolare')->numeric()->suffix('kg')->step('0.1'),
            TextInput::make('resting_hr')->label('Battito a riposo')->numeric()->suffix('bpm'),

            Textarea::make('notes')->label('Note')->rows(2)->columnSpanFull(),
        ])->columns(2);
    }
}
