<?php

namespace App\Filament\Resources\SleepLogs\Pages;

use App\Filament\Resources\SleepLogs\SleepLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSleepLog extends CreateRecord
{
    protected static string $resource = SleepLogResource::class;
}
