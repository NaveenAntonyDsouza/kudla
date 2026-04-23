<?php

namespace App\Mail;

use App\Models\User;

class Reengagement30DayMail extends DatabaseMailable
{
    protected string $templateSlug = 'reengagement-30day';

    public function __construct(public User $user) {}

    protected function templateVariables(): array
    {
        return [
            'USER_NAME' => $this->user->name,
            'LOGIN_URL' => url('/login'),
            'UNSUBSCRIBE_URL' => $this->user->unsubscribeUrl('email_reengagement'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Last reminder — is your search for a life partner still active?';
    }
}
