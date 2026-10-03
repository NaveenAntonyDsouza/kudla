<?php

namespace App\Mail;

use App\Models\User;
use App\Support\MemberName;

/** "Your plan ends tomorrow" — the second of three expiry emails. */
class MembershipEndingTomorrowMail extends DatabaseMailable
{
    protected string $templateSlug = 'membership-expiring-tomorrow';

    public function __construct(
        public User $user,
        public string $planName,
        public string $expiryDate,
    ) {}

    protected function templateVariables(): array
    {
        return [
            'USER_NAME' => MemberName::first($this->user->name) ?: 'there',
            'PLAN_NAME' => $this->planName,
            'EXPIRY_DATE' => $this->expiryDate,
            'ACTION_URL' => url('/membership-plans'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return "Your {$this->planName} plan ends tomorrow";
    }
}
