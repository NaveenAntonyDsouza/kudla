<?php

namespace App\Mail;

use App\Models\PhotoRequest;

/**
 * "<MATRI_ID> would like you to add a photo." — the no-photo version of a
 * photo request (the member asked has no photo yet), pointing them to the
 * photo upload page. Matri ID only, like the interest emails.
 */
class PhotoUploadRequestedMail extends DatabaseMailable
{
    protected string $templateSlug = 'photo-upload-requested';

    public function __construct(public PhotoRequest $photoRequest) {}

    protected function templateVariables(): array
    {
        return [
            'RECEIVER_NAME' => $this->photoRequest->targetProfile?->full_name ?? '',
            'REQUESTER_MATRI_ID' => $this->photoRequest->requesterProfile?->matri_id ?? '',
            'ACTION_URL' => route('photos.manage'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'A Member Would Like to See Your Photo - ' . config('app.name');
    }
}
