<?php

namespace App\Filament\Resources\Meals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MealsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Il più recente in cima: è quello che si guarda.
            ->defaultSort('eaten_on', 'desc')
            ->columns([
                TextColumn::make('kind')
                    ->label('')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'planned' ? 'previsto' : 'mangiato')
                    ->color(fn (string $state): string => $state === 'planned' ? 'gray' : 'success'),
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('eaten_on')
                    ->date()
                    ->sortable(),
                TextColumn::make('moment')
                    ->badge(),
                TextColumn::make('eaten_at')
                    ->time()
                    ->sortable(),
                TextColumn::make('calories')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('protein_g')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('carbs_g')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('fat_g')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('nutrition_estimated')
                    ->boolean(),
                IconColumn::make('eaten_out')
                    ->boolean(),
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
                    ->options(['eaten' => 'Mangiati', 'planned' => 'Previsti dal piano'])
                    // Di norma si guarda cosa si è mangiato: il piano si
                    // chiede, non si subisce mescolato al resto.
                    ->default('eaten'),
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
