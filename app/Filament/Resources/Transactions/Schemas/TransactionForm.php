<?php

namespace App\Filament\Resources\Transactions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('account_id')
                    ->relationship('account', 'name')
                    ->required(),
                Select::make('category_id')
                    ->relationship('category', 'name'),
                TextInput::make('statement_import_id')
                    ->numeric(),
                DatePicker::make('booked_on')
                    ->required(),
                DatePicker::make('valued_on'),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('currency')
                    ->required()
                    ->default('EUR'),
                Textarea::make('raw_description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('description'),
                TextInput::make('counterparty'),
                TextInput::make('fingerprint')
                    ->required(),
                TextInput::make('occurrence')
                    ->required()
                    ->numeric()
                    ->default(1),
                Toggle::make('category_locked')
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
