<?php

namespace App\Mail;

use App\Models\User;

class ProfileApprovedMail extends DatabaseMailable
{
    protected string $templateSlug = 'profile-approved';

    public function __construct(public User $user) {}

    protected function templateVariables(): array
    {
        return [
            'USER_NAME' => $this->user->name,
            'MATRI_ID' => $this->user->profile?->matri_id ?? '',
            'ACTION_URL' => url('/profile'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Your Profile is Now Live - ' . config('app.name');
    }
}
