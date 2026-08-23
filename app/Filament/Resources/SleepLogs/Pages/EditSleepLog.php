<?php

namespace App\Filament\Resources\SleepLogs\Pages;

use App\Filament\Resources\SleepLogs\SleepLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSleepLog extends EditRecord
{
    protected static string $resource = SleepLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
