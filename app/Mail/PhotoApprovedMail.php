<?php

namespace App\Mail;

use App\Models\User;

class PhotoApprovedMail extends DatabaseMailable
{
    protected string $templateSlug = 'photo-approved';

    public function __construct(public User $user) {}

    protected function templateVariables(): array
    {
        return [
            'USER_NAME' => $this->user->name,
            'ACTION_URL' => url('/profile'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Your Photo Has Been Approved - ' . config('app.name');
    }
}
