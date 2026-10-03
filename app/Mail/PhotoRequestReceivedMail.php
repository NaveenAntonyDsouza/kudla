<?php

namespace App\Mail;

use App\Models\PhotoRequest;
use App\Models\User;

/**
 * "<MATRI_ID> has requested to see your photos." — to the member whose
 * photos were requested. Shows only the requester's Matri ID (not name),
 * same privacy level as the interest emails.
 */
class PhotoRequestReceivedMail extends DatabaseMailable
{
    protected string $templateSlug = 'photo-request-received';

    protected ?string $unsubscribePreference = 'email_interest';

    protected function recipient(): ?User
    {
        return $this->photoRequest->targetProfile?->user;
    }

    public function __construct(public PhotoRequest $photoRequest) {}

    protected function templateVariables(): array
    {
        return [
            'RECEIVER_NAME' => $this->photoRequest->targetProfile?->full_name ?? '',
            'REQUESTER_MATRI_ID' => $this->photoRequest->requesterProfile?->matri_id ?? '',
            'ACTION_URL' => route('photo-requests.index'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'New Photo Request - ' . config('app.name');
    }
}
