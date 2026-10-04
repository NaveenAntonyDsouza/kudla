<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\RegistrationService;
use App\Support\Analytics;
use Illuminate\Http\Request;

/**
 * "Confirm my email" from the registration emails. The link only shows a
 * button (mail scanners open every link, so a GET must change nothing); the
 * button POSTs to the same signed URL and verifies the address. A member who
 * had filled in every step and was only waiting on this is registered at once
 * (which sends the welcome email).
 */
class EmailConfirmLinkController extends Controller
{
    public function show(User $user, Request $request)
    {
        return view('email-confirm.confirm', $this->state($user, $request) + ['action' => $request->fullUrl()]);
    }

    public function confirm(User $user, Request $request, RegistrationService $registration)
    {
        $state = $this->state($user, $request);

        if ($state['status'] === 'ready') {
            User::whereKey($user->id)->toBase()->update(['email_verified_at' => now()]);
            $user->refresh();
            Analytics::track('email_verified', ['via' => 'email_link']);

            $profile = $user->profile;
            if ($profile && ! $profile->onboarding_completed && $profile->onboarding_step_completed >= 5) {
                $registration->nextVerificationStep($user); // completes it unless phone verification is also required
                if ($profile->fresh()->onboarding_completed) {
                    Analytics::track('registration_complete', [], 'CompleteRegistration');
                }
            }

            $state = $this->state($user->fresh(), $request);
            $state['status'] = 'confirmed';
        }

        return view('email-confirm.confirm', $state + ['action' => null]);
    }

    /** @return array{status: string, email: ?string, finished: bool, continueUrl: string} */
    private function state(User $user, Request $request): array
    {
        $status = match (true) {
            ! hash_equals(User::emailFingerprint((string) $user->email), (string) $request->query('e')) => 'changed',
            $user->email_verified_at !== null => 'already',
            default => 'ready',
        };

        return [
            'status' => $status,
            'email' => $status === 'changed' ? null : $user->email,
            'finished' => (bool) $user->profile?->onboarding_completed,
            'continueUrl' => url('/login'),
        ];
    }
}
