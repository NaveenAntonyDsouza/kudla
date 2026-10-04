<?php

namespace App\Mail;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\LoginIdentifier;
use Illuminate\Support\Facades\URL;

/**
 * "Are you still looking?" — to members inactive for 90+ days
 * (members:dormant). Each answer is a signed link to a page with a button
 * (StillLookingController), so a mail scanner opening links changes nothing.
 */
class StillLookingMail extends DatabaseMailable
{
    protected string $templateSlug = 'still-looking';

    protected ?string $unsubscribePreference = 'email_reengagement';

    public function __construct(public User $user) {}

    protected function recipient(): ?User
    {
        return $this->user;
    }

    public static function answerUrl(User $user, string $answer): string
    {
        return URL::temporarySignedRoute('still-looking.show', now()->addDays(60), ['user' => $user->id, 'answer' => $answer]);
    }

    protected function templateVariables(): array
    {
        $phone = trim((string) SiteSetting::getValue('phone', ''));

        return [
            'USER_NAME' => trim(strtok((string) $this->user->name, ' ')) ?: 'there',
            'MATRI_ID' => $this->user->profile?->matri_id ?? '',
            'MEMBER_ID_LABEL' => LoginIdentifier::memberIdLabel(),
            'STILL_LOOKING_URL' => self::answerUrl($this->user, 'looking'),
            'FOUND_MATCH_URL' => self::answerUrl($this->user, 'found'),
            'HIDE_URL' => self::answerUrl($this->user, 'hide'),
            'HELP_LINE' => $phone !== '' ? "Questions? Reply to this email, or call / WhatsApp us on {$phone}." : 'Questions? Just reply to this email.',
            'UNSUBSCRIBE_URL' => $this->user->unsubscribeUrl('email_reengagement'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Are you still looking for your partner?';
    }
}
