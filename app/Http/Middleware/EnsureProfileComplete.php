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

        $profile = $user->profile;

        // No profile at all — send to registration
        if (! $profile) {
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
