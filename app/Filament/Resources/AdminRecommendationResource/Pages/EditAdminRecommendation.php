<?php

namespace App\Filament\Resources\AdminRecommendationResource\Pages;

use App\Filament\Resources\AdminRecommendationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdminRecommendation extends EditRecord
{
    protected static string $resource = AdminRecommendationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
