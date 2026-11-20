<?php

namespace App\Support;

use App\Models\User;

/**
 * The email alerts a member can switch on or off (Settings -> Manage Alerts), in page order.
 *
 * Owner, 20 Nov 2026: every alert is ON until the member switches it off (like Chavara), and
 * the page must show exactly what we send. Saving the page changes only these keys, so an
 * unsubscribe made from an email link (or a push choice made in the app) is never undone.
 */
final class EmailAlerts
{
    /** key => [label, help line] */
    public const LIST = [
        'email_interest' => ['New interests', 'When someone sends you an interest, and reminders about interests waiting for your reply.'],
        'email_accepted' => ['Interest accepted', 'When a member accepts your interest.'],
        'email_declined' => ['Interest declined', 'When a member declines your interest.'],
        'email_photos' => ['Photo requests', 'Photo requests from members, and when a photo you asked for is added or shared with you.'],
        'email_views' => ['Who viewed my profile', 'A weekly summary of members who viewed your profile.'],
        'email_weekly_matches' => ['Weekly matches', 'New members who match your partner preferences, once a week.'],
        'email_reengagement' => ['Reminders', 'Reminders to complete your profile, add a photo, or come back after a break.'],
        'email_promotions' => ['Offers and membership', 'Membership reminders, offers and discounts.'],
    ];

    /**
     * A newer key that took over part of an older one. Until the member sets the newer key,
     * it follows the older one, so nobody who switched off "New interests" (which used to
     * cover the photo emails too) starts getting photo emails.
     */
    public const FOLLOWS = [
        'email_photos' => 'email_interest',
    ];

    /** What the member gets right now for each alert: true unless they switched it off. */
    public static function current(User $user): array
    {
        $out = [];
        foreach (array_keys(self::LIST) as $key) {
            $out[$key] = $user->wantsNotification($key);
        }

        return $out;
    }

    /** The stored preferences with the page's ticks applied; every other key is kept. */
    public static function merged(?array $stored, array $ticked): array
    {
        $prefs = $stored ?? [];
        foreach (array_keys(self::LIST) as $key) {
            $prefs[$key] = in_array($key, $ticked, true);
        }

        return $prefs;
    }
}
