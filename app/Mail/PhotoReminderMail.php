<?php

namespace App\Mail;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\LoginIdentifier;

/**
 * "Add a photo" reminder for members who finished registering but have no
 * photo (sent by `members:remind-photo`). Admin-editable template
 * `photo-reminder`; switching it off stops it.
 */
class PhotoReminderMail extends DatabaseMailable
{
    protected string $templateSlug = 'photo-reminder';

    public function __construct(public User $user) {}

    protected function templateVariables(): array
    {
        $phone = trim((string) SiteSetting::getValue('phone', ''));

        return [
            'USER_NAME' => trim(strtok((string) $this->user->name, ' ')) ?: 'there',
            'MATRI_ID' => $this->user->profile?->matri_id ?? '',
            'MEMBER_ID_LABEL' => LoginIdentifier::memberIdLabel(),
            // Logged-out members land on the login page and come back here after
            'PHOTOS_URL' => route('photos.manage'),
            'HELP_LINE' => $phone !== ''
                ? "Need help adding a photo? Just reply to this email, or call / WhatsApp us on {$phone}."
                : 'Need help adding a photo? Just reply to this email.',
            'FORGOT_URL' => url('/forgot-password'),
            'UNSUBSCRIBE_URL' => $this->user->unsubscribeUrl('email_reengagement'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Add a photo to your profile';
    }
}
