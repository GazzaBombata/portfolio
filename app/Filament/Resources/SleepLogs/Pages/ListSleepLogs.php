<?php

namespace App\Filament\Resources\SleepLogs\Pages;

use App\Filament\Resources\SleepLogs\SleepLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSleepLogs extends ListRecords
{
    protected static string $resource = SleepLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
