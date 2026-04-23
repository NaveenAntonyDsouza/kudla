<?php

namespace App\Http\Middleware;

use App\Services\AffiliateTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CaptureAffiliateRef — runs on every public web request.
 *
 * Behavior:
 *   - GET request with ?ref=CODE → log click, set cookie, redirect to clean URL (strip ?ref)
 *   - All other requests → pass through unchanged
 *
 * Why redirect after capture?
 *   - Prevents accidental re-tracking on F5
 *   - Keeps the user's URL clean (better UX)
 *   - Search engines see the clean URL (no duplicate-content issues)
 *
 * The redirect carries the cookie via Cookie::queue(), which Laravel attaches
 * automatically to the response.
 */
class CaptureAffiliateRef
{
    public function __construct(protected AffiliateTracker $tracker)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $ref = $request->query('ref');

        // Only act on GET requests with a ref param
        if ($request->isMethod('GET') && is_string($ref) && trim($ref) !== '') {
            $branch = $this->tracker->captureClick($request, $ref);

            if ($branch) {
                $this->tracker->setCookie($branch->code);

                // Redirect to clean URL (strip ?ref but preserve other query params)
                $cleanQuery = $request->except(['ref']);
                $cleanUrl = $request->url() . (empty($cleanQuery) ? '' : '?' . http_build_query($cleanQuery));

                return redirect($cleanUrl);
            }
            // Unknown code — fall through silently (don't break the page)
        }

        return $next($request);
    }
}
