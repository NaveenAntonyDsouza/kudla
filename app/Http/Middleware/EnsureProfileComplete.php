<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Admin/moderator users don't need a profile — let them through
        if (in_array($user->role, ['admin', 'moderator', 'support'])) {
            return $next($request);
        }

        $profile = $user->profile;

        // No profile at all — send to registration step 1
        if (! $profile) {
            // Prevent redirect loop: if already on a register route, let through
            $currentRoute = $request->route()?->getName();
            if ($currentRoute && str_starts_with($currentRoute, 'register')) {
                return $next($request);
            }
            return redirect()->route('register');
        }

        // Onboarding not completed — redirect to the appropriate step
        if (! $profile->onboarding_completed) {
            $step = $profile->onboarding_step_completed;
            $currentRoute = $request->route()->getName();

            // Allow access to registration routes (so they don't get stuck in redirect loop)
            if ($currentRoute && (str_starts_with($currentRoute, 'register.') || str_starts_with($currentRoute, 'onboarding.'))) {
                return $next($request);
            }

            // Onboarding Step 5 (/onboarding/photo) renders the full
            // /manage-photos grid via a shared partial, so its forms post
            // directly to the photo action endpoints below. Permit them
            // so an onboarding user can actually upload / archive / set
            // primary before they hit "Continue". We deliberately do NOT
            // allow photos.manage itself — incomplete users still belong
            // on /onboarding/photo, not /manage-photos.
            $onboardingPhotoActions = [
                'photos.upload', 'photos.destroy', 'photos.restore',
                'photos.primary', 'photos.privacy', 'photos.deletePermanent',
            ];
            if (in_array($currentRoute, $onboardingPhotoActions, true)) {
                return $next($request);
            }

            // Redirect to the next incomplete step
            if ($step < 5) {
                return redirect()->route('register.step' . ($step + 1));
            }

            return redirect()->route('register.verifyemail');
        }

        return $next($request);
    }
}
