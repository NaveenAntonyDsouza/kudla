<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Models\AdminActivityLog;
use App\Support\Permissions;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => Permissions::can('delete_lead')),
        ];
    }

    protected function afterSave(): void
    {
        try {
            AdminActivityLog::create([
                'admin_user_id' => auth()->id(),
                'action' => 'lead_updated',
                'model_type' => 'Lead',
                'model_id' => $this->record->id,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            // silently fail
        }
    }
}
