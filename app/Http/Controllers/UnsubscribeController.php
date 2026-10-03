<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\EmailLogger;
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
    /** Preferences a member can switch off from an email, with their labels. */
    public const PREFERENCES = [
        'email_reengagement' => 'Re-engagement reminders',
        'email_weekly_matches' => 'Weekly match suggestions',
        'email_interest' => 'Interest notifications',
        'email_accepted' => 'Interest accepted notifications',
        'email_declined' => 'Interest declined notifications',
        'email_views' => 'Profile view notifications',
        'email_promotions' => 'Promotional emails',
    ];

    /**
     * Show unsubscribe confirmation + apply the change (1-click).
     *
     * The signature is validated by Laravel's 'signed' middleware on the route.
     */
    public function __invoke(User $user, string $preference, Request $request)
    {
        $allowed = self::PREFERENCES;
        if (!isset($allowed[$preference])) {
            abort(404);
        }

        $this->switchOff($user, $preference);

        return view('unsubscribe.success', [
            'user' => $user,
            'preference' => $preference,
            'preferenceLabel' => $allowed[$preference],
            'allPreferences' => $allowed,
        ]);
    }

    /**
     * One-tap unsubscribe (RFC 8058): Gmail, Yahoo and others POST
     * "List-Unsubscribe=One-Click" to the URL in the List-Unsubscribe header
     * when a member presses their Unsubscribe button. Same signed URL as the
     * link in the email; no login and no CSRF token (exempted in bootstrap/app.php).
     */
    public function oneClick(User $user, string $preference)
    {
        if (!isset(self::PREFERENCES[$preference])) {
            abort(404);
        }

        $this->switchOff($user, $preference);

        return response('Unsubscribed.', 200)->header('Content-Type', 'text/plain');
    }

    private function switchOff(User $user, string $preference): void
    {
        $prefs = $user->notification_preferences ?? [];
        $prefs[$preference] = false;

        $user->notification_preferences = $prefs;
        $user->saveQuietly();

        EmailLogger::unsubscribed($user, $preference);
    }

    /**
     * Re-subscribe (flip preference back to true).
     */
    public function resubscribe(User $user, string $preference, Request $request)
    {
        if (!isset(self::PREFERENCES[$preference])) {
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
