<?php

namespace App\Filament\Resources\StaffTargetResource\Pages;

use App\Filament\Resources\StaffTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStaffTargets extends ListRecords
{
    protected static string $resource = StaffTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Staff Target'),
        ];
    }
}
