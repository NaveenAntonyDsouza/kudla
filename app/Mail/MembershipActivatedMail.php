<?php

namespace App\Mail;

use App\Models\User;

class MembershipActivatedMail extends DatabaseMailable
{
    protected string $templateSlug = 'membership-activated';

    public function __construct(
        public User $user,
        public string $planName,
        public string $expiryDate,
    ) {}

    protected function templateVariables(): array
    {
        return [
            'USER_NAME' => $this->user->name,
            'PLAN_NAME' => $this->planName,
            'EXPIRY_DATE' => $this->expiryDate,
            'ACTION_URL' => url('/dashboard'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Your ' . $this->planName . ' Plan is Active - ' . config('app.name');
    }
}
