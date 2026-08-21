<?php

namespace App\Filament\Pages;

use App\Models\Account;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * La dashboard con i suoi filtri.
 *
 * Stanno sulla pagina e non dentro ai riquadri perché la domanda è una sola —
 * "in questo periodo, su questi conti" — e va fatta una volta a tutti e
 * quattro insieme. Filtri per riquadro darebbero quattro risposte che sembrano
 * la stessa e non lo sono.
 */
class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Select::make('periodo')
                        ->label('Periodo')
                        ->options([
                            'mese' => 'Questo mese',
                            'scorso' => 'Mese scorso',
                            'trimestre' => 'Ultimi 3 mesi',
                            'anno' => 'Da inizio anno',
                            'tutto' => 'Tutto lo storico',
                            'personalizzato' => 'Da / a…',
                        ])
                        ->default('anno')
                        ->selectablePlaceholder(false)
                        ->live(),

                    // Compaiono solo quando servono: due campi data sempre
                    // visibili accanto a una scelta rapida sembrano un secondo
                    // filtro che la contraddice.
                    DatePicker::make('dal')
                        ->label('Dal')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->visible(fn ($get): bool => $get('periodo') === 'personalizzato'),

                    DatePicker::make('al')
                        ->label('Al')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->visible(fn ($get): bool => $get('periodo') === 'personalizzato'),

                    Select::make('accounts')
                        ->label('Conti')
                        ->multiple()
                        ->options(fn (): array => Account::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->placeholder('Tutti i conti'),
                ])
                ->columns(4),
        ]);
    }
}
