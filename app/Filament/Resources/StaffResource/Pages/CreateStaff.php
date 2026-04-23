<?php

namespace App\Filament\Resources\StaffResource\Pages;

use App\Filament\Resources\StaffResource;
use App\Mail\StaffCreatedMemberWelcomeMail;
use App\Models\AdminActivityLog;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    protected string $tempPassword = '';
    protected bool $sendCredentials = true;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Stash "send credentials" flag (it's not a DB column)
        $this->sendCredentials = (bool) ($data['send_credentials'] ?? true);
        unset($data['send_credentials']);

        // Auto-generate password if blank
        if (empty($data['password'])) {
            $this->tempPassword = Str::random(12);
            $data['password'] = Hash::make($this->tempPassword);
        } else {
            $this->tempPassword = $data['password'];
            $data['password'] = Hash::make($data['password']);
        }

        // Set default role enum (backward compat)
        $data['role'] = $data['role'] ?? 'user';
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var User $staff */
        $staff = $this->record;

        // Send welcome email with credentials
        if ($this->sendCredentials) {
            try {
                Mail::to($staff->email)->send(new StaffCreatedMemberWelcomeMail($staff, $this->tempPassword));
            } catch (\Throwable $e) {
                // Silently fail — notification will still show password
            }
        }

        // Log activity
        try {
            AdminActivityLog::create([
                'admin_user_id' => auth()->id(),
                'action' => 'staff_created',
                'model_type' => class_basename($staff),
                'model_id' => $staff->id,
                'changes' => ['role' => $staff->staffRole?->slug, 'email' => $staff->email],
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            // silently fail
        }

        // Show notification with temp password (persistent so admin can copy)
        Notification::make()
            ->title('Staff member created successfully')
            ->body("Temporary password: {$this->tempPassword}\n(Copy now — it won't be shown again.)")
            ->success()
            ->persistent()
            ->send();
    }
}
