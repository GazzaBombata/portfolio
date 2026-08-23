<?php

namespace App\Filament\Resources\ImportProfiles\Schemas;

use App\Models\Account;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Come leggere l'esportazione di una banca.
 *
 * Tutto quello che qui è un campo, altrove sarebbe stato un parser dedicato per
 * istituto. Cinque banche esportano cinque formati — intestazione alla riga 28,
 * date americane, addebiti e accrediti in colonne separate, spese scritte in
 * positivo — e quando una cambia tracciato si corregge una riga da questa
 * schermata invece di aspettare una modifica al codice.
 */
class ImportProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Di che conto')
                ->schema([
                    Select::make('account_id')
                        ->label('Conto')
                        ->options(fn (): array => Account::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),

                    TextInput::make('name')
                        ->label('Nome del profilo')
                        ->placeholder('ING — carta di credito (csv)')
                        ->required(),
                ])
                ->columns(2),

            Section::make('Dove sta la tabella')
                ->description('Gli estratti mettono intestatario, IBAN e saldi sopra ai movimenti: qui si dice a che riga comincia la tabella vera.')
                ->schema([
                    TextInput::make('header_row')
                        ->label('Riga delle intestazioni')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required()
                        ->helperText('1 se il file comincia subito con i nomi delle colonne.'),

                    TextInput::make('sheet_name')
                        ->label('Foglio')
                        ->placeholder('vuoto = il primo')
                        ->helperText('Solo per i file Excel.'),

                    Select::make('delimiter')
                        ->label('Separatore')
                        ->options([';' => 'punto e virgola  ;', ',' => 'virgola  ,', "\t" => 'tabulazione'])
                        ->default(';')
                        ->helperText('Solo per i CSV.'),
                ])
                ->columns(3),

            Section::make('Come sono scritti i numeri')
                ->schema([
                    Select::make('date_format')
                        ->label('Formato delle date')
                        ->options([
                            'd/m/Y' => 'giorno/mese/anno   (31/12/2026)',
                            'm/d/Y' => 'mese/giorno/anno   (12/31/2026)  — americano',
                            'Y-m-d' => 'anno-mese-giorno   (2026-12-31)',
                            'd-m-Y' => 'giorno-mese-anno   (31-12-2026)',
                            'd.m.Y' => 'giorno.mese.anno   (31.12.2026)',
                        ])
                        ->default('d/m/Y')
                        ->required()
                        // 05/06/2026 è una data valida letta in tutti e due i
                        // versi: si sceglie, non si indovina.
                        ->helperText('Va scelto: 05/06/2026 è valido sia come 5 giugno che come 6 maggio.'),

                    Select::make('decimal_separator')
                        ->label('Separatore decimale')
                        ->options([',' => 'virgola   1.234,56', '.' => 'punto   1,234.56'])
                        ->default(',')
                        ->required(),

                    Select::make('thousands_separator')
                        ->label('Separatore delle migliaia')
                        ->options(['.' => 'punto', ',' => 'virgola', ' ' => 'spazio'])
                        ->placeholder('nessuno')
                        ->helperText('Negli Excel di solito non serve: i numeri sono già numeri.'),

                    Select::make('amount_mode')
                        ->label('Come è scritto l\'importo')
                        ->options([
                            'signed' => 'Una colonna, il meno indica un\'uscita',
                            'inverted' => 'Una colonna, il PIÙ indica una spesa (carte di credito)',
                            'split' => 'Due colonne separate: addebiti e accrediti',
                        ])
                        ->default('signed')
                        ->required()
                        ->live(),
                ])
                ->columns(2),

            Section::make('Quale colonna è cosa')
                ->description('A sinistra il nome del campo, a destra l\'intestazione esatta della colonna nel file.')
                ->schema([
                    KeyValue::make('columns')
                        ->label('')
                        ->keyLabel('Campo')
                        ->valueLabel('Intestazione nel file')
                        ->default([
                            'booked_on' => '',
                            'amount' => '',
                            'description' => '',
                        ])
                        ->helperText(
                            'Campi riconosciuti: booked_on (data, obbligatorio), amount oppure debit e credit '
                            .'(importo), description, valued_on, counterparty, notes.'
                        ),
                ]),
        ]);
    }
}
