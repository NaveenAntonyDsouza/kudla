<?php

namespace App\Mail;

use App\Models\Interest;
use App\Models\User;
use App\Support\MemberName;
use App\Support\MemberSummary;

class InterestReceivedMail extends DatabaseMailable
{
    protected string $templateSlug = 'interest-received';

    protected ?string $unsubscribePreference = 'email_interest';

    protected function recipient(): ?User
    {
        return $this->interest->receiverProfile?->user;
    }

    public function __construct(public Interest $interest) {}

    protected function templateVariables(): array
    {
        $page = route('interests.show', $this->interest);

        return [
            'RECEIVER_NAME' => MemberName::first($this->interest->receiverProfile->full_name) ?: $this->interest->receiverProfile->full_name,
            'SENDER_MATRI_ID' => $this->interest->senderProfile->matri_id,
            'SENDER_SUMMARY' => MemberSummary::line($this->interest->senderProfile),
            // Open the interest page with that reply chosen; the member confirms
            // there. A link alone never answers: mail scanners open links too.
            'ACCEPT_URL' => $page . '?reply=accept#reply',
            'DECLINE_URL' => $page . '?reply=decline#reply',
            'ACTION_URL' => $page,
        ];
    }

    protected function fallbackView(): ?string
    {
        return 'emails.interest-received';
    }

    protected function fallbackSubject(): string
    {
        return 'New Interest Received - ' . config('app.name');
    }

    protected function fallbackData(): array
    {
        return [
            'senderMatriId' => $this->interest->senderProfile->matri_id,
            'receiverName' => $this->interest->receiverProfile->full_name,
            'url' => route('interests.show', $this->interest),
            'siteName' => config('app.name'),
        ];
    }
}
