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
            if ($currentRoute && str_starts_with($currentRoute, 'register.')) {
                return $next($request);
            }

            // Redirect to the next incomplete step
            $nextStep = min($step + 1, 5);
            if ($nextStep <= 4) {
                return redirect()->route('register.step'.$nextStep);
            }

            return redirect()->route('register.verify');
        }

        return $next($request);
    }
}
