<?php

namespace App\Services;

use App\Models\AffiliateClick;
use App\Models\Branch;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;

/**
 * AffiliateTracker — orchestrates the affiliate click → registration → conversion funnel.
 *
 * Flow:
 *   1. Visitor arrives with ?ref=MNG → middleware calls captureClick()
 *      - Logs an AffiliateClick row
 *      - Sets a 60-day cookie 'aff_branch_code=MNG'
 *   2. Visitor browses without ?ref= → cookie remembers their attribution
 *   3. Visitor registers → RegisterController calls attributeRegistration()
 *      - Reads cookie, finds matching branch, stamps user.branch_id
 *      - Updates the AffiliateClick row with registered_user_id + registered_at
 *   4. Visitor pays → Subscription model event calls markConversion()
 *      - Sets converted_at on the relevant AffiliateClick row(s)
 */
class AffiliateTracker
{
    /** Cookie name that stores the branch code (e.g., "MNG") */
    public const COOKIE_NAME = 'aff_branch_code';

    /** Cookie lifetime — matrimony is high-consideration, 60 days is a balance */
    public const COOKIE_DAYS = 60;

    /**
     * Capture a click that arrived via ?ref=CODE.
     * Returns the Branch if the code is valid, otherwise null.
     */
    public function captureClick(Request $request, string $code): ?Branch
    {
        $branch = Branch::active()->where('code', strtoupper($code))->first();

        if (!$branch) {
            return null; // unknown code — silently ignore
        }

        // Log the click (best-effort — never throw on logging)
        try {
            AffiliateClick::create([
                'branch_id' => $branch->id,
                'ip_hash' => $this->hashIp($request->ip()),
                'user_agent_hash' => $this->hashUserAgent($request->userAgent()),
                'referrer_url' => $this->truncate($request->headers->get('referer'), 500),
                'landing_page' => $this->truncate($request->fullUrl(), 500),
                'utm_source' => $this->truncate($request->query('utm_source'), 100),
                'utm_medium' => $this->truncate($request->query('utm_medium'), 100),
                'utm_campaign' => $this->truncate($request->query('utm_campaign'), 100),
                'visited_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Don't break the page if logging fails
            report($e);
        }

        return $branch;
    }

    /**
     * Queue the affiliate cookie for the response. Lifetime: 60 days.
     */
    public function setCookie(string $code): void
    {
        Cookie::queue(
            self::COOKIE_NAME,
            strtoupper($code),
            self::COOKIE_DAYS * 24 * 60, // minutes
            null,  // path (default /)
            null,  // domain
            null,  // secure (auto-detect)
            true,  // httpOnly — JS can't read
            false, // raw
            'lax'  // SameSite — allow first-party visits from external sites (essential for affiliate flow)
        );
    }

    /**
     * Read the branch code from the visitor's cookie (or null if unset).
     */
    public function getCookieCode(Request $request): ?string
    {
        $code = $request->cookie(self::COOKIE_NAME);
        return $code ? strtoupper($code) : null;
    }

    /**
     * Get the Branch the visitor is attributed to (via cookie). Null if no cookie or unknown branch.
     */
    public function getAttributedBranch(Request $request): ?Branch
    {
        $code = $this->getCookieCode($request);
        if (!$code) {
            return null;
        }
        return Branch::active()->where('code', $code)->first();
    }

    /**
     * Called when a User registers — attributes them to their affiliate branch and
     * updates the most recent matching click row.
     */
    public function attributeRegistration(Request $request, User $user): void
    {
        $branch = $this->getAttributedBranch($request);
        if (!$branch) {
            return; // no affiliate cookie — nothing to do
        }

        // Stamp the branch on the user (only if not already set)
        if ($user->branch_id === null) {
            $user->branch_id = $branch->id;
            $user->saveQuietly(); // skip events to avoid loop
        }

        // Find the most recent unconverted click from this visitor and link it
        $click = AffiliateClick::where('branch_id', $branch->id)
            ->where('ip_hash', $this->hashIp($request->ip()))
            ->whereNull('registered_user_id')
            ->latest('visited_at')
            ->first();

        if ($click) {
            $click->registered_user_id = $user->id;
            $click->registered_at = now();
            $click->save();
        } else {
            // No matching click row (cookie persisted but click was old/expired) — log a synthetic one
            AffiliateClick::create([
                'branch_id' => $branch->id,
                'ip_hash' => $this->hashIp($request->ip()),
                'user_agent_hash' => $this->hashUserAgent($request->userAgent()),
                'landing_page' => '(synthetic — cookie-based attribution at registration)',
                'visited_at' => now(),
                'registered_user_id' => $user->id,
                'registered_at' => now(),
            ]);
        }

        // Optional: clear the cookie so re-registrations don't re-attribute
        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }

    /**
     * Called when a Subscription is marked paid — sets converted_at on the matching click.
     * (Hooked into Subscription model events in Stage B.)
     */
    public function markConversion(Subscription $subscription): void
    {
        if ($subscription->payment_status !== 'paid' || !$subscription->user_id) {
            return;
        }

        // Find the click that registered this user; only set converted_at if not already set
        AffiliateClick::where('registered_user_id', $subscription->user_id)
            ->whereNull('converted_at')
            ->update(['converted_at' => now()]);
    }

    /* ------------------------------------------------------------------
     |  Hashing helpers (privacy)
     | ------------------------------------------------------------------ */

    public function hashIp(?string $ip): ?string
    {
        if (!$ip) return null;
        return hash('sha256', $ip . config('app.key'));
    }

    public function hashUserAgent(?string $ua): ?string
    {
        if (!$ua) return null;
        return hash('sha256', $ua . config('app.key'));
    }

    private function truncate(?string $value, int $length): ?string
    {
        if ($value === null) return null;
        return mb_substr($value, 0, $length);
    }
}
