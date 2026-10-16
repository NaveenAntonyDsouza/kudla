<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppInboxService;
use App\Support\Permissions;

/**
 * Shows a photo a member sent on WhatsApp inside the admin inbox. Meta's media
 * links need the access token, so the file is fetched here (once, then kept)
 * and only staff who can open the inbox can see it.
 */
class WhatsAppMediaController extends Controller
{
    public function __invoke(WhatsAppMessage $message)
    {
        abort_unless(Permissions::can('view_member'), 403);

        $media = WhatsAppInboxService::media($message);
        abort_unless($media !== null, 404);

        $mime = strtolower(trim(explode(';', $media['mime'])[0]));
        if (! str_starts_with($mime, 'image/') || $mime === 'image/svg+xml') {
            $mime = 'application/octet-stream'; // never render anything but a plain image inline
        }

        return response($media['body'], 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
        ]);
    }
}
