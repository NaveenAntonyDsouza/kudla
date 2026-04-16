<?php

namespace App\Mail;

use App\Models\Interest;

class InterestDeclinedMail extends DatabaseMailable
{
    protected string $templateSlug = 'interest-declined';

    public function __construct(public Interest $interest) {}

    protected function templateVariables(): array
    {
        return [
            'SENDER_NAME' => $this->interest->senderProfile->full_name,
            'DECLINER_MATRI_ID' => $this->interest->receiverProfile->matri_id,
            'ACTION_URL' => route('interests.inbox'),
        ];
    }

    protected function fallbackView(): ?string
    {
        return 'emails.interest-declined';
    }

    protected function fallbackSubject(): string
    {
        return 'Interest Update - ' . config('app.name');
    }

    protected function fallbackData(): array
    {
        return [
            'declinerMatriId' => $this->interest->receiverProfile->matri_id,
            'senderName' => $this->interest->senderProfile->full_name,
            'url' => route('interests.inbox'),
            'siteName' => config('app.name'),
        ];
    }
}
