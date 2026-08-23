<?php

namespace App\Filament\Resources\Workouts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkoutsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Il più recente in cima: è quello che si guarda.
            ->defaultSort('performed_on', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('performed_on')
                    ->date()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->time()
                    ->sortable(),
                TextColumn::make('activity')
                    ->searchable(),
                TextColumn::make('minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('distance_km')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sets')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reps')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('load_kg')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('intensity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('calories')
                    ->numeric()
                    ->sortable(),
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
