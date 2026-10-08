<?php

namespace App\Mail;

use App\Models\SiteSetting;
use App\Models\User;

/**
 * Asks a member to choose their community when their caste is a value that
 * isn't one — "Salian" (a bari shared by several communities) or "Konkani"
 * (a language). One-off, sent by `communities:tulu-nadu-update --ask`;
 * admin-editable template `community-update`.
 */
class CommunityUpdateRequestMail extends DatabaseMailable
{
    protected string $templateSlug = 'community-update';

    /** Why this value isn't a community, in plain words. */
    public const REASONS = [
        'Salian' => '"Salian" is a bari (family line) shared by several communities, such as Billava, Mogaveera, Bunt and Kulal, so on its own it doesn\'t tell matches which community you belong to. (We\'re also adding a separate place for your bari.)',
        'Konkani' => '"Konkani" is a language spoken by several communities, such as GSB, RSB, Daivajna and Kharvi, so on its own it doesn\'t tell matches which community you belong to.',
    ];

    public function __construct(public User $user, public string $currentValue) {}

    protected function recipient(): ?User
    {
        return $this->user;
    }

    protected function templateVariables(): array
    {
        $phone = trim((string) SiteSetting::getValue('phone', ''));

        return [
            'USER_NAME' => trim(strtok((string) $this->user->name, ' ')) ?: 'there',
            'MATRI_ID' => $this->user->profile?->matri_id ?? '',
            'CURRENT_VALUE' => $this->currentValue,
            'REASON_LINE' => self::REASONS[$this->currentValue] ?? 'It isn\'t on our list of communities any more.',
            // Opens the Religious Information section of their profile
            'EDIT_URL' => route('profile.show', ['section' => 'religious']),
            'HELP_LINE' => $phone !== ''
                ? "Not sure which to choose? Just reply to this email, or call / WhatsApp us on {$phone}."
                : 'Not sure which to choose? Just reply to this email.',
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Please check your community on your profile';
    }
}
