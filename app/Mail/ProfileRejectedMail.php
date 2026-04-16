<?php

namespace App\Mail;

use App\Models\User;

class ProfileRejectedMail extends DatabaseMailable
{
    protected string $templateSlug = 'profile-rejected';

    public function __construct(public User $user, public string $reason = '') {}

    protected function templateVariables(): array
    {
        return [
            'USER_NAME' => $this->user->name,
            'REASON' => $this->reason,
            'ACTION_URL' => url('/profile'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Profile Update Required - ' . config('app.name');
    }
}
