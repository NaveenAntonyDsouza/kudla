<?php

namespace App\Filament\Resources\ContactSubmissionResource\Pages;

use App\Filament\Resources\ContactSubmissionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewContactSubmission extends ViewRecord
{
    protected static string $resource = ContactSubmissionResource::class;

    /** Reply and Close where the message is read, the same as on the list (6 Nov 2026). */
    protected function getHeaderActions(): array
    {
        return [
            ContactSubmissionResource::replyAction(),
            ContactSubmissionResource::closeAction(),
        ];
    }
}
