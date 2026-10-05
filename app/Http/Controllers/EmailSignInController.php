<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use App\Models\User;
use App\Support\Analytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * One tap from a member email: the signed link (DatabaseMailable::tagLinks,
 * valid SIGN_IN_LINK_DAYS days, for that member only) signs them in and opens
 * the page the email pointed at. Never for staff or admin, never for a
 * blocked account, and never switches away from someone already signed in
 * on this device.
 */
class EmailSignInController extends Controller
{
    public function __invoke(User $user, Request $request)
    {
        $to = $this->safeTarget((string) $request->query('to', '/'));

        if (Auth::check()) {
            return redirect($to); // whoever is signed in here stays signed in
        }

        if ($user->staff_role_id !== null || $user->role === 'admin' || $user->blockedStatus() !== null) {
            return redirect()->route('login');
        }

        Auth::login($user);
        $request->session()->regenerate();
        LoginHistory::record($user, 'email_link');
        Analytics::track('login', ['method' => 'email_link']);

        // Unfinished registration: back to the step where they stopped, as a normal login does
        $profile = $user->profile;
        if ($profile && ! $profile->onboarding_completed) {
            $step = (int) $profile->onboarding_step_completed;

            return redirect()->route($step >= 5 ? 'register.verifyemail' : 'register.step'.max(2, $step + 1));
        }

        return redirect($to);
    }

    /** Only a path on this site: no other host, no protocol-relative "//", no backslashes. */
    private function safeTarget(string $to): string
    {
        if ($to === '' || $to[0] !== '/' || str_starts_with($to, '//') || str_contains($to, '\\')) {
            return '/';
        }

        return $to;
    }
}
