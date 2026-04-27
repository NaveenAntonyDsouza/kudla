<?php

use App\Http\Controllers\Api\V1\PaymentController;
use App\Models\Coupon;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserMembership;
use App\Models\WebhookEvent;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\RazorpayService;
use App\Services\Payment\SubscriptionActivator;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Razorpay webhook + SubscriptionActivator tests
|--------------------------------------------------------------------------
| Two concerns covered together since they're tightly coupled:
|
|   1. SubscriptionActivator (used by /verify AND webhook) — happy path,
|      idempotent on already-paid, deactivates priors, records coupon
|      usage, plus the markFailed / markRefunded paths.
|
|   2. Razorpay webhook (POST /webhooks/razorpay) — signature verification
|      with the WEBHOOK secret (separate from API secret), idempotency
|      via webhook_events unique index, dispatch on payment.captured /
|      payment.failed / refund.processed, graceful no-op on unknown
|      events.
|
| End-to-end against real Razorpay webhook deliveries lands at Bruno
| smoke / sandbox time (Phase 2c).
|
| Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-05-razorpay-webhook.md
*/

function createWebhookTables(): void
{
    if (! Schema::hasTable('membership_plans')) {
        Schema::create('membership_plans', function (Blueprint $t) {
            $t->id();
            $t->string('plan_name');
            $t->string('slug')->unique();
            $t->integer('duration_months')->default(1);
            $t->integer('price_inr')->default(0);
            $t->integer('strike_price_inr')->nullable();
            $t->json('features')->nullable();
            $t->integer('daily_interest_limit')->default(5);
            $t->boolean('can_view_contact')->default(false);
            $t->integer('view_contacts_limit')->default(0);
            $t->integer('daily_contact_views')->default(0);
            $t->boolean('personalized_messages')->default(false);
            $t->boolean('allows_free_member_chat')->default(false);
            $t->boolean('exposes_contact_to_free')->default(false);
            $t->boolean('featured_profile')->default(false);
            $t->boolean('priority_support')->default(false);
            $t->boolean('is_highlighted')->default(false);
            $t->boolean('is_popular')->default(false);
            $t->integer('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }
    if (! Schema::hasTable('subscriptions')) {
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->unsignedBigInteger('plan_id');
            $t->string('plan_name')->nullable();
            $t->string('gateway', 30)->default('razorpay');
            $t->json('gateway_metadata')->nullable();
            $t->unsignedBigInteger('coupon_id')->nullable();
            $t->string('coupon_code', 50)->nullable();
            $t->unsignedInteger('discount_amount')->default(0);
            $t->unsignedInteger('original_amount')->default(0);
            $t->unsignedInteger('amount')->default(0);
            $t->string('razorpay_order_id', 100)->nullable();
            $t->string('razorpay_payment_id', 100)->nullable();
            $t->string('razorpay_signature', 200)->nullable();
            $t->string('payment_status', 20)->default('pending');
            $t->date('starts_at')->nullable();
            $t->date('expires_at')->nullable();
            $t->boolean('is_active')->default(false);
            $t->timestamps();
        });
    }
    if (! Schema::hasTable('user_memberships')) {
        Schema::create('user_memberships', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('plan_id');
            $t->boolean('is_active')->default(true);
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->timestamps();
        });
    }
    if (! Schema::hasTable('coupons')) {
        Schema::create('coupons', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->string('code', 50)->unique();
            $t->string('description', 200)->nullable();
            $t->string('discount_type', 20);
            $t->unsignedInteger('discount_value');
            $t->unsignedInteger('max_discount_cap')->nullable();
            $t->unsignedInteger('min_purchase_amount')->nullable();
            $t->json('applicable_plan_ids')->nullable();
            $t->unsignedInteger('usage_limit_total')->nullable();
            $t->unsignedInteger('usage_limit_per_user')->default(1);
            $t->unsignedInteger('times_used')->default(0);
            $t->date('valid_from')->nullable();
            $t->date('valid_until')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }
    if (! Schema::hasTable('coupon_usages')) {
        Schema::create('coupon_usages', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('coupon_id');
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('subscription_id')->nullable();
            $t->unsignedInteger('discount_amount')->default(0);
            $t->timestamps();
        });
    }
    if (! Schema::hasTable('webhook_events')) {
        Schema::create('webhook_events', function (Blueprint $t) {
            $t->id();
            $t->string('gateway', 30);
            $t->string('event_id', 100);
            $t->string('event_type', 80);
            $t->string('status', 20)->default('processed');
            $t->json('payload')->nullable();
            $t->timestamps();
            $t->unique(['gateway', 'event_id']);
        });
    }
}

function dropWebhookTables(): void
{
    Schema::dropIfExists('webhook_events');
    Schema::dropIfExists('coupon_usages');
    Schema::dropIfExists('coupons');
    Schema::dropIfExists('user_memberships');
    Schema::dropIfExists('subscriptions');
    Schema::dropIfExists('membership_plans');
}

function seedWebhookPlan(array $overrides = []): MembershipPlan
{
    return MembershipPlan::create(array_merge([
        'plan_name' => 'Test Plan',
        'slug' => 'test-plan-'.uniqid(),
        'duration_months' => 6,
        'price_inr' => 999,
        'is_active' => true,
    ], $overrides));
}

function seedPendingSubscription(int $userId, int $planId, string $orderId = 'order_test_xx', array $overrides = []): Subscription
{
    return Subscription::create(array_merge([
        'user_id' => $userId,
        'plan_id' => $planId,
        'gateway' => 'razorpay',
        'razorpay_order_id' => $orderId,
        'amount' => 99900,
        'original_amount' => 99900,
        'payment_status' => 'pending',
    ], $overrides));
}

/** Build a Razorpay event payload + valid signature. */
function razorpayEvent(string $type, array $entity, string $eventId = 'evt_test_001'): array
{
    $payload = [
        'event' => $type,
        'id' => $eventId,
        'payload' => [],
    ];

    if (str_starts_with($type, 'payment.')) {
        $payload['payload']['payment'] = ['entity' => $entity];
    } elseif (str_starts_with($type, 'refund.')) {
        $payload['payload']['refund'] = ['entity' => $entity];
    }

    return $payload;
}

function signedWebhookRequest(array $event, string $secret): Request
{
    $body = json_encode($event);
    $signature = hash_hmac('sha256', $body, $secret);

    $request = Request::create('/api/v1/webhooks/razorpay', 'POST', [], [], [], [
        'HTTP_X-Razorpay-Signature' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $body);

    return $request;
}

beforeEach(function () {
    createWebhookTables();
    config([
        'services.razorpay.key' => 'rzp_test_xxx',
        'services.razorpay.secret' => 'api-secret',
        'services.razorpay.webhook_secret' => 'webhook-secret-xxx',
    ]);
});

afterEach(function () {
    dropWebhookTables();
});

/* ==================================================================
 |  SubscriptionActivator
 | ================================================================== */

it('activator marks subscription paid + creates UserMembership on first call', function () {
    $plan = seedWebhookPlan(['duration_months' => 3]);
    $sub = seedPendingSubscription(7700, $plan->id);

    $activator = app(SubscriptionActivator::class);
    $result = $activator->activate($sub);

    expect($result)->toBeTrue();
    $sub->refresh();
    expect($sub->payment_status)->toBe('paid');
    expect($sub->is_active)->toBeTrue();
    expect($sub->starts_at)->not->toBeNull();
    expect($sub->expires_at)->not->toBeNull();

    $mem = UserMembership::where('user_id', 7700)->first();
    expect($mem)->not->toBeNull();
    expect($mem->is_active)->toBeTrue();
});

it('activator is idempotent — second call on already-paid returns false', function () {
    $plan = seedWebhookPlan();
    $sub = seedPendingSubscription(7700, $plan->id, overrides: ['payment_status' => 'paid', 'is_active' => true]);

    $activator = app(SubscriptionActivator::class);
    $result = $activator->activate($sub);

    expect($result)->toBeFalse();  // no-op
    expect(UserMembership::where('user_id', 7700)->count())->toBe(0);  // didn't create one
});

it('activator deactivates prior active memberships', function () {
    $oldPlan = seedWebhookPlan(['plan_name' => 'Old']);
    $newPlan = seedWebhookPlan(['plan_name' => 'New']);

    UserMembership::create([
        'user_id' => 7700,
        'plan_id' => $oldPlan->id,
        'is_active' => true,
        'starts_at' => Carbon::now()->subMonths(2),
        'ends_at' => Carbon::now()->addMonths(1),
    ]);

    $sub = seedPendingSubscription(7700, $newPlan->id);

    app(SubscriptionActivator::class)->activate($sub);

    expect(UserMembership::where('plan_id', $oldPlan->id)->first()->is_active)->toBeFalse();
    expect(UserMembership::where('plan_id', $newPlan->id)->first()->is_active)->toBeTrue();
});

it('activator records coupon usage when subscription has coupon_id', function () {
    $plan = seedWebhookPlan();
    $coupon = Coupon::create([
        'code' => 'WELCOME20',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'usage_limit_per_user' => 1,
        'is_active' => true,
    ]);
    $sub = seedPendingSubscription(7700, $plan->id, overrides: [
        'coupon_id' => $coupon->id,
        'coupon_code' => 'WELCOME20',
        'discount_amount' => 19980,
    ]);

    app(SubscriptionActivator::class)->activate($sub);

    expect(\App\Models\CouponUsage::where('coupon_id', $coupon->id)->count())->toBe(1);
    expect($coupon->fresh()->times_used)->toBe(1);
});

it('activator markFailed flips pending → failed', function () {
    $plan = seedWebhookPlan();
    $sub = seedPendingSubscription(7700, $plan->id);

    app(SubscriptionActivator::class)->markFailed($sub);

    expect($sub->fresh()->payment_status)->toBe('failed');
    expect($sub->fresh()->is_active)->toBeFalse();
});

it('activator markFailed does NOT flip paid back to failed', function () {
    $plan = seedWebhookPlan();
    $sub = seedPendingSubscription(7700, $plan->id, overrides: ['payment_status' => 'paid', 'is_active' => true]);

    app(SubscriptionActivator::class)->markFailed($sub);

    // Subscription stays paid — markFailed protects terminal "paid" state.
    expect($sub->fresh()->payment_status)->toBe('paid');
});

it('activator markRefunded sets refunded + deactivates membership', function () {
    $plan = seedWebhookPlan();
    $sub = seedPendingSubscription(7700, $plan->id, overrides: [
        'payment_status' => 'paid',
        'is_active' => true,
    ]);
    UserMembership::create([
        'user_id' => 7700,
        'plan_id' => $plan->id,
        'is_active' => true,
        'starts_at' => Carbon::now(),
        'ends_at' => Carbon::now()->addMonths(6),
    ]);

    app(SubscriptionActivator::class)->markRefunded($sub);

    expect($sub->fresh()->payment_status)->toBe('refunded');
    expect($sub->fresh()->is_active)->toBeFalse();
    expect(UserMembership::where('user_id', 7700)->first()->is_active)->toBeFalse();
});

/* ==================================================================
 |  Razorpay webhook — signature + dedup
 | ================================================================== */

it('webhook returns 401 on invalid signature', function () {
    $request = Request::create('/api/v1/webhooks/razorpay', 'POST', [], [], [], [
        'HTTP_X-Razorpay-Signature' => 'totally-wrong-signature',
        'CONTENT_TYPE' => 'application/json',
    ], json_encode(['event' => 'payment.captured', 'id' => 'evt_x', 'payload' => []]));

    $controller = makeWebhookController();
    $response = $controller->webhook($request, 'razorpay');

    expect($response->getStatusCode())->toBe(401);
});

it('webhook returns 503 when webhook_secret is not configured', function () {
    config(['services.razorpay.webhook_secret' => null]);

    $request = signedWebhookRequest(razorpayEvent('payment.captured', ['id' => 'pay_x', 'order_id' => 'order_x']), 'whatever');

    $response = makeWebhookController()->webhook($request, 'razorpay');

    expect($response->getStatusCode())->toBe(503);
});

it('webhook returns 422 on malformed JSON', function () {
    $request = Request::create('/api/v1/webhooks/razorpay', 'POST', [], [], [], [
        'HTTP_X-Razorpay-Signature' => hash_hmac('sha256', 'not-json', 'webhook-secret-xxx'),
        'CONTENT_TYPE' => 'application/json',
    ], 'not-json');

    $response = makeWebhookController()->webhook($request, 'razorpay');

    expect($response->getStatusCode())->toBe(422);
});

it('webhook returns 404 for unknown gateway slug', function () {
    $request = signedWebhookRequest(razorpayEvent('payment.captured', []), 'webhook-secret-xxx');

    $response = makeWebhookController()->webhook($request, 'nonexistent-gateway');

    expect($response->getStatusCode())->toBe(404);
});

/* ==================================================================
 |  Razorpay webhook — payment.captured
 | ================================================================== */

it('webhook payment.captured marks subscription paid + creates membership', function () {
    $plan = seedWebhookPlan(['duration_months' => 6]);
    $sub = seedPendingSubscription(7700, $plan->id, 'order_M1zXabc');

    $event = razorpayEvent('payment.captured', [
        'id' => 'pay_M1zXqrstuv',
        'order_id' => 'order_M1zXabc',
    ], 'evt_001');
    $request = signedWebhookRequest($event, 'webhook-secret-xxx');

    $response = makeWebhookController()->webhook($request, 'razorpay');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('processed');

    $sub->refresh();
    expect($sub->payment_status)->toBe('paid');
    expect($sub->is_active)->toBeTrue();
    expect($sub->razorpay_payment_id)->toBe('pay_M1zXqrstuv');
    expect(UserMembership::where('user_id', 7700)->where('is_active', true)->exists())->toBeTrue();
});

it('webhook payment.captured is idempotent — duplicate event_id returns 200 duplicate', function () {
    $plan = seedWebhookPlan();
    seedPendingSubscription(7700, $plan->id, 'order_M1zXabc');

    $event = razorpayEvent('payment.captured', [
        'id' => 'pay_X', 'order_id' => 'order_M1zXabc',
    ], 'evt_dup');

    // First delivery — processed.
    $r1 = makeWebhookController()->webhook(signedWebhookRequest($event, 'webhook-secret-xxx'), 'razorpay');
    expect($r1->getStatusCode())->toBe(200);
    expect($r1->getData(true)['status'])->toBe('processed');

    // Second delivery (Razorpay retry) — duplicate.
    $r2 = makeWebhookController()->webhook(signedWebhookRequest($event, 'webhook-secret-xxx'), 'razorpay');
    expect($r2->getStatusCode())->toBe(200);
    expect($r2->getData(true)['status'])->toBe('duplicate');

    // Webhook event row count is exactly 1 (unique constraint enforced).
    expect(WebhookEvent::where('event_id', 'evt_dup')->count())->toBe(1);

    // UserMembership count is exactly 1 (no double-activation).
    expect(UserMembership::where('user_id', 7700)->count())->toBe(1);
});

it('webhook payment.captured returns 200 ignored when subscription not found for order_id', function () {
    $event = razorpayEvent('payment.captured', [
        'id' => 'pay_X', 'order_id' => 'order_orphan',
    ], 'evt_orphan');

    $response = makeWebhookController()->webhook(signedWebhookRequest($event, 'webhook-secret-xxx'), 'razorpay');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('ignored');
});

/* ==================================================================
 |  Razorpay webhook — payment.failed + refund.processed
 | ================================================================== */

it('webhook payment.failed marks subscription failed', function () {
    $plan = seedWebhookPlan();
    $sub = seedPendingSubscription(7700, $plan->id, 'order_X');

    $event = razorpayEvent('payment.failed', [
        'id' => 'pay_X', 'order_id' => 'order_X',
    ], 'evt_failed');

    $response = makeWebhookController()->webhook(signedWebhookRequest($event, 'webhook-secret-xxx'), 'razorpay');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('processed');
    expect($sub->fresh()->payment_status)->toBe('failed');
});

it('webhook refund.processed marks subscription refunded + deactivates membership', function () {
    $plan = seedWebhookPlan();
    $sub = seedPendingSubscription(7700, $plan->id, 'order_X', [
        'razorpay_payment_id' => 'pay_X',
        'payment_status' => 'paid',
        'is_active' => true,
    ]);
    UserMembership::create([
        'user_id' => 7700,
        'plan_id' => $plan->id,
        'is_active' => true,
        'starts_at' => Carbon::now(),
        'ends_at' => Carbon::now()->addMonths(6),
    ]);

    $event = razorpayEvent('refund.processed', [
        'id' => 'rfnd_X', 'payment_id' => 'pay_X',
    ], 'evt_refund');

    $response = makeWebhookController()->webhook(signedWebhookRequest($event, 'webhook-secret-xxx'), 'razorpay');

    expect($response->getStatusCode())->toBe(200);
    expect($sub->fresh()->payment_status)->toBe('refunded');
    expect(UserMembership::where('user_id', 7700)->first()->is_active)->toBeFalse();
});

/* ==================================================================
 |  Razorpay webhook — unknown event types
 | ================================================================== */

it('webhook returns 200 ignored for unknown event types', function () {
    $event = razorpayEvent('subscription.charged', [], 'evt_unknown');
    // Add a fake "subscription" entity to keep the payload shape sensible.
    $event['payload']['subscription'] = ['entity' => ['id' => 'sub_X']];

    $response = makeWebhookController()->webhook(signedWebhookRequest($event, 'webhook-secret-xxx'), 'razorpay');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('ignored');

    // Row recorded with status='ignored' for admin debugging.
    $row = WebhookEvent::where('event_id', 'evt_unknown')->first();
    expect($row)->not->toBeNull();
    expect($row->status)->toBe('ignored');
});

/* ==================================================================
 |  Helper — controller with manager bound
 | ================================================================== */

/**
 * The PaymentController constructor takes PaymentGatewayManager.
 * The manager is bound as a singleton with RazorpayService registered
 * (in AppServiceProvider). Tests use the real binding.
 */
function makeWebhookController(): PaymentController
{
    return app(PaymentController::class);
}
