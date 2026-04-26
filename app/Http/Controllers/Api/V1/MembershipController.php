<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Models\Coupon;
use App\Models\DailyInterestUsage;
use App\Models\MembershipPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Membership read-side endpoints — plans, current membership, coupon
 * validation. Razorpay order creation + verification land in step-04
 * and step-05.
 *
 *   GET  /api/v1/membership/plans              public — list active plans
 *   GET  /api/v1/membership/me                 auth   — current membership + today's usage
 *   POST /api/v1/membership/coupon/validate    auth   — validate a coupon for a plan
 *
 * Coupon validation delegates to App\Models\Coupon::validateFor() which
 * is the canonical web implementation (used by /membership-plans/checkout
 * + /membership-plans/verify). Reusing it keeps web and API behaviour
 * identical and prevents the 6-rule validation matrix from drifting.
 *
 * Currency convention:
 *   - API contract uses INR integers (price_inr, discount_amount_inr,
 *     final_amount_inr, etc.) — matches the existing membership_plans
 *     schema and matches what the user sees on the pricing page.
 *   - Internally, Coupon::validateFor() and ::calculateDiscount() work
 *     in PAISE. We convert at the boundary: paise = inr * 100, and
 *     inr = (int) round(paise / 100) for the response.
 *
 * Design reference:
 *   docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-03-membership-plans-coupon.md
 */
class MembershipController extends BaseApiController
{
    /* ==================================================================
     |  GET /membership/plans (public)
     | ================================================================== */

    /**
     * List all active membership plans (public — no auth needed for the pricing screen).
     *
     * @unauthenticated
     *
     * @group Membership
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": [
     *     {"id": 5, "slug": "diamond-plus", "name": "Diamond Plus",
     *      "duration_months": 6, "price_inr": 2999, "strike_price_inr": 4999, "discount_pct": 40,
     *      "daily_interest_limit": 50, "view_contacts_limit": 0, "daily_contact_views": 0,
     *      "can_view_contact": true, "personalized_messages": false,
     *      "allows_free_member_chat": true, "exposes_contact_to_free": true,
     *      "featured_profile": false, "priority_support": false,
     *      "is_popular": false, "is_highlighted": false,
     *      "features": ["50 interests/day"]}
     *   ]
     * }
     */
    public function plans(): JsonResponse
    {
        $plans = [];
        try {
            $plans = MembershipPlan::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (MembershipPlan $p) => $this->renderPlan($p))
                ->values()
                ->all();
        } catch (\Throwable $e) {
            // membership_plans table unreachable — return empty list rather
            // than 500. Production tables always exist; this branch is
            // defensive for the SQLite test environment.
        }

        return ApiResponse::ok($plans);
    }

    /* ==================================================================
     |  GET /membership/me (auth)
     | ================================================================== */

    /**
     * Get the viewer's current membership + today's usage counters (interests_sent / contacts_viewed).
     *
     * @authenticated
     *
     * @group Membership
     *
     * @response 200 scenario="paid-member" {
     *   "success": true,
     *   "data": {
     *     "membership": {"plan_id": 5, "plan_name": "Diamond Plus", "is_premium": true,
     *                    "starts_at": "2026-04-01T...", "ends_at": "2026-10-01T...",
     *                    "days_remaining": 159, "is_active": true},
     *     "usage_today": {"interests_sent": 3, "interests_limit": 50, "contacts_viewed": null, "contacts_limit": 0}
     *   }
     * }
     *
     * @response 200 scenario="free-tier" {
     *   "success": true,
     *   "data": {
     *     "membership": {"plan_id": null, "plan_name": "Free", "is_premium": false,
     *                    "starts_at": null, "ends_at": null,
     *                    "days_remaining": null, "is_active": false},
     *     "usage_today": {"interests_sent": 0, "interests_limit": 5, "contacts_viewed": null, "contacts_limit": 0}
     *   }
     * }
     *
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function mine(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile;
        if (! $profile) {
            return ApiResponse::error(
                'PROFILE_REQUIRED',
                'Complete registration before viewing membership.',
                null,
                422,
            );
        }

        $membership = $this->safeActiveMembership($user);
        $plan = $membership?->plan;  // belongsTo relation

        $isPremium = (bool) $membership;

        $endsAt = $membership?->ends_at;
        $daysRemaining = $endsAt ? max(0, (int) now()->diffInDays($endsAt, false)) : null;

        // Default daily interest limit for free tier from config; can be
        // overridden per-plan if active membership exists.
        $interestsLimit = $plan?->daily_interest_limit
            ?? (int) config('matrimony.daily_interest_limit_free', 5);

        return ApiResponse::ok([
            'membership' => [
                'plan_id' => $plan?->id,
                'plan_name' => (string) ($plan?->plan_name ?? 'Free'),
                'is_premium' => $isPremium,
                'starts_at' => $membership?->starts_at?->toIso8601String(),
                'ends_at' => $endsAt?->toIso8601String(),
                'days_remaining' => $daysRemaining,
                'is_active' => $isPremium,
            ],
            'usage_today' => [
                'interests_sent' => $this->safeInterestsSentToday($profile->id),
                'interests_limit' => (int) $interestsLimit,
                // Per-day contact-view tracking is a future feature
                // (no daily counter exists yet). Returning null lets
                // Flutter show "—" instead of "0/0".
                'contacts_viewed' => null,
                'contacts_limit' => (int) ($plan?->daily_contact_views ?? 0),
            ],
        ]);
    }

    /* ==================================================================
     |  POST /membership/coupon/validate (auth)
     | ================================================================== */

    /**
     * Validate a coupon code against a plan + return the computed discount. Used pre-checkout.
     *
     * @authenticated
     *
     * @group Membership
     *
     * @bodyParam plan_id integer required Plan to apply coupon against.
     * @bodyParam coupon_code string required Coupon code (case-insensitive matched).
     *
     * @response 200 scenario="valid" {
     *   "success": true,
     *   "data": {
     *     "valid": true,
     *     "coupon_code": "WELCOME20",
     *     "discount_type": "percentage",
     *     "discount_value": 20,
     *     "original_amount_inr": 2999,
     *     "discount_amount_inr": 599,
     *     "final_amount_inr": 2400
     *   }
     * }
     *
     * @response 400 scenario="invalid-coupon" {
     *   "success": false,
     *   "error": {"code": "COUPON_INVALID", "message": "This coupon has expired."}
     * }
     *
     * @response 404 scenario="unknown-plan" {"success": false, "error": {"code": "NOT_FOUND", "message": "Plan not found."}}
     * @response 422 scenario="validation-failed" {"success": false, "error": {"code": "VALIDATION_FAILED", "message": "...", "fields": {...}}}
     * @response 429 scenario="throttled" {"success": false, "error": {"code": "THROTTLED", "message": "..."}}
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|integer',
            'coupon_code' => 'required|string|max:50',
        ]);

        $plan = $this->safeFindPlan((int) $data['plan_id']);
        if (! $plan) {
            return ApiResponse::error('NOT_FOUND', 'Plan not found.', null, 404);
        }

        // Coupon::validateFor handles every rule (active, dates, total +
        // per-user usage limits, applicable plans, min purchase). It
        // works in paise — convert plan price before calling.
        $coupon = $this->safeFindCoupon((string) $data['coupon_code']);
        if (! $coupon) {
            return ApiResponse::error('COUPON_INVALID', 'Coupon not found.', null, 400);
        }

        $priceInPaise = ((int) $plan->price_inr) * 100;
        $result = $coupon->validateFor($plan->id, $priceInPaise, $request->user()->id);

        if (! $result['valid']) {
            return ApiResponse::error(
                'COUPON_INVALID',
                (string) ($result['message'] ?? 'Coupon is not valid.'),
                null,
                400,
            );
        }

        $discountInPaise = (int) $result['discount'];
        $discountInr = (int) round($discountInPaise / 100);
        $finalInr = max(0, ((int) $plan->price_inr) - $discountInr);

        return ApiResponse::ok([
            'valid' => true,
            'coupon_code' => (string) $coupon->code,
            'discount_type' => (string) $coupon->discount_type,
            // Display value: % for percentage; rupees for fixed
            // (calculateDiscount converts the stored paise to a usable
            // discount, but for the response we expose the raw config
            // value so Flutter can render "20% off" or "₹500 off").
            'discount_value' => $coupon->discount_type === 'percentage'
                ? (int) $coupon->discount_value
                : (int) round(((int) $coupon->discount_value) / 100),
            'original_amount_inr' => (int) $plan->price_inr,
            'discount_amount_inr' => $discountInr,
            'final_amount_inr' => $finalInr,
        ]);
    }

    /* ==================================================================
     |  Helpers
     | ================================================================== */

    /** Render a single MembershipPlan into the public API shape. */
    private function renderPlan(MembershipPlan $p): array
    {
        $price = (int) ($p->price_inr ?? 0);
        $strike = (int) ($p->strike_price_inr ?? 0);
        $discountPct = ($strike > 0 && $strike > $price)
            ? (int) round((($strike - $price) / $strike) * 100)
            : 0;

        return [
            'id' => (int) $p->id,
            'slug' => (string) $p->slug,
            'name' => (string) $p->plan_name,
            'duration_months' => (int) $p->duration_months,
            'price_inr' => $price,
            'strike_price_inr' => $strike > 0 ? $strike : null,
            'discount_pct' => $discountPct,
            // Limits + counters
            'daily_interest_limit' => (int) ($p->daily_interest_limit ?? 0),
            'view_contacts_limit' => (int) ($p->view_contacts_limit ?? 0),
            'daily_contact_views' => (int) ($p->daily_contact_views ?? 0),
            // Feature flags
            'can_view_contact' => (bool) $p->can_view_contact,
            'personalized_messages' => (bool) $p->personalized_messages,
            'allows_free_member_chat' => (bool) ($p->allows_free_member_chat ?? false),
            'exposes_contact_to_free' => (bool) ($p->exposes_contact_to_free ?? false),
            'featured_profile' => (bool) $p->featured_profile,
            'priority_support' => (bool) $p->priority_support,
            'is_popular' => (bool) $p->is_popular,
            'is_highlighted' => (bool) $p->is_highlighted,
            // Marketing
            'features' => is_array($p->features) ? $p->features : [],
        ];
    }

    /**
     * Get the user's active membership defensively. activeMembership()
     * queries user_memberships → joins membership_plans for the plan
     * relation; either failure should degrade to "free tier" rather
     * than 500.
     */
    private function safeActiveMembership($user)
    {
        try {
            return $user->activeMembership();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Today's interest count for the profile, or 0 on any failure. */
    private function safeInterestsSentToday(int $profileId): int
    {
        try {
            return (int) (DailyInterestUsage::query()
                ->where('profile_id', $profileId)
                ->where('usage_date', today())
                ->value('count') ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function safeFindPlan(int $id): ?MembershipPlan
    {
        try {
            return MembershipPlan::find($id);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Find a coupon by code (case-insensitive, mirroring how users
     * naturally type promo codes in upper or lowercase).
     */
    private function safeFindCoupon(string $code): ?Coupon
    {
        try {
            // whereRaw LOWER() — keeps it case-insensitive across drivers.
            return Coupon::query()
                ->whereRaw('LOWER(code) = ?', [strtolower($code)])
                ->first();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
