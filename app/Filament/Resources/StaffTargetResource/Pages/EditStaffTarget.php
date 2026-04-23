<?php

namespace App\Filament\Resources\StaffTargetResource\Pages;

use App\Filament\Resources\StaffTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStaffTarget extends EditRecord
{
    protected static string $resource = StaffTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Convert rupees inputs to paise for storage
        $rupeeRevenueTarget = (int) ($data['revenue_target_rupees'] ?? 0);
        $rupeeIncentivePerReg = (int) ($data['incentive_per_registration_rupees'] ?? 0);

        $data['revenue_target'] = $rupeeRevenueTarget * 100;
        $data['incentive_per_registration'] = $rupeeIncentivePerReg * 100;

        unset($data['revenue_target_rupees'], $data['incentive_per_registration_rupees']);

        return $data;
    }
}
