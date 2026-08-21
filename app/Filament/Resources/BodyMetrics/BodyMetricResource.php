<?php

namespace App\Filament\Resources\BodyMetrics;

use App\Filament\Resources\BodyMetrics\Pages\CreateBodyMetric;
use App\Filament\Resources\BodyMetrics\Pages\EditBodyMetric;
use App\Filament\Resources\BodyMetrics\Pages\ListBodyMetrics;
use App\Filament\Resources\BodyMetrics\Schemas\BodyMetricForm;
use App\Filament\Resources\BodyMetrics\Tables\BodyMetricsTable;
use App\Models\BodyMetric;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BodyMetricResource extends Resource
{
    protected static ?string $model = BodyMetric::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|\UnitEnum|null $navigationGroup = 'Salute';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Peso e misure';

    protected static ?string $modelLabel = 'misurazione';

    protected static ?string $pluralModelLabel = 'misurazioni';

    public static function form(Schema $schema): Schema
    {
        return BodyMetricForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BodyMetricsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBodyMetrics::route('/'),
            'create' => CreateBodyMetric::route('/create'),
            'edit' => EditBodyMetric::route('/{record}/edit'),
        ];
    }
}
