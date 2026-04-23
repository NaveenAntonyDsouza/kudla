<?php

namespace App\Mail;

use App\Models\User;

class Reengagement7DayMail extends DatabaseMailable
{
    protected string $templateSlug = 'reengagement-7day';

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
        return 'We miss you — come back and see new matches';
    }
}
