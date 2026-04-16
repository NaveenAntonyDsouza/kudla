<?php

namespace App\Filament\Resources\AdminRecommendationResource\Pages;

use App\Filament\Resources\AdminRecommendationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdminRecommendation extends CreateRecord
{
    protected static string $resource = AdminRecommendationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['admin_user_id'] = auth()->id();

        return $data;
    }
}
