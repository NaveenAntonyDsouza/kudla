<?php

namespace App\Mail;

use App\Models\PhotoRequest;

/**
 * "<MATRI_ID> has added a photo." — to a member who asked someone without
 * a photo to add one, once that photo is approved and visible to them.
 */
class PhotoAddedMail extends DatabaseMailable
{
    protected string $templateSlug = 'photo-added';

    public function __construct(public PhotoRequest $photoRequest) {}

    protected function templateVariables(): array
    {
        $owner = $this->photoRequest->targetProfile;

        return [
            'REQUESTER_NAME' => $this->photoRequest->requesterProfile?->full_name ?? '',
            'OWNER_MATRI_ID' => $owner?->matri_id ?? '',
            'ACTION_URL' => $owner ? route('profile.view', $owner) : route('dashboard'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'A Member You Asked Has Added a Photo - ' . config('app.name');
    }
}
