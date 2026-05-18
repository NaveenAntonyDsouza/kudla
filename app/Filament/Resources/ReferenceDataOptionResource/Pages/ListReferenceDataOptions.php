<?php

namespace App\Filament\Resources\ReferenceDataOptionResource\Pages;

use App\Filament\Resources\ReferenceDataOptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReferenceDataOptions extends ListRecords
{
    protected static string $resource = ReferenceDataOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Add Option'),
        ];
    }
}
