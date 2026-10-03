<?php

namespace App\Filament\Pages\Auth;

use App\Models\AdminActivityLog;
use Filament\Auth\Pages\EditProfile;
use Filament\Schemas\Components\Component;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Password;

/**
 * "My Profile" in the admin panel (avatar menu, top right) — the only place
 * an admin can change their OWN password. Before this existed the panel had
 * no way to do it, so four sites were still on the install-time password.
 *
 * Filament's page already asks for the current password before any password
 * or email change; this adds a stronger rule than Laravel's 8-character
 * default (these accounts can see every member's phone number and photos)
 * and records the change in the activity log (never the password itself).
 */
class EditAdminProfile extends EditProfile
{
    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->rule(Password::min(12)->letters()->mixedCase()->numbers()->symbols())
            ->helperText('At least 12 characters, with upper- and lower-case letters, a number and a symbol.');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $changed = array_values(array_intersect(array_keys($data), ['name', 'email', 'password']));
        $oldEmail = $record->getAttribute('email');

        $record = parent::handleRecordUpdate($record, $data);

        try {
            AdminActivityLog::create([
                'admin_user_id' => $record->getKey(),
                'action' => 'own_profile_updated',
                'model_type' => 'User',
                'model_id' => $record->getKey(),
                'changes' => [
                    'password_changed' => in_array('password', $changed, true),
                    'email' => $oldEmail !== $record->getAttribute('email') ? [$oldEmail, $record->getAttribute('email')] : null,
                ],
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        return $record;
    }
}
