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
     * The link in the email: shows a "Confirm unsubscribe" button and changes
     * nothing. Mail security scanners open every link in a message (HEAD +
     * GETs, seen on kudla 4 Oct 2026 from a .gov.in gateway); unsubscribing on
     * GET let them switch members off. The button POSTs to the same URL.
     *
     * The signature is validated by Laravel's 'signed' middleware on the route.
     */
    public function __invoke(User $user, string $preference, Request $request)
    {
        if (!isset(self::PREFERENCES[$preference])) {
            abort(404);
        }

        return view('unsubscribe.confirm', [
            'preferenceLabel' => self::PREFERENCES[$preference],
            'alreadyOff' => ($user->notification_preferences[$preference] ?? true) === false,
            'action' => $request->fullUrl(),
        ]);
    }

    /**
     * POST to the signed URL, from either:
     *  - the mail app's one-tap Unsubscribe (RFC 8058): Gmail, Yahoo and others
     *    send body "List-Unsubscribe=One-Click" when the member presses their
     *    Unsubscribe button → plain 200;
     *  - the Confirm button on our page → the "you've been unsubscribed" page.
     * No login and no CSRF token (exempted in bootstrap/app.php).
     */
    public function oneClick(User $user, string $preference, Request $request)
    {
        if (!isset(self::PREFERENCES[$preference])) {
            abort(404);
        }

        $this->switchOff($user, $preference);

        if ($request->input('List-Unsubscribe') === 'One-Click') {
            return response('Unsubscribed.', 200)->header('Content-Type', 'text/plain');
        }

        return view('unsubscribe.success', [
            'user' => $user,
            'preference' => $preference,
            'preferenceLabel' => self::PREFERENCES[$preference],
            'allPreferences' => self::PREFERENCES,
        ]);
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
