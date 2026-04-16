<?php

namespace App\Mail;

use App\Models\Interest;

class InterestReceivedMail extends DatabaseMailable
{
    protected string $templateSlug = 'interest-received';

    public function __construct(public Interest $interest) {}

    protected function templateVariables(): array
    {
        return [
            'RECEIVER_NAME' => $this->interest->receiverProfile->full_name,
            'SENDER_MATRI_ID' => $this->interest->senderProfile->matri_id,
            'ACTION_URL' => route('interests.show', $this->interest),
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
