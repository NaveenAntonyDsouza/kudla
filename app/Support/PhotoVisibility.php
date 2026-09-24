<?php

namespace App\Support;

use App\Models\Interest;
use App\Models\PhotoPrivacySetting;
use App\Models\PhotoRequest;
use App\Models\Profile;

/**
 * The ONE answer to "may this viewer see this member's profile photo?".
 *
 * Every place that shows another member's photo — search cards, profile
 * page, homepage, interests, notifications, views, photo requests, print —
 * must go through here. Before this existed each view read the privacy
 * setting its own way (several not at all, one reading a column the privacy
 * form never writes), so photos members had hidden were shown anyway.
 *
 * Rules (viewer = the logged-in member's profile, null for guests):
 *   - own photo                → always visible
 *   - no approved primary photo → NO_PHOTO
 *   - 'visible_to_all'         → visible
 *   - 'hidden'                 → only if the owner approved this viewer's photo request
 *   - 'interest_accepted'      → only if an interest between them was accepted
 * Guests never pass the hidden / interest_accepted gates.
 *
 * When a photo isn't visible, callers get null from url() and must render a
 * placeholder — never the real image blurred with CSS, which still hands
 * the photo to anyone who opens the image address.
 */
class PhotoVisibility
{
    public const VISIBLE = 'visible';
    public const NO_PHOTO = 'no_photo';
    public const HIDDEN = 'hidden';
    public const AFTER_ACCEPTANCE = 'after_acceptance';

    public static function state(Profile $owner, ?Profile $viewer = null): string
    {
        $photo = $owner->primaryPhoto;
        if (! $photo) {
            return self::NO_PHOTO;
        }

        if ($viewer && $viewer->id === $owner->id) {
            return self::VISIBLE;
        }

        $level = $owner->photoPrivacySetting?->levelForType('profile') ?? PhotoPrivacySetting::LEVEL_VISIBLE_TO_ALL;

        return match ($level) {
            PhotoPrivacySetting::LEVEL_HIDDEN => $viewer && in_array($owner->id, self::approvedRequestTargets($viewer->id), true)
                ? self::VISIBLE
                : self::HIDDEN,
            PhotoPrivacySetting::LEVEL_INTEREST_ACCEPTED => $viewer && in_array($owner->id, self::acceptedPartners($viewer->id), true)
                ? self::VISIBLE
                : self::AFTER_ACCEPTANCE,
            default => self::VISIBLE,
        };
    }

    /** The photo URL if the viewer may see it, otherwise null (render a placeholder). */
    public static function url(Profile $owner, ?Profile $viewer = null): ?string
    {
        return self::state($owner, $viewer) === self::VISIBLE ? $owner->primaryPhoto->full_url : null;
    }

    /** Shortcut for Blade: the current logged-in member as viewer. */
    public static function urlForCurrentViewer(?Profile $owner): ?string
    {
        return $owner ? self::url($owner, auth()->user()?->profile) : null;
    }

    public static function stateForCurrentViewer(Profile $owner): string
    {
        return self::state($owner, auth()->user()?->profile);
    }

    /**
     * Profiles whose photo request from $viewerId was approved. Memoised
     * per request with once(), so a page of 20 cards costs one query.
     *
     * @return list<int>
     */
    private static function approvedRequestTargets(int $viewerId): array
    {
        return once(fn () => PhotoRequest::where('requester_profile_id', $viewerId)
            ->where('status', 'approved')
            ->pluck('target_profile_id')
            ->map(fn ($id) => (int) $id)
            ->all());
    }

    /**
     * Profiles with an accepted interest with $viewerId, either direction.
     *
     * @return list<int>
     */
    private static function acceptedPartners(int $viewerId): array
    {
        return once(fn () => Interest::where('status', 'accepted')
            ->where(fn ($q) => $q->where('sender_profile_id', $viewerId)->orWhere('receiver_profile_id', $viewerId))
            ->get(['sender_profile_id', 'receiver_profile_id'])
            ->map(fn ($i) => (int) ((int) $i->sender_profile_id === $viewerId ? $i->receiver_profile_id : $i->sender_profile_id))
            ->all());
    }
}
