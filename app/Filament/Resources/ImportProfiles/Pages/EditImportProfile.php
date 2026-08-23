<?php

namespace App\Filament\Resources\ImportProfiles\Pages;

use App\Filament\Resources\ImportProfiles\ImportProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditImportProfile extends EditRecord
{
    protected static string $resource = ImportProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
