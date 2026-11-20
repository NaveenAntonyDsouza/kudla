<?php

namespace App\Support;

use App\Models\Profile;
use App\Services\ProfileAccessService;

/**
 * The other member's name next to a notification on the notifications page ("Priya D.", owner
 * 20 Nov 2026). Shown only when the viewer could open that member's profile right now (the same
 * gates as the profile page: deleted, suspended, blocked either way, hidden, "show my profile to"
 * limits), and never put into the notification text itself, which also goes out as phone push.
 */
final class NotificationName
{
    public static function for(?Profile $other, ?Profile $viewer): string
    {
        if (! $other || ! $viewer || $other->id === $viewer->id) {
            return '';
        }
        // remembered on the request, so many notifications from one member cost one check
        $key = "notif_name.{$viewer->id}.{$other->id}";
        $attrs = request()->attributes;
        if (! $attrs->has($key)) {
            $attrs->set($key, app(ProfileAccessService::class)->canAccess($viewer, $other) ? MemberName::short($other->full_name) : '');
        }

        return (string) $attrs->get($key);
    }
}
