<?php

namespace App\Filament\Resources\AdminRecommendationResource\Pages;

use App\Filament\Resources\AdminRecommendationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdminRecommendations extends ListRecords
{
    protected static string $resource = AdminRecommendationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Recommendation'),
        ];
    }
}
