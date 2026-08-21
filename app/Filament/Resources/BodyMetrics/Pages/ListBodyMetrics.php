<?php

namespace App\Filament\Resources\BodyMetrics\Pages;

use App\Filament\Resources\BodyMetrics\BodyMetricResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBodyMetrics extends ListRecords
{
    protected static string $resource = BodyMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
