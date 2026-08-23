<?php

namespace App\Filament\Resources\SleepLogs;

use App\Filament\Resources\SleepLogs\Pages\CreateSleepLog;
use App\Filament\Resources\SleepLogs\Pages\EditSleepLog;
use App\Filament\Resources\SleepLogs\Pages\ListSleepLogs;
use App\Filament\Resources\SleepLogs\Schemas\SleepLogForm;
use App\Filament\Resources\SleepLogs\Tables\SleepLogsTable;
use App\Models\SleepLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SleepLogResource extends Resource
{
    protected static ?string $model = SleepLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMoon;

    protected static string|\UnitEnum|null $navigationGroup = 'Salute';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Sonno';

    protected static ?string $modelLabel = 'notte';

    protected static ?string $pluralModelLabel = 'notti';

    public static function form(Schema $schema): Schema
    {
        return SleepLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SleepLogsTable::configure($table);
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
            'index' => ListSleepLogs::route('/'),
            'create' => CreateSleepLog::route('/create'),
            'edit' => EditSleepLog::route('/{record}/edit'),
        ];
    }
}
