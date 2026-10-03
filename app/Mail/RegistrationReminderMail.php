<?php

namespace App\Mail;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\LoginIdentifier;

/**
 * "Finish your registration" reminder for members who started registering
 * but stopped part-way (sent by `members:remind-incomplete`). Admin-editable
 * template `registration-reminder`; switching it off stops it.
 */
class RegistrationReminderMail extends DatabaseMailable
{
    protected string $templateSlug = 'registration-reminder';

    protected ?string $unsubscribePreference = 'email_reengagement';

    protected function recipient(): ?User
    {
        return $this->user;
    }

    public function __construct(public User $user) {}

    protected function templateVariables(): array
    {
        $profile = $this->user->profile;
        $matriId = $profile?->matri_id ?? '';
        $onlyVerificationLeft = ($profile?->onboarding_step_completed ?? 0) >= 5;
        $phone = trim((string) SiteSetting::getValue('phone', ''));

        return [
            // First name reads warmer in a greeting ("Hi Naveen")
            'USER_NAME' => trim(strtok((string) $this->user->name, ' ')) ?: 'there',
            'MATRI_ID' => $matriId,
            'MEMBER_ID_LABEL' => LoginIdentifier::memberIdLabel(),
            'STATUS_LINE' => $onlyVerificationLeft
                ? "Your profile ({$matriId}) is filled in — you just need to verify your email address to activate it."
                : "Your profile ({$matriId}) isn't finished yet, so you can't start browsing matches or sending interests.",
            'HELP_LINE' => $phone !== ''
                ? "Need help? Just reply to this email, or call / WhatsApp us on {$phone}."
                : 'Need help? Just reply to this email.',
            'LOGIN_URL' => url('/login'),
            'FORGOT_URL' => url('/forgot-password'),
            'UNSUBSCRIBE_URL' => $this->user->unsubscribeUrl('email_reengagement'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Your profile is almost ready — finish your registration';
    }
}
