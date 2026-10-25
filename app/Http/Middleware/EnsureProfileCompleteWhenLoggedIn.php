<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * For pages visitors and members share (the search pages, Oct 2026): visitors
 * pass straight through; a logged-in member gets the same checks as the
 * members' area (blocked accounts signed out, unfinished registrations sent
 * back to their step) — EnsureProfileComplete.
 */
class EnsureProfileCompleteWhenLoggedIn
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        return app(EnsureProfileComplete::class)->handle($request, $next);
    }
}
