<?php

namespace App\Filament\Resources\Workouts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
                TextColumn::make('kind')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'planned' ? 'In programma' : 'Fatta')
                    ->color(fn (string $state): string => $state === 'planned' ? 'warning' : 'success'),
                TextColumn::make('authored_by')
                    ->label('Scritta da')
                    ->formatStateUsing(fn (string $state): string => $state === 'assistant' ? 'Consulente' : 'Giorgio')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                TextColumn::make('exercises_count')
                    ->label('Esercizi')
                    ->counts('exercises')
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
                SelectFilter::make('kind')
                    ->label('Tipo')
                    ->options(['done' => 'Fatte', 'planned' => 'In programma']),
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
