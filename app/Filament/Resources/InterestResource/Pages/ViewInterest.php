<?php

namespace App\Filament\Resources\InterestResource\Pages;

use App\Filament\Resources\InterestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInterest extends ViewRecord
{
    protected static string $resource = InterestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
