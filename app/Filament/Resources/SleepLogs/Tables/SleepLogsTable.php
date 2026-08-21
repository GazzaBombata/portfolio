<?php

namespace App\Filament\Resources\SleepLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SleepLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Il più recente in cima: è quello che si guarda.
            ->defaultSort('night_of', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('night_of')
                    ->date()
                    ->sortable(),
                TextColumn::make('fell_asleep_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('woke_up_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('quality')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('awakenings')
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
