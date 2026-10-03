<?php

namespace App\Mail;

use App\Models\User;
use App\Support\LoginIdentifier;
use App\Support\MemberName;

class WelcomeMail extends DatabaseMailable
{
    protected string $templateSlug = 'welcome';

    public function __construct(public User $user) {}

    protected function templateVariables(): array
    {
        return [
            'USER_NAME' => MemberName::first($this->user->name) ?: $this->user->name,
            'USER_EMAIL' => $this->user->email,
            'MATRI_ID' => $this->user->profile?->matri_id ?? '',
            // "Matri ID" on matrimony sites, "Member ID" where the site says so
            'MEMBER_ID_LABEL' => LoginIdentifier::memberIdLabel(),
            'ACTION_URL' => url('/dashboard'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Welcome to ' . config('app.name') . '!';
    }
}
