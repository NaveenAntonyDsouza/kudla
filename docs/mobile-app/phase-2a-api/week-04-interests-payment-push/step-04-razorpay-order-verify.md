# Step 4 — Razorpay Create Order + Verify Payment

## Goal
- `POST /api/v1/membership/order` — creates Razorpay order, returns payload Flutter feeds to `razorpay_flutter` SDK
- `POST /api/v1/membership/verify` — verifies signature after Razorpay success callback; activates membership

**Design ref:** [`design/08-membership-payment-api.md §8.5–8.6`](../../design/08-membership-payment-api.md)

## Procedure

### 1. Install Razorpay PHP SDK (if not already)

```bash
composer require razorpay/razorpay
```

### 2. Add methods to `MembershipController`

```php
use Razorpay\Api\Api as RazorpayApi;

/**
 * @authenticated
 * @group Membership
 */
public function createOrder(Request $request): JsonResponse
{
    $data = $request->validate([
        'plan_id' => 'required|integer|exists:membership_plans,id',
        'coupon_code' => 'nullable|string|max:50',
    ]);

    $user = $request->user();
    $plan = \App\Models\MembershipPlan::find($data['plan_id']);

    // Re-validate coupon server-side (don't trust client)
    $finalInr = $plan->sale_price;
    $coupon = null;
    $discountInr = 0;

    if (! empty($data['coupon_code'])) {
        $validation = $this->validateCoupon($request)->getData(true);
        if ($validation['success']) {
            $finalInr = $validation['data']['final_amount_inr'];
            $discountInr = $validation['data']['discount_amount_inr'];
            $coupon = \App\Models\Coupon::where('code', $data['coupon_code'])->first();
        } else {
            return ApiResponse::error($validation['error']['code'], $validation['error']['message'], status: 400);
        }
    }

    $finalPaise = $finalInr * 100;

    // Create subscription row (pending)
    $subscription = \App\Models\Subscription::create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'amount_paise' => $finalPaise,
        'original_amount_paise' => $plan->sale_price * 100,
        'discount_amount_paise' => $discountInr * 100,
        'coupon_id' => $coupon?->id,
        'coupon_code' => $coupon?->code,
        'status' => 'pending',
    ]);

    // 100% coupon short-circuit
    if ($finalPaise === 0) {
        app(\App\Services\PaymentService::class)->activateFreeSubscription($subscription);
        return ApiResponse::ok([
            'is_free' => true,
            'subscription_id' => $subscription->id,
            'membership' => $user->fresh()->activeMembership,
        ]);
    }

    // Create Razorpay order
    $razorpay = new RazorpayApi(
        \App\Models\SiteSetting::getValue('razorpay_key_id'),
        \App\Models\SiteSetting::getValue('razorpay_key_secret'),
    );

    $order = $razorpay->order->create([
        'amount' => $finalPaise,
        'currency' => 'INR',
        'receipt' => 'sub_' . $subscription->id,
        'notes' => [
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
        ],
    ]);

    $subscription->update(['razorpay_order_id' => $order->id]);

    return ApiResponse::ok([
        'is_free' => false,
        'subscription_id' => $subscription->id,
        'razorpay' => [
            'order_id' => $order->id,
            'amount_paise' => $finalPaise,
            'currency' => 'INR',
            'key' => \App\Models\SiteSetting::getValue('razorpay_key_id'),
        ],
        'user' => ['name' => $user->name, 'email' => $user->email, 'contact' => $user->phone],
        'prefill' => ['name' => $user->name, 'email' => $user->email, 'contact' => $user->phone],
        'notes' => ['subscription_id' => $subscription->id, 'user_id' => $user->id, 'plan_id' => $plan->id],
        'theme' => ['color' => \App\Models\SiteSetting::getValue('primary_color', '#dc2626')],
    ]);
}

/**
 * @authenticated
 * @group Membership
 */
public function verifyPayment(Request $request): JsonResponse
{
    $data = $request->validate([
        'subscription_id' => 'required|integer|exists:subscriptions,id',
        'razorpay_payment_id' => 'required|string',
        'razorpay_order_id' => 'required|string',
        'razorpay_signature' => 'required|string',
    ]);

    $subscription = \App\Models\Subscription::find($data['subscription_id']);
    abort_if($subscription->user_id !== $request->user()->id, 403);
    abort_if($subscription->razorpay_order_id !== $data['razorpay_order_id'], 400);

    // Verify signature
    $expectedSig = hash_hmac(
        'sha256',
        $data['razorpay_order_id'] . '|' . $data['razorpay_payment_id'],
        \App\Models\SiteSetting::getValue('razorpay_key_secret'),
    );

    if (! hash_equals($expectedSig, $data['razorpay_signature'])) {
        return ApiResponse::error('PAYMENT_FAILED', 'Signature verification failed.', status: 400);
    }

    // Activate (idempotent)
    if ($subscription->status !== 'paid') {
        app(\App\Services\PaymentService::class)->completeSubscription(
            $subscription,
            $data['razorpay_payment_id'],
            $data['razorpay_signature'],
        );
    }

    $subscription->refresh();

    return ApiResponse::ok([
        'subscription' => [
            'id' => $subscription->id,
            'status' => $subscription->status,
            'amount_inr' => $subscription->amount_paise / 100,
            'plan_title' => $subscription->plan->title,
            'paid_at' => $subscription->paid_at?->toIso8601String(),
            'receipt_url' => url("/api/v1/membership/subscriptions/{$subscription->id}/receipt.pdf"),
        ],
        'membership' => [
            'plan_id' => $subscription->plan_id,
            'plan_title' => $subscription->plan->title,
            'starts_at' => $request->user()->activeMembership?->starts_at?->toIso8601String(),
            'ends_at' => $request->user()->activeMembership?->ends_at?->toIso8601String(),
            'is_active' => true,
        ],
    ]);
}
```

### 3. Routes

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/membership/order', [\App\Http\Controllers\Api\V1\MembershipController::class, 'createOrder']);
    Route::post('/membership/verify', [\App\Http\Controllers\Api\V1\MembershipController::class, 'verifyPayment']);
});
```

## Verification

- [ ] Order creates Razorpay order + pending subscription
- [ ] 100% coupon skips Razorpay and activates directly
- [ ] Signature verification rejects tampered payloads
- [ ] Subscription + UserMembership correctly populated on verify
- [ ] Duplicate verify calls are idempotent

## Commit

```bash
git commit -am "phase-2a wk-04: step-04 Razorpay order + verify"
```

## Next step
→ [step-05-razorpay-webhook.md](step-05-razorpay-webhook.md)
