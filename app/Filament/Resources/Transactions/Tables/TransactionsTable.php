<?php

namespace App\Filament\Resources\Transactions\Tables;

use App\Models\Category;
use App\Models\Transaction;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('booked_on', 'desc')
            ->columns([
                TextColumn::make('booked_on')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Descrizione')
                    ->searchable(['description', 'raw_description', 'counterparty'])
                    ->wrap()
                    // Quello che ha scritto la banca, sotto la versione pulita:
                    // serve quando la pulizia ha tolto qualcosa che serviva.
                    ->description(fn (Transaction $record): ?string => $record->raw_description !== $record->description
                        ? Str::limit($record->raw_description, 90)
                        : null),

                TextColumn::make('account.name')
                    ->label('Conto')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->badge()
                    ->color(fn (Transaction $record): string => match ($record->category?->kind) {
                        'income' => 'success',
                        'transfer' => 'info',
                        'expense' => 'warning',
                        default => 'gray',
                    })
                    // Non "vuoto": è un lavoro da fare, e va detto come tale.
                    ->placeholder('Da classificare')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Importo')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => number_format((float) $state, 2, ',', '.').' €')
                    ->color(fn (string $state): string => (float) $state < 0 ? 'danger' : 'success')
                    ->weight('medium')
                    /*
                     * Il totale di quello che si sta guardando.
                     *
                     * È il motivo per cui i filtri esistono: "quanto ho speso di
                     * spesa a giugno" è una domanda a cui si risponde filtrando e
                     * leggendo qui sotto, senza costruire un report apposta.
                     */
                    ->summarize(
                        Sum::make()
                            ->label('Totale')
                            ->formatStateUsing(fn (?string $state): string => number_format((float) $state, 2, ',', '.').' €')
                    ),
            ])
            ->filters([
                SelectFilter::make('account')
                    ->label('Conto')
                    ->relationship('account', 'name')
                    ->multiple()
                    ->preload(),

                SelectFilter::make('category')
                    ->label('Categoria')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload(),

                TernaryFilter::make('uncategorised')
                    ->label('Da classificare')
                    ->placeholder('Tutte')
                    ->trueLabel('Solo da classificare')
                    ->falseLabel('Solo già classificate')
                    ->queries(
                        true: fn (Builder $q): Builder => $q->whereNull('category_id'),
                        false: fn (Builder $q): Builder => $q->whereNotNull('category_id'),
                    ),

                /*
                 * I giroconti falsano ogni totale: le spese di una carta sono già
                 * contate una per una, e il pagamento dell'estratto le conterebbe
                 * una seconda volta. Di norma stanno fuori, e si guardano solo
                 * quando si vuole guardare proprio quelli.
                 */
                TernaryFilter::make('transfers')
                    ->label('Giroconti')
                    ->placeholder('Escludi giroconti')
                    ->trueLabel('Solo giroconti')
                    ->falseLabel('Tutto, giroconti inclusi')
                    ->queries(
                        true: fn (Builder $q): Builder => $q->whereHas('category', fn (Builder $c) => $c->where('kind', 'transfer')),
                        false: fn (Builder $q): Builder => $q,
                        blank: fn (Builder $q): Builder => $q->whereDoesntHave('category', fn (Builder $c) => $c->where('kind', 'transfer')),
                    ),

                Filter::make('periodo')
                    ->schema([
                        DatePicker::make('dal')->label('Dal')->native(false),
                        DatePicker::make('al')->label('Al')->native(false),
                    ])
                    ->query(fn (Builder $q, array $data): Builder => $q
                        ->when($data['dal'] ?? null, fn (Builder $q, $d) => $q->whereDate('booked_on', '>=', $d))
                        ->when($data['al'] ?? null, fn (Builder $q, $d) => $q->whereDate('booked_on', '<=', $d))),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    /*
                     * Classificare a mano è il lavoro più noioso qui dentro:
                     * dopo un import ci sono centinaia di righe, e molte sono lo
                     * stesso esercente. Selezionarle e assegnarle in un colpo è
                     * quello che rende la cosa fattibile.
                     */
                    BulkAction::make('assegnaCategoria')
                        ->label('Assegna categoria')
                        ->icon('heroicon-m-tag')
                        ->schema([
                            Select::make('category_id')
                                ->label('Categoria')
                                ->options(fn (): array => Category::query()
                                    ->with('parent')
                                    ->orderBy('parent_id')
                                    ->get()
                                    ->mapWithKeys(fn (Category $c): array => [$c->id => $c->fullName()])
                                    ->all())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(fn (Transaction $t) => $t->update([
                                'category_id' => $data['category_id'],
                                // Scelta da una persona: la classificazione
                                // automatica non la tocca più.
                                'category_locked' => true,
                            ]));
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
