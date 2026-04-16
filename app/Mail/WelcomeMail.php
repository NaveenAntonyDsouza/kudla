<?php

namespace App\Mail;

use App\Models\User;

class WelcomeMail extends DatabaseMailable
{
    protected string $templateSlug = 'welcome';

    public function __construct(public User $user) {}

    protected function templateVariables(): array
    {
        return [
            'USER_NAME' => $this->user->name,
            'USER_EMAIL' => $this->user->email,
            'MATRI_ID' => $this->user->profile?->matri_id ?? '',
            'ACTION_URL' => url('/dashboard'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Welcome to ' . config('app.name') . '!';
    }
}
