<?php

namespace App\Filament\Resources\BodyMetrics\Pages;

use App\Filament\Resources\BodyMetrics\BodyMetricResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBodyMetric extends EditRecord
{
    protected static string $resource = BodyMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
