<?php

namespace App\Filament\Resources\StaffResource\Pages;

use App\Filament\Resources\StaffResource;
use App\Models\AdminActivityLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn ($record) => !$record->isSuperAdmin()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Password is dehydrated only when filled, so we re-hash if provided
        if (!empty($data['password'])) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        unset($data['send_credentials']);

        return $data;
    }

    protected function afterSave(): void
    {
        try {
            AdminActivityLog::create([
                'admin_user_id' => auth()->id(),
                'action' => 'staff_updated',
                'model_type' => class_basename($this->record),
                'model_id' => $this->record->id,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            // silently fail
        }
    }
}
