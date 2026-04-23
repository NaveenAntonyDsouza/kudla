# Step 5 — Razorpay Webhook Endpoint

## Goal
Handle `payment.captured`, `payment.failed`, `refund.processed` events from Razorpay. Idempotent.

## Procedure

### 1. Add `webhook` to `MembershipController`

```php
/**
 * Razorpay webhook.
 *
 * @unauthenticated
 * @group Membership
 */
public function webhook(Request $request): JsonResponse
{
    $payload = $request->getContent();
    $signature = $request->header('X-Razorpay-Signature');
    $secret = \App\Models\SiteSetting::getValue('razorpay_webhook_secret');

    $expected = hash_hmac('sha256', $payload, $secret);
    if (! hash_equals($expected, $signature ?? '')) {
        \Log::warning('Razorpay webhook signature mismatch', ['ip' => $request->ip()]);
        return response()->json(['success' => true], 200);  // don't reveal mismatch
    }

    $event = json_decode($payload, true);
    match ($event['event'] ?? null) {
        'payment.captured' => $this->handlePaymentCaptured($event),
        'payment.failed' => $this->handlePaymentFailed($event),
        'refund.processed' => $this->handleRefundProcessed($event),
        default => null,
    };

    return response()->json(['success' => true], 200);
}

private function handlePaymentCaptured(array $event): void
{
    $paymentId = $event['payload']['payment']['entity']['id'] ?? null;
    $orderId = $event['payload']['payment']['entity']['order_id'] ?? null;

    $subscription = \App\Models\Subscription::where('razorpay_order_id', $orderId)->first();
    if (! $subscription || $subscription->status === 'paid') return;  // idempotent

    app(\App\Services\PaymentService::class)->completeSubscription($subscription, $paymentId);
}

private function handlePaymentFailed(array $event): void
{
    $orderId = $event['payload']['payment']['entity']['order_id'] ?? null;
    \App\Models\Subscription::where('razorpay_order_id', $orderId)->update(['status' => 'failed']);
}

private function handleRefundProcessed(array $event): void
{
    $paymentId = $event['payload']['refund']['entity']['payment_id'] ?? null;
    $sub = \App\Models\Subscription::where('razorpay_payment_id', $paymentId)->first();
    if (! $sub) return;

    $sub->update(['status' => 'refunded']);
    // Deactivate membership linked to this subscription
    \App\Models\UserMembership::where('user_id', $sub->user_id)->update(['is_active' => false]);
}
```

### 2. Route (public)

```php
Route::post('/webhooks/razorpay', [\App\Http\Controllers\Api\V1\MembershipController::class, 'webhook']);
```

### 3. Configure Razorpay dashboard

- Razorpay dashboard → Webhooks → Add
- URL: `https://kudlamatrimony.com/api/v1/webhooks/razorpay`
- Events: `payment.captured`, `payment.failed`, `refund.processed`
- Secret: save to `site_settings.razorpay_webhook_secret`

## Verification

- [ ] Webhook endpoint returns 200 even on invalid signature
- [ ] Valid `payment.captured` activates pending subscription
- [ ] Duplicate webhook is idempotent
- [ ] `payment.failed` marks subscription failed

## Commit

```bash
git commit -am "phase-2a wk-04: step-05 Razorpay webhook handler"
```

## Next step
→ [step-06-fcm-install.md](step-06-fcm-install.md)
