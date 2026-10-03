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
 * or email change; this adds the owner's rule (8+ characters with a number
 * and a symbol), blocks reusing the current password or a known default,
 * and records the change in the activity log (never the password itself).
 */
class EditAdminProfile extends EditProfile
{
    /** Install-time / well-known passwords that must never be chosen again. */
    private const BANNED = ['admin@1234', 'admin@123', 'admin1234', 'password@1', 'welcome@123'];

    protected function getPasswordFormComponent(): Component
    {
        // Owner's choice (2026-10-03): 8+ characters with a letter, a number
        // and a symbol — but never the current password or a known default,
        // which would otherwise pass (Admin@1234 meets that rule).
        return parent::getPasswordFormComponent()
            ->rule(Password::min(8)->letters()->numbers()->symbols())
            ->rule(fn () => function (string $attribute, $value, \Closure $fail) {
                if (in_array(mb_strtolower((string) $value), self::BANNED, true)) {
                    $fail('That is a well-known default password. Please choose a different one.');
                } elseif (\Illuminate\Support\Facades\Hash::check((string) $value, (string) $this->getUser()->getAuthPassword())) {
                    $fail('The new password must be different from your current one.');
                }
            })
            ->helperText('At least 8 characters, with a number and a symbol (for example # or !).');
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
