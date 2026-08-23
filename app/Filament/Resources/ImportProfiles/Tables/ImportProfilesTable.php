<?php

namespace App\Filament\Resources\ImportProfiles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImportProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('account.name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('header_row')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sheet_name')
                    ->searchable(),
                TextColumn::make('delimiter')
                    ->searchable(),
                TextColumn::make('date_format')
                    ->searchable(),
                TextColumn::make('decimal_separator')
                    ->searchable(),
                TextColumn::make('thousands_separator')
                    ->searchable(),
                TextColumn::make('amount_mode')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
