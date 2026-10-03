<?php

namespace App\Mail;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\MemberName;

/**
 * "Your plan is one step away" — sent once when a member started a payment
 * and didn't finish it. Follows their "Promotions" email setting.
 */
class PaymentReminderMail extends DatabaseMailable
{
    protected string $templateSlug = 'payment-reminder';

    protected ?string $unsubscribePreference = 'email_promotions';

    public function __construct(public User $user, public string $planName) {}

    protected function recipient(): ?User
    {
        return $this->user;
    }

    protected function templateVariables(): array
    {
        $phone = trim((string) SiteSetting::getValue('phone', ''));

        return [
            'USER_NAME' => MemberName::first($this->user->name) ?: 'there',
            'PLAN_NAME' => $this->planName,
            'PLANS_URL' => route('membership.index'),
            'HELP_LINE' => $phone !== '' ? "You can also call / WhatsApp us on {$phone}." : '',
            'UNSUBSCRIBE_URL' => $this->user->unsubscribeUrl('email_promotions'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return "Complete your {$this->planName} membership";
    }
}
