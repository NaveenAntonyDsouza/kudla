<?php

namespace App\Mail;

use App\Models\User;

class MembershipExpiringMail extends DatabaseMailable
{
    protected string $templateSlug = 'membership-expiring';

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
            'ACTION_URL' => url('/membership-plans'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Your Plan Expires Soon - ' . config('app.name');
    }
}
