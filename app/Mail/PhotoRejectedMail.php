<?php

namespace App\Mail;

use App\Models\User;

class PhotoRejectedMail extends DatabaseMailable
{
    protected string $templateSlug = 'photo-rejected';

    public function __construct(public User $user, public string $reason = '') {}

    protected function templateVariables(): array
    {
        return [
            'USER_NAME' => $this->user->name,
            'REASON' => $this->reason,
            // Named route — the photo page lives at /manage-photos; the old
            // hardcoded /profile/photos was a 404.
            'ACTION_URL' => route('photos.manage'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Photo Update Required - ' . config('app.name');
    }
}
