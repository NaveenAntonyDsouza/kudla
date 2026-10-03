<?php

namespace App\Mail;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\MemberName;

/**
 * "Your password was changed" — a security alert after any password change
 * (settings page, app, or reset). Not unsubscribable: a member whose account
 * was taken over must still hear about it.
 */
class PasswordChangedMail extends DatabaseMailable
{
    protected string $templateSlug = 'password-changed';

    public function __construct(public User $user, public string $changedAt) {}

    protected function templateVariables(): array
    {
        $phone = trim((string) SiteSetting::getValue('phone', ''));

        return [
            'USER_NAME' => MemberName::first($this->user->name) ?: 'there',
            'CHANGED_AT' => $this->changedAt,
            'FORGOT_URL' => url('/forgot-password'),
            'HELP_LINE' => $phone !== ''
                ? "Need help? Reply to this email, or call / WhatsApp us on {$phone}."
                : 'Need help? Reply to this email.',
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Your password was changed';
    }
}
