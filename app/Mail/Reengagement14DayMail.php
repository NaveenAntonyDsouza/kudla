<?php

namespace App\Mail;

use App\Models\User;

class Reengagement14DayMail extends DatabaseMailable
{
    protected string $templateSlug = 'reengagement-14day';

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
        return 'Someone might be waiting to meet you';
    }
}
