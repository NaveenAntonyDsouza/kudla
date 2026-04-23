<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * UnsubscribeController — one-click unsubscribe from specific notification types.
 *
 * URL: /unsubscribe/{user}/{preference} — signed by Laravel's signed URL
 * Example: /unsubscribe/42/email_reengagement?signature=...
 *
 * Security:
 *   - Laravel's signed route middleware validates the signature
 *   - If signature is invalid/expired, user sees an error page (not the unsubscribe action)
 *   - If valid, we flip that specific preference to false and show a thank-you page
 *
 * We deliberately do NOT log the user in — unsubscribe should work even if their
 * session is expired, and doesn't need authentication.
 */
class UnsubscribeController extends Controller
{
    /**
     * Show unsubscribe confirmation + apply the change (1-click).
     *
     * The signature is validated by Laravel's 'signed' middleware on the route.
     */
    public function __invoke(User $user, string $preference, Request $request)
    {
        // Whitelist of unsubscribable preferences
        $allowed = [
            'email_reengagement' => 'Re-engagement reminders',
            'email_weekly_matches' => 'Weekly match suggestions',
            'email_interest' => 'Interest notifications',
            'email_accepted' => 'Interest accepted notifications',
            'email_declined' => 'Interest declined notifications',
            'email_views' => 'Profile view notifications',
            'email_promotions' => 'Promotional emails',
        ];

        if (!isset($allowed[$preference])) {
            abort(404);
        }

        // Apply the unsubscribe — set preference to false
        $prefs = $user->notification_preferences ?? [];
        $prefs[$preference] = false;

        $user->notification_preferences = $prefs;
        $user->saveQuietly();

        return view('unsubscribe.success', [
            'user' => $user,
            'preference' => $preference,
            'preferenceLabel' => $allowed[$preference],
            'allPreferences' => $allowed,
        ]);
    }

    /**
     * Re-subscribe (flip preference back to true).
     */
    public function resubscribe(User $user, string $preference, Request $request)
    {
        $allowed = [
            'email_reengagement', 'email_weekly_matches',
            'email_interest', 'email_accepted',
            'email_declined', 'email_views', 'email_promotions',
        ];
        if (!in_array($preference, $allowed, true)) {
            abort(404);
        }

        $prefs = $user->notification_preferences ?? [];
        $prefs[$preference] = true;

        $user->notification_preferences = $prefs;
        $user->saveQuietly();

        return view('unsubscribe.resubscribed', [
            'user' => $user,
            'preference' => $preference,
        ]);
    }
}
