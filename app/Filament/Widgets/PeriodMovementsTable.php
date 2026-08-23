<?php

namespace App\Filament\Widgets;

use App\Finance\Period;
use App\Finance\Reporting;
use App\Models\Category;
use App\Models\Transaction;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * I movimenti dietro ai numeri qui sopra.
 *
 * È la risposta alla domanda che ogni riquadro fa venire: "e questi 8.000 €
 * di maggio da cosa sono fatti?". Senza, un grafico che mostra un mese strano
 * lascia solo la sensazione che qualcosa non torni, senza modo di guardarci
 * dentro — e quel modo deve stare sulla stessa pagina, con gli stessi filtri
 * già applicati.
 */
class PeriodMovementsTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $periodo = Period::fromFilters($this->pageFilters);

        return $table
            ->heading('Movimenti')
            ->description('Quello che compone i numeri qui sopra, per '.$periodo->label.'.')
            ->query(fn (): Builder => Reporting::realMovements($this->pageFilters)->with(['account', 'category']))
            ->defaultSort('booked_on', 'desc')
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('booked_on')->label('Data')->date('d/m/Y')->sortable(),

                TextColumn::make('description')
                    ->label('Descrizione')
                    ->searchable(['description', 'raw_description', 'counterparty', 'notes'])
                    ->wrap()
                    ->description(fn (Transaction $record): ?string => filled($record->notes)
                        ? Str::limit((string) $record->notes, 80)
                        : null),

                TextColumn::make('account.name')->label('Conto')->badge()->color('gray')->toggleable(),

                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->badge()
                    ->color(fn (Transaction $record): string => $record->category?->kind === 'income' ? 'success' : 'warning')
                    ->placeholder('Da classificare')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Importo')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => Reporting::euro((float) $state))
                    ->color(fn (string $state): string => (float) $state < 0 ? 'danger' : 'success')
                    ->summarize(Sum::make()->label('Totale')
                        ->formatStateUsing(fn (?string $state): string => Reporting::euro((float) $state))),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoria')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload(),

                TernaryFilter::make('uncategorised')
                    ->label('Da classificare')
                    ->placeholder('Tutti')
                    ->trueLabel('Solo da classificare')
                    ->falseLabel('Solo classificati')
                    ->queries(
                        true: fn (Builder $q): Builder => $q->whereNull('category_id'),
                        false: fn (Builder $q): Builder => $q->whereNotNull('category_id'),
                    ),

                TernaryFilter::make('direzione')
                    ->label('Segno')
                    ->placeholder('Entrate e uscite')
                    ->trueLabel('Solo entrate')
                    ->falseLabel('Solo uscite')
                    ->queries(
                        true: fn (Builder $q): Builder => $q->where('amount', '>', 0),
                        false: fn (Builder $q): Builder => $q->where('amount', '<', 0),
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('assegnaCategoria')
                        ->label('Assegna categoria')
                        ->icon('heroicon-m-tag')
                        ->schema([
                            Select::make('category_id')
                                ->label('Categoria')
                                ->options(fn (): array => Category::query()->with('parent')->orderBy('parent_id')->get()
                                    ->mapWithKeys(fn (Category $c): array => [$c->id => $c->fullName()])->all())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(fn (Collection $records, array $data) => $records->each(
                            fn (Transaction $t) => $t->update([
                                'category_id' => $data['category_id'],
                                'category_locked' => true,
                            ])
                        ))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
