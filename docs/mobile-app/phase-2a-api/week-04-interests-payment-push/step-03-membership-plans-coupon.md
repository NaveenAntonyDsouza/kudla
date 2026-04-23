# Step 3 — Membership Plans + Coupon Validation

## Goal
- `GET /api/v1/membership/plans` — public list of plans
- `GET /api/v1/membership/me` — current membership + today's usage
- `POST /api/v1/membership/coupon/validate` — validate coupon for plan

**Design ref:** [`design/08-membership-payment-api.md §8.2–8.4`](../../design/08-membership-payment-api.md)

## Procedure

### 1. `MembershipController`

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Models\Coupon;
use App\Models\MembershipPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipController extends BaseApiController
{
    /**
     * @unauthenticated
     * @group Membership
     */
    public function plans(): JsonResponse
    {
        $plans = MembershipPlan::where('is_active', true)->orderBy('sort_order')->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'slug' => $p->slug,
                'title' => $p->title,
                'description' => $p->description,
                'duration_months' => $p->duration_months,
                'original_price_inr' => $p->original_price ?? $p->sale_price,
                'sale_price_inr' => $p->sale_price,
                'daily_interest_limit' => $p->daily_interest_limit,
                'view_contacts_limit' => $p->view_contacts_limit,
                'daily_contact_views' => $p->daily_contact_views,
                'personalized_messages' => (bool) $p->personalized_messages,
                'featured_profile' => (bool) $p->featured_profile,
                'priority_support' => (bool) $p->priority_support,
                'is_popular' => (bool) $p->is_popular,
                'is_active' => (bool) $p->is_active,
                'features' => $p->features ?? [],
            ]);

        return ApiResponse::ok($plans);
    }

    /**
     * @authenticated
     * @group Membership
     */
    public function mine(Request $request): JsonResponse
    {
        $user = $request->user();
        $membership = $user->activeMembership;
        $profile = $user->profile;

        return ApiResponse::ok([
            'membership' => [
                'plan_id' => $membership?->plan_id ?? 1,
                'plan_title' => $membership?->plan?->title ?? 'Free',
                'starts_at' => $membership?->starts_at?->toIso8601String(),
                'ends_at' => $membership?->ends_at?->toIso8601String(),
                'is_active' => (bool) $membership,
                'days_remaining' => $membership?->ends_at ? (int) $membership->ends_at->diffInDays() : null,
                'source' => $membership?->source ?? 'default',
                'auto_renew' => false,
            ],
            'usage_today' => [
                'interests_sent' => \App\Models\DailyInterestUsage::where('profile_id', $profile->id)
                    ->where('date', today())
                    ->value('sent_count') ?? 0,
                'interests_limit' => $membership?->plan?->daily_interest_limit ?? config('matrimony.daily_interest_limit_free', 5),
                'contacts_viewed' => 0,  // implement if tracking
                'contacts_limit' => $membership?->plan?->daily_contact_views ?? 0,
            ],
        ]);
    }

    /**
     * @authenticated
     * @group Membership
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|integer|exists:membership_plans,id',
            'coupon_code' => 'required|string|max:50',
        ]);

        $plan = MembershipPlan::find($data['plan_id']);
        $coupon = Coupon::where('code', $data['coupon_code'])->first();

        if (! $coupon || ! $coupon->is_active) {
            return ApiResponse::error('COUPON_INVALID', 'Coupon not found or inactive.', status: 400);
        }

        if ($coupon->valid_until && now()->gt($coupon->valid_until)) {
            return ApiResponse::error('COUPON_INVALID', 'This coupon has expired.', status: 400);
        }

        if ($coupon->valid_from && now()->lt($coupon->valid_from)) {
            return ApiResponse::error('COUPON_INVALID', 'This coupon is not yet active.', status: 400);
        }

        if ($coupon->max_usage && $coupon->usage_count >= $coupon->max_usage) {
            return ApiResponse::error('COUPON_INVALID', 'This coupon has reached its usage limit.', status: 400);
        }

        // Check per-user usage
        $userUsed = \App\Models\CouponUsage::where('coupon_id', $coupon->id)
            ->where('user_id', $request->user()->id)
            ->exists();
        if ($userUsed) {
            return ApiResponse::error('COUPON_INVALID', 'You have already used this coupon.', status: 400);
        }

        // Compute discount
        $original = $plan->sale_price;
        $discount = $coupon->discount_type === 'percent'
            ? (int) round($original * $coupon->discount_value / 100)
            : (int) $coupon->discount_value;
        $discount = min($discount, $original);
        $final = $original - $discount;

        return ApiResponse::ok([
            'valid' => true,
            'discount_type' => $coupon->discount_type,
            'discount_value' => $coupon->discount_value,
            'original_amount_inr' => $original,
            'discount_amount_inr' => $discount,
            'final_amount_inr' => $final,
            'coupon_code' => $coupon->code,
        ]);
    }
}
```

### 2. Routes

```php
// Public
Route::get('/membership/plans', [\App\Http\Controllers\Api\V1\MembershipController::class, 'plans']);

// Auth
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/membership/me', [\App\Http\Controllers\Api\V1\MembershipController::class, 'mine']);
    Route::post('/membership/coupon/validate', [\App\Http\Controllers\Api\V1\MembershipController::class, 'validateCoupon']);
});
```

## Verification

- [ ] `/plans` returns all active plans (public, no auth)
- [ ] `/me` returns current plan + usage counts
- [ ] `/coupon/validate` correctly rejects expired/exhausted/already-used coupons
- [ ] 100% coupon computes `final_amount_inr = 0`

## Commit

```bash
git commit -am "phase-2a wk-04: step-03 membership plans + coupon validation"
```

## Next step
→ [step-04-razorpay-order-verify.md](step-04-razorpay-order-verify.md)
