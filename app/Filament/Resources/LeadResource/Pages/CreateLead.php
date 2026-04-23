<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Models\AdminActivityLog;
use Filament\Resources\Pages\CreateRecord;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-set the creator
        $data['created_by_staff_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        try {
            AdminActivityLog::create([
                'admin_user_id' => auth()->id(),
                'action' => 'lead_created',
                'model_type' => 'Lead',
                'model_id' => $this->record->id,
                'changes' => ['full_name' => $this->record->full_name, 'phone' => $this->record->phone],
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            // silently fail
        }
    }
}
