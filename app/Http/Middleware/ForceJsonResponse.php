<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prepends Accept: application/json on all /api/* requests.
 *
 * Guarantees Laravel's error + response handling produces JSON even if the
 * client forgot the Accept header. Without this, clients hitting /api/*
 * without the header could receive HTML error pages on edge cases (e.g.,
 * the default 401 redirect flow).
 *
 * Design reference: docs/mobile-app/design/01-api-foundations.md §1.8
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
