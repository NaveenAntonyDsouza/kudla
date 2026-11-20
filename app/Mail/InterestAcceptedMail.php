<?php

namespace App\Mail;

use App\Models\Interest;
use App\Models\User;
use App\Support\MemberName;

class InterestAcceptedMail extends DatabaseMailable
{
    protected string $templateSlug = 'interest-accepted';

    protected ?string $unsubscribePreference = 'email_accepted';

    protected function recipient(): ?User
    {
        return $this->interest->senderProfile?->user;
    }

    public function __construct(public Interest $interest) {}

    protected function templateVariables(): array
    {
        return [
            'SENDER_NAME' => $this->interest->senderProfile->full_name,
            'ACCEPTER_MATRI_ID' => $this->interest->receiverProfile->matri_id,   // preview line: ID only
            'ACCEPTER_LABEL' => MemberName::withId($this->interest->receiverProfile),
            'ACTION_URL' => route('interests.show', $this->interest),
        ];
    }

    protected function fallbackView(): ?string
    {
        return 'emails.interest-accepted';
    }

    protected function fallbackSubject(): string
    {
        return 'Interest Accepted! - ' . config('app.name');
    }

    protected function fallbackData(): array
    {
        return [
            'accepterMatriId' => MemberName::withId($this->interest->receiverProfile),
            'senderName' => $this->interest->senderProfile->full_name,
            'url' => route('interests.show', $this->interest),
            'siteName' => config('app.name'),
        ];
    }
}
