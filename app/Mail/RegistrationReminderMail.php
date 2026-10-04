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

    /** The first reminder within this many days of signing up is the "thank you for joining" email. */
    public const STARTED_WITHIN_DAYS = 3;

    /** @param bool $first the member's first reminder (sent an hour after they stopped) */
    public function __construct(public User $user, bool $first = false)
    {
        if ($first && $user->created_at?->gt(now()->subDays(self::STARTED_WITHIN_DAYS))) {
            $this->templateSlug = 'registration-started';
        }
    }

    protected function templateVariables(): array
    {
        $profile = $this->user->profile;
        $matriId = $profile?->matri_id ?? '';
        $onlyVerificationLeft = ($profile?->onboarding_step_completed ?? 0) >= 5;
        $phone = trim((string) SiteSetting::getValue('phone', ''));
        $started = $this->templateSlug === 'registration-started';

        return [
            // First name reads warmer in a greeting ("Hi Naveen")
            'USER_NAME' => trim(strtok((string) $this->user->name, ' ')) ?: 'there',
            'MATRI_ID' => $matriId,
            'MEMBER_ID_LABEL' => LoginIdentifier::memberIdLabel(),
            'STATUS_LINE' => match (true) {
                $started && $onlyVerificationLeft => 'Your profile is filled in — just confirm your email address below and it goes live.',
                $started => "A few more details and your profile is ready to be seen. It takes about 10 minutes, and you'll continue right where you left off.",
                $onlyVerificationLeft => "Your profile ({$matriId}) is filled in — you just need to verify your email address to activate it.",
                default => "Your profile ({$matriId}) isn't finished yet, so you can't start browsing matches or sending interests.",
            },
            'CONFIRM_EMAIL_HTML' => $this->confirmEmailHtml(),
            'HELP_LINE' => $phone !== ''
                ? "Need help? Just reply to this email, or call / WhatsApp us on {$phone}."
                : 'Need help? Just reply to this email.',
            'LOGIN_URL' => url('/login'),
            'FORGOT_URL' => url('/forgot-password'),
            'UNSUBSCRIBE_URL' => $this->user->unsubscribeUrl('email_reengagement'),
        ];
    }

    /** "Confirm my email" button, only while the address isn't verified yet. */
    private function confirmEmailHtml(): string
    {
        if ($this->user->email_verified_at !== null) {
            return '';
        }

        $color = e($this->getPrimaryColor());

        return '<p style="text-align:center;margin:0 0 2rem;color:#374151;">Please also confirm this is your email address, so you don\'t miss messages from members who are interested in you.<br>'
            .'<a href="'.e($this->user->confirmEmailUrl()).'" style="display:inline-block;margin-top:12px;padding:10px 28px;border:2px solid '.$color.';color:'.$color.';text-decoration:none;border-radius:6px;font-weight:600;">Confirm my email</a></p>';
    }

    protected function fallbackSubject(): string
    {
        return 'Your profile is almost ready — finish your registration';
    }
}
