<?php

namespace App\Filament\Resources\ReferenceDataOptionResource\Pages;

use App\Filament\Resources\ReferenceDataOptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReferenceDataOption extends EditRecord
{
    protected static string $resource = ReferenceDataOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
