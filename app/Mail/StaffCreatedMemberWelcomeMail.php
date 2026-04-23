<?php

namespace App\Mail;

use App\Models\User;

class StaffCreatedMemberWelcomeMail extends DatabaseMailable
{
    protected string $templateSlug = 'staff_created_member_welcome';

    public function __construct(public User $user, public string $tempPassword) {}

    protected function templateVariables(): array
    {
        return [
            'USER_NAME' => $this->user->name,
            'USER_EMAIL' => $this->user->email,
            'MATRI_ID' => $this->user->profile?->matri_id ?? '',
            'TEMP_PASSWORD' => $this->tempPassword,
            'LOGIN_URL' => url('/login'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Welcome to ' . config('app.name') . ' — Your Account is Ready';
    }
}
