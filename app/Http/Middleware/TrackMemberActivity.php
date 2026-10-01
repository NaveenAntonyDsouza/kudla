<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * TrackMemberActivity — keeps users.last_login_at meaning "last active".
 *
 * last_login_at used to be written only when a member typed a password or
 * OTP. OTP logins set a remember-me cookie, and registration logs the member
 * in without a "login", so members could use the site for weeks while their
 * last_login_at stayed old or empty. Re-engagement ("we miss you"), weekly
 * matches, nudges and the "Active 2 hours ago" labels all read this column,
 * so active members looked inactive.
 *
 * Now any request by a signed-in member (web session or API token) refreshes
 * it, at most once per THROTTLE_MINUTES, and resets reengagement_level like
 * a login does. Query-builder update: no model events, no updated_at change.
 * Never throws — activity tracking must not break a page.
 */
class TrackMemberActivity
{
    public const THROTTLE_MINUTES = 15;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            // Only a user the request already resolved (auth middleware ran,
            // or the page asked for the user) — never an extra auth lookup.
            $guard = Auth::guard();
            if (! $guard->hasUser()) {
                return $response;
            }
            $user = $guard->user();

            // Members only — staff/admin "Last Login" keeps its meaning.
            if ($user->staff_role_id !== null || $user->role === 'admin') {
                return $response;
            }

            $last = $user->last_login_at;
            if ($last && $last->greaterThan(now()->subMinutes(self::THROTTLE_MINUTES))) {
                return $response;
            }

            DB::table('users')->where('id', $user->id)->update([
                'last_login_at' => now(),
                'reengagement_level' => 0,
            ]);
            $user->last_login_at = now();
            $user->reengagement_level = 0;
            $user->syncOriginalAttributes(['last_login_at', 'reengagement_level']);
        } catch (\Throwable $e) {
            report($e);
        }

        return $response;
    }
}
