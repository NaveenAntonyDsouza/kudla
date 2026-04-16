<?php

namespace App\Mail;

use App\Models\User;

class PhotoRejectedMail extends DatabaseMailable
{
    protected string $templateSlug = 'photo-rejected';

    public function __construct(public User $user, public string $reason = '') {}

    protected function templateVariables(): array
    {
        return [
            'USER_NAME' => $this->user->name,
            'REASON' => $this->reason,
            'ACTION_URL' => url('/profile/photos'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Photo Update Required - ' . config('app.name');
    }
}
