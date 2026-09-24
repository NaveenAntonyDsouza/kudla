<?php

namespace App\Mail;

use App\Models\PhotoRequest;

/**
 * "<MATRI_ID> approved your photo request." — to the member who asked,
 * linking to the approver's profile where the photos are now visible.
 */
class PhotoRequestApprovedMail extends DatabaseMailable
{
    protected string $templateSlug = 'photo-request-approved';

    public function __construct(public PhotoRequest $photoRequest) {}

    protected function templateVariables(): array
    {
        $approver = $this->photoRequest->targetProfile;

        return [
            'REQUESTER_NAME' => $this->photoRequest->requesterProfile?->full_name ?? '',
            'APPROVER_MATRI_ID' => $approver?->matri_id ?? '',
            'ACTION_URL' => $approver ? route('profile.view', $approver) : route('dashboard'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Your Photo Request Was Approved - ' . config('app.name');
    }
}
