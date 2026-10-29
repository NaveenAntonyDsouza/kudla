<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff accounts have no member profile, so a staff account signed in on the
 * member site was sent to /register. The admin panel has its own sign-in
 * since 29 Oct 2026, but member-site sign-ins from before that (and the
 * remember-me cookie) still carried the staff account. Such a sign-in is
 * ended for the member site only — the admin panel's own sign-in stays —
 * and the page goes on as for any visitor. A staff account that also has a
 * member profile is left alone.
 */
class SignOutStaffFromMemberSite
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $guard = Auth::guard('web');
            $user = $guard->user();
            if ($user && ($user->staff_role_id !== null || $user->role === 'admin') && ! $user->profile) {
                // not logout(): that would change the shared remember token and
                // end the admin panel's "remember me" too
                $guard->logoutCurrentDevice();
            }
        } catch (\Throwable) {
            // never stop a page over this
        }

        return $next($request);
    }
}
