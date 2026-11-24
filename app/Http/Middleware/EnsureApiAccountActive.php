<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The app's version of the website's account gate (EnsureProfileComplete / LoginController,
 * 24 Nov 2026): a member who deleted their account, or whom staff suspended, banned or deactivated,
 * gets 403 PROFILE_SUSPENDED on every signed-in request, with the website's message, instead of
 * carrying on with a token issued before. Signing out still works. Staff accounts follow their
 * user row only (User::blockedStatus).
 */
class EnsureApiAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User || $this->mayAlwaysPass($request)) {
            return $next($request);
        }

        if ($blocked = $user->blockedStatus()) {
            return ApiResponse::error('PROFILE_SUSPENDED', User::blockedStatusMessage($blocked), null, 403);
        }

        return $next($request);
    }

    /** Signing out (and taking this phone off push) must work for every account. */
    private function mayAlwaysPass(Request $request): bool
    {
        return ($request->isMethod('POST') && $request->is('api/v1/auth/logout'))
            || ($request->isMethod('DELETE') && $request->is('api/v1/devices/*'));
    }
}
