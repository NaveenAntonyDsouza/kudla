<?php

namespace App\Mail;

use App\Models\User;
use App\Support\MemberName;

/** "Your plan has ended" — the last of three expiry emails, with a success-story invitation. */
class MembershipExpiredMail extends DatabaseMailable
{
    protected string $templateSlug = 'membership-expired';

    public function __construct(
        public User $user,
        public string $planName,
    ) {}

    protected function templateVariables(): array
    {
        return [
            'USER_NAME' => MemberName::first($this->user->name) ?: 'there',
            'PLAN_NAME' => $this->planName,
            'ACTION_URL' => url('/membership-plans'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return "Your {$this->planName} plan has ended";
    }
}
