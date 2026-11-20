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
     * WhatsApp messages a member can switch off (owner 20 Nov 2026). Only messages we start belong
     * here: sign-in codes the member asks for, and our replies to a member who wrote to us, always go.
     */
    public const WHATSAPP = [
        'whatsapp_photos' => ['Photo reminders', 'A message from our team asking you to add a profile photo.'],
    ];

    /**
     * App notifications (owner 20 Nov 2026): the groups NotificationService::pushPrefKey really
     * sends under. "Interest accepted" always comes through (HIGH_PRIORITY_TYPES), so it is not
     * a tick. Shown only to members whose phone is linked (the Kudla app, see RegisterAppDevice).
     */
    public const PUSH = [
        'push_interest' => ['Interests', 'New interests and messages, interests declined or expired. Accepted interests always come through.'],
        'push_photos' => ['Photos', 'Photo requests from members, when a photo you asked for is added or shared with you, and when your photos are approved.'],
        'push_account' => ['Profile and membership', 'Membership reminders, reminders to complete your profile, ID proof and document checks, and messages from our team.'],
    ];

    /** Every key the Manage Alerts page can show (email, WhatsApp, then app). */
    public static function keys(): array
    {
        return array_merge(array_keys(self::LIST), array_keys(self::WHATSAPP), array_keys(self::PUSH));
    }

    /**
     * A newer key that took over part of an older one. Until the member sets the newer key,
     * it follows the older one, so nobody who switched off "New interests" (which used to
     * cover the photo emails too) starts getting photo emails. Until 20 Nov 2026 every push
     * followed push_interest, hence the two push lines.
     */
    public const FOLLOWS = [
        'email_photos' => 'email_interest',
        'push_photos' => 'push_interest',
        'push_account' => 'push_interest',
    ];

    /** What the member gets right now for each alert: true unless they switched it off. */
    public static function current(User $user): array
    {
        $out = [];
        foreach (self::keys() as $key) {
            $out[$key] = $user->wantsNotification($key);
        }

        return $out;
    }

    /**
     * The stored preferences with the page's ticks applied; every other key is kept.
     * $shown = the keys the page showed (default all), so a section the member did not see
     * (App notifications without a linked phone) is never switched off by saving.
     */
    public static function merged(?array $stored, array $ticked, ?array $shown = null): array
    {
        $prefs = $stored ?? [];
        foreach ($shown ?? self::keys() as $key) {
            $prefs[$key] = in_array($key, $ticked, true);
        }

        return $prefs;
    }
}
