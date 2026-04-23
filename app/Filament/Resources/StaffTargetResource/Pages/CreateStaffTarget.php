<?php

namespace App\Filament\Resources\StaffTargetResource\Pages;

use App\Filament\Resources\StaffTargetResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateStaffTarget extends CreateRecord
{
    protected static string $resource = StaffTargetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Convert rupees inputs to paise for storage
        $rupeeRevenueTarget = (int) ($data['revenue_target_rupees'] ?? 0);
        $rupeeIncentivePerReg = (int) ($data['incentive_per_registration_rupees'] ?? 0);

        $data['revenue_target'] = $rupeeRevenueTarget * 100;
        $data['incentive_per_registration'] = $rupeeIncentivePerReg * 100;

        unset($data['revenue_target_rupees'], $data['incentive_per_registration_rupees']);

        // Prevent duplicates
        $exists = \App\Models\StaffTarget::where('staff_user_id', $data['staff_user_id'])
            ->whereDate('month', $data['month'])
            ->exists();

        if ($exists) {
            Notification::make()
                ->title('A target already exists for this staff member and month.')
                ->danger()
                ->send();
            $this->halt();
        }

        return $data;
    }
}
