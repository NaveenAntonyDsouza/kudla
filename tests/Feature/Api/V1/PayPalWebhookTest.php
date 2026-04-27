<?php

use App\Http\Controllers\Api\V1\PaymentController;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserMembership;
use App\Models\WebhookEvent;
use App\Services\Payment\PayPalService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| PayPal — service + webhook tests
|--------------------------------------------------------------------------
| Mirrors Razorpay/Stripe coverage with PayPal specifics:
|
|   1. PayPalService unit slice — isConfigured, createOrder (Http::fake
|      including the OAuth token endpoint), verifyPayment capture flow
|      including the ORDER_ALREADY_CAPTURED 422 fallback.
|
|   2. Webhook verification — POST /v1/notifications/verify-webhook-signature
|      against PayPal (Http::fake), 503 on missing webhook_id, 401 on
|      missing transmission headers, 401 on FAILED verification.
|
|   3. Webhook dispatch — PAYMENT.CAPTURE.COMPLETED → activate (lookup
|      via custom_id), PAYMENT.CAPTURE.DENIED / .DECLINED → markFailed,
|      PAYMENT.CAPTURE.REFUNDED → markRefunded (lookup via capture_id
|      in supplementary_data.related_ids), unknown → ignored.
|
|   4. Idempotency — duplicate event_id → 200 'duplicate'.
|
| Tests preload the OAuth token into the cache so each Http::fake doesn't
| need to mock /v1/oauth2/token. Webhook tests still mock the
| verify-webhook-signature endpoint.
|
| Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-05-razorpay-webhook.md
| (PayPal addition — step-05b)
*/

function createPayPalTables(): void
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

function dropPayPalTables(): void
{
    Schema::dropIfExists('webhook_events');
    Schema::dropIfExists('user_memberships');
    Schema::dropIfExists('subscriptions');
    Schema::dropIfExists('membership_plans');
}

function seedPayPalPlan(array $overrides = []): MembershipPlan
{
    return MembershipPlan::create(array_merge([
        'plan_name' => 'PayPal Plan',
        'slug' => 'paypal-plan-'.uniqid(),
        'duration_months' => 6,
        'price_inr' => 999,
        'is_active' => true,
    ], $overrides));
}

/** Pending PayPal subscription with paypal_order_id in gateway_metadata. */
function seedPayPalSubscription(int $userId, int $planId, string $orderId = 'PAYPAL_ORDER_TEST', array $overrides = []): Subscription
{
    return Subscription::create(array_merge([
        'user_id' => $userId,
        'plan_id' => $planId,
        'gateway' => 'paypal',
        'gateway_metadata' => ['paypal_order_id' => $orderId, 'paypal_status' => 'CREATED'],
        'amount' => 99900,
        'original_amount' => 99900,
        'payment_status' => 'pending',
    ], $overrides));
}

/** Build a PayPal webhook event payload (top-level shape). */
function paypalEvent(string $type, array $resource, string $eventId = 'WH-TEST-001'): array
{
    return [
        'id' => $eventId,
        'event_type' => $type,
        'resource_type' => 'capture',
        'summary' => "Test {$type} event",
        'resource' => $resource,
    ];
}

/**
 * Build a request with all five PayPal transmission headers. The actual
 * signature is bogus — verification is delegated to PayPal's API which
 * we mock per-test.
 */
function paypalSignedRequest(array $event): Request
{
    return Request::create('/api/v1/webhooks/paypal', 'POST', [], [], [], [
        'HTTP_Paypal-Auth-Algo' => 'SHA256withRSA',
        'HTTP_Paypal-Cert-Url' => 'https://api.sandbox.paypal.com/v1/notifications/certs/CERT-360caa42',
        'HTTP_Paypal-Transmission-Id' => 'transmission-id-test',
        'HTTP_Paypal-Transmission-Sig' => 'transmission-sig-test',
        'HTTP_Paypal-Transmission-Time' => '2026-04-26T10:00:00Z',
        'CONTENT_TYPE' => 'application/json',
    ], json_encode($event));
}

beforeEach(function () {
    createPayPalTables();
    config([
        'services.paypal.client_id' => 'paypal-client-id-xxx',
        'services.paypal.secret' => 'paypal-secret-xxx',
        'services.paypal.mode' => 'sandbox',
        'services.paypal.webhook_id' => 'WH-CONFIGURED-ID',
        'services.paypal.currency' => 'USD',
    ]);
    // Pre-populate OAuth token in cache so tests don't need to mock
    // /v1/oauth2/token on every Http::fake() block.
    Cache::put('paypal_access_token_sandbox', 'fake-access-token-xxx', 3600);
});

afterEach(function () {
    Cache::forget('paypal_access_token_sandbox');
    Cache::forget('paypal_access_token_live');
    dropPayPalTables();
});

/* ==================================================================
 |  PayPalService — isConfigured + createOrder + verifyPayment
 | ================================================================== */

it('paypal isConfigured returns true when client_id + secret present', function () {
    expect(app(PayPalService::class)->isConfigured())->toBeTrue();
});

it('paypal isConfigured returns false when client_id missing', function () {
    config(['services.paypal.client_id' => null]);
    expect(app(PayPalService::class)->isConfigured())->toBeFalse();
});

it('paypal isConfigured returns false when secret missing', function () {
    config(['services.paypal.secret' => null]);
    expect(app(PayPalService::class)->isConfigured())->toBeFalse();
});

it('paypal createOrder posts an order + returns Flutter-friendly payload', function () {
    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
            'id' => 'PAYPAL_ORDER_5O190127TN364715T',
            'status' => 'CREATED',
            'links' => [
                ['rel' => 'self', 'href' => 'https://...'],
                ['rel' => 'approve', 'href' => 'https://www.paypal.com/checkoutnow?token=PAYPAL_ORDER_5O190127TN364715T'],
            ],
        ], 201),
    ]);

    $payload = app(PayPalService::class)->createOrder(99900, [
        'subscription_id' => 12,
        'plan_name' => 'Diamond Plus',
        'receipt' => 'sub_12',
    ]);

    expect($payload['paypal_order_id'])->toBe('PAYPAL_ORDER_5O190127TN364715T');
    expect($payload['status'])->toBe('CREATED');
    expect($payload['approve_url'])->toContain('paypal.com/checkoutnow');
    expect($payload['currency'])->toBe('USD');
    expect($payload['amount'])->toBe('999.00');
    expect($payload['client_id'])->toBe('paypal-client-id-xxx');
    expect($payload['mode'])->toBe('sandbox');

    Http::assertSent(function ($req) {
        return str_contains($req->url(), '/v2/checkout/orders')
            && $req->method() === 'POST'
            && $req['intent'] === 'CAPTURE'
            && $req['purchase_units'][0]['amount']['currency_code'] === 'USD'
            && $req['purchase_units'][0]['amount']['value'] === '999.00'
            && $req['purchase_units'][0]['custom_id'] === '12';
    });
});

it('paypal createOrder uses sandbox base URL when mode=sandbox', function () {
    Http::fake([
        'api-m.sandbox.paypal.com/*' => Http::response(['id' => 'X', 'status' => 'CREATED', 'links' => []], 201),
    ]);

    app(PayPalService::class)->createOrder(99900, []);

    Http::assertSent(function ($req) {
        return str_starts_with($req->url(), 'https://api-m.sandbox.paypal.com/');
    });
});

it('paypal createOrder uses live base URL when mode=live', function () {
    config(['services.paypal.mode' => 'live']);
    Cache::put('paypal_access_token_live', 'live-token', 3600);

    Http::fake([
        'api-m.paypal.com/*' => Http::response(['id' => 'X', 'status' => 'CREATED', 'links' => []], 201),
    ]);

    app(PayPalService::class)->createOrder(99900, []);

    Http::assertSent(function ($req) {
        return str_starts_with($req->url(), 'https://api-m.paypal.com/')
            && ! str_contains($req->url(), 'sandbox');
    });
});

it('paypal createOrder throws when API returns error', function () {
    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response(
            ['name' => 'INVALID_REQUEST', 'message' => 'Bad amount'],
            400,
        ),
    ]);

    expect(fn () => app(PayPalService::class)->createOrder(99900, []))
        ->toThrow(\RuntimeException::class);
});

it('paypal createOrder triggers OAuth token fetch when cache empty', function () {
    Cache::forget('paypal_access_token_sandbox');

    Http::fake([
        'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
            'access_token' => 'newly-fetched-token',
            'expires_in' => 32400,
        ], 200),
        'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
            'id' => 'X', 'status' => 'CREATED', 'links' => [],
        ], 201),
    ]);

    app(PayPalService::class)->createOrder(99900, []);

    // Token call happened.
    Http::assertSent(fn ($req) => str_contains($req->url(), '/v1/oauth2/token'));
    // Subsequent order call used the new bearer token.
    Http::assertSent(function ($req) {
        return str_contains($req->url(), '/v2/checkout/orders')
            && $req->hasHeader('Authorization', 'Bearer newly-fetched-token');
    });
    // Token cached for next time.
    expect(Cache::get('paypal_access_token_sandbox'))->toBe('newly-fetched-token');
});

/**
 * Build an in-memory Subscription with paypal_order_id in
 * gateway_metadata — verifyPayment now binds against it (Phase 2a Vuln 1).
 */
function paypalSubscription(string $orderId = 'ORDER_X'): \App\Models\Subscription
{
    $sub = new \App\Models\Subscription();
    $sub->forceFill([
        'id' => 1,
        'gateway_metadata' => ['paypal_order_id' => $orderId],
    ]);
    return $sub;
}

it('paypal verifyPayment captures order + returns true on COMPLETED', function () {
    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_X/capture' => Http::response([
            'id' => 'ORDER_X',
            'status' => 'COMPLETED',
            'purchase_units' => [['payments' => ['captures' => [['id' => 'CAPTURE_X']]]]],
        ], 201),
    ]);

    $ok = app(PayPalService::class)->verifyPayment(
        ['paypal_order_id' => 'ORDER_X'],
        paypalSubscription('ORDER_X'),
    );

    expect($ok)->toBeTrue();
});

it('paypal verifyPayment returns false when capture status is not COMPLETED', function () {
    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_X/capture' => Http::response([
            'id' => 'ORDER_X',
            'status' => 'PENDING',
        ], 201),
    ]);

    expect(app(PayPalService::class)->verifyPayment(
        ['paypal_order_id' => 'ORDER_X'],
        paypalSubscription('ORDER_X'),
    ))->toBeFalse();
});

it('paypal verifyPayment falls back to GET on ORDER_ALREADY_CAPTURED 422', function () {
    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_X/capture' => Http::response([
            'name' => 'UNPROCESSABLE_ENTITY',
            'details' => [['issue' => 'ORDER_ALREADY_CAPTURED']],
        ], 422),
        'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_X' => Http::response([
            'id' => 'ORDER_X',
            'status' => 'COMPLETED',
        ], 200),
    ]);

    $ok = app(PayPalService::class)->verifyPayment(
        ['paypal_order_id' => 'ORDER_X'],
        paypalSubscription('ORDER_X'),
    );

    expect($ok)->toBeTrue();
});

it('paypal verifyPayment REJECTS replay across subscriptions (Vuln 1 anti-substitution)', function () {
    Http::fake([
        'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_PAID/capture' => Http::response([
            'id' => 'ORDER_PAID',
            'status' => 'COMPLETED',
        ], 201),
    ]);

    // Submitted order id is sub_A's, but the named subscription has a
    // different persisted paypal_order_id.
    expect(app(PayPalService::class)->verifyPayment(
        ['paypal_order_id' => 'ORDER_PAID'],
        paypalSubscription('ORDER_OTHER'),
    ))->toBeFalse();
});

/* ==================================================================
 |  PayPal webhook — auth headers + verification API
 | ================================================================== */

it('paypal webhook returns 503 when webhook_id is not configured', function () {
    config(['services.paypal.webhook_id' => null]);

    $request = paypalSignedRequest(paypalEvent('PAYMENT.CAPTURE.COMPLETED', []));

    $response = app(PaymentController::class)->webhook($request, 'paypal');

    expect($response->getStatusCode())->toBe(503);
});

it('paypal webhook returns 401 when transmission headers are missing', function () {
    $request = Request::create('/api/v1/webhooks/paypal', 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode(paypalEvent('PAYMENT.CAPTURE.COMPLETED', [])));

    $response = app(PaymentController::class)->webhook($request, 'paypal');

    expect($response->getStatusCode())->toBe(401);
});

it('paypal webhook returns 422 on malformed JSON', function () {
    $request = Request::create('/api/v1/webhooks/paypal', 'POST', [], [], [], [
        'HTTP_Paypal-Auth-Algo' => 'SHA256withRSA',
        'HTTP_Paypal-Cert-Url' => 'https://x',
        'HTTP_Paypal-Transmission-Id' => 'x',
        'HTTP_Paypal-Transmission-Sig' => 'x',
        'HTTP_Paypal-Transmission-Time' => 'x',
        'CONTENT_TYPE' => 'application/json',
    ], 'not-json');

    $response = app(PaymentController::class)->webhook($request, 'paypal');

    expect($response->getStatusCode())->toBe(422);
});

it('paypal webhook returns 401 when verify-webhook-signature returns FAILED', function () {
    Http::fake([
        'api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response([
            'verification_status' => 'FAILURE',
        ], 200),
    ]);

    $request = paypalSignedRequest(paypalEvent('PAYMENT.CAPTURE.COMPLETED', ['id' => 'CAP_X']));

    $response = app(PaymentController::class)->webhook($request, 'paypal');

    expect($response->getStatusCode())->toBe(401);
});

/* ==================================================================
 |  PayPal webhook — PAYMENT.CAPTURE.COMPLETED
 | ================================================================== */

it('paypal webhook PAYMENT.CAPTURE.COMPLETED activates subscription via custom_id', function () {
    $plan = seedPayPalPlan(['duration_months' => 6]);
    $sub = seedPayPalSubscription(7700, $plan->id);

    Http::fake([
        'api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS'], 200),
    ]);

    $event = paypalEvent('PAYMENT.CAPTURE.COMPLETED', [
        'id' => 'CAPTURE_5O',
        'status' => 'COMPLETED',
        'amount' => ['currency_code' => 'USD', 'value' => '999.00'],
        'custom_id' => (string) $sub->id,  // hook for subscription lookup
        'supplementary_data' => ['related_ids' => ['order_id' => 'PAYPAL_ORDER_TEST']],
    ], 'WH-001');

    $response = app(PaymentController::class)->webhook(paypalSignedRequest($event), 'paypal');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('processed');

    $sub->refresh();
    expect($sub->payment_status)->toBe('paid');
    expect($sub->is_active)->toBeTrue();
    expect($sub->gateway_metadata['paypal_capture_id'])->toBe('CAPTURE_5O');
    expect($sub->gateway_metadata['paypal_status'])->toBe('COMPLETED');
    expect(UserMembership::where('user_id', 7700)->where('is_active', true)->exists())->toBeTrue();
});

it('paypal webhook falls back to order_id lookup when custom_id is empty', function () {
    $plan = seedPayPalPlan();
    $sub = seedPayPalSubscription(7700, $plan->id, 'ORDER_FALLBACK');

    Http::fake([
        'api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS'], 200),
    ]);

    $event = paypalEvent('PAYMENT.CAPTURE.COMPLETED', [
        'id' => 'CAP_X',
        'status' => 'COMPLETED',
        'custom_id' => '',  // empty — must fall back
        'supplementary_data' => ['related_ids' => ['order_id' => 'ORDER_FALLBACK']],
    ], 'WH-FB');

    $response = app(PaymentController::class)->webhook(paypalSignedRequest($event), 'paypal');

    expect($response->getStatusCode())->toBe(200);
    expect($sub->fresh()->payment_status)->toBe('paid');
});

it('paypal webhook PAYMENT.CAPTURE.COMPLETED is idempotent — duplicate event_id returns 200 duplicate', function () {
    $plan = seedPayPalPlan();
    $sub = seedPayPalSubscription(7700, $plan->id);

    Http::fake([
        'api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS'], 200),
    ]);

    $event = paypalEvent('PAYMENT.CAPTURE.COMPLETED', [
        'id' => 'CAP_DUP',
        'status' => 'COMPLETED',
        'custom_id' => (string) $sub->id,
    ], 'WH-DUP');

    $r1 = app(PaymentController::class)->webhook(paypalSignedRequest($event), 'paypal');
    expect($r1->getStatusCode())->toBe(200);
    expect($r1->getData(true)['status'])->toBe('processed');

    $r2 = app(PaymentController::class)->webhook(paypalSignedRequest($event), 'paypal');
    expect($r2->getStatusCode())->toBe(200);
    expect($r2->getData(true)['status'])->toBe('duplicate');

    expect(WebhookEvent::where('event_id', 'WH-DUP')->count())->toBe(1);
    expect(UserMembership::where('user_id', 7700)->count())->toBe(1);
});

it('paypal webhook PAYMENT.CAPTURE.COMPLETED returns 200 ignored when subscription not found', function () {
    Http::fake([
        'api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS'], 200),
    ]);

    $event = paypalEvent('PAYMENT.CAPTURE.COMPLETED', [
        'id' => 'CAP_ORPHAN',
        'status' => 'COMPLETED',
        'custom_id' => '99999',  // no such subscription
    ], 'WH-ORPHAN');

    $response = app(PaymentController::class)->webhook(paypalSignedRequest($event), 'paypal');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('ignored');
});

/* ==================================================================
 |  PayPal webhook — capture failure modes
 | ================================================================== */

it('paypal webhook PAYMENT.CAPTURE.DENIED marks subscription failed', function () {
    $plan = seedPayPalPlan();
    $sub = seedPayPalSubscription(7700, $plan->id);

    Http::fake([
        'api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS'], 200),
    ]);

    $event = paypalEvent('PAYMENT.CAPTURE.DENIED', [
        'id' => 'CAP_DENIED',
        'status' => 'DENIED',
        'custom_id' => (string) $sub->id,
    ], 'WH-DENIED');

    $response = app(PaymentController::class)->webhook(paypalSignedRequest($event), 'paypal');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('processed');
    expect($sub->fresh()->payment_status)->toBe('failed');
});

it('paypal webhook PAYMENT.CAPTURE.DECLINED also marks subscription failed', function () {
    $plan = seedPayPalPlan();
    $sub = seedPayPalSubscription(7700, $plan->id);

    Http::fake([
        'api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS'], 200),
    ]);

    $event = paypalEvent('PAYMENT.CAPTURE.DECLINED', [
        'id' => 'CAP_DECLINED',
        'status' => 'DECLINED',
        'custom_id' => (string) $sub->id,
    ], 'WH-DEC');

    $response = app(PaymentController::class)->webhook(paypalSignedRequest($event), 'paypal');

    expect($response->getStatusCode())->toBe(200);
    expect($sub->fresh()->payment_status)->toBe('failed');
});

/* ==================================================================
 |  PayPal webhook — PAYMENT.CAPTURE.REFUNDED
 | ================================================================== */

it('paypal webhook PAYMENT.CAPTURE.REFUNDED marks subscription refunded + deactivates membership', function () {
    $plan = seedPayPalPlan();
    $sub = seedPayPalSubscription(7700, $plan->id, 'ORDER_REF', [
        'gateway_metadata' => [
            'paypal_order_id' => 'ORDER_REF',
            'paypal_capture_id' => 'CAPTURE_TO_REFUND',
            'paypal_status' => 'COMPLETED',
        ],
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

    Http::fake([
        'api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS'], 200),
    ]);

    // PayPal refund event: resource is the refund, captured payment is
    // referenced via supplementary_data.related_ids.capture_id.
    $event = paypalEvent('PAYMENT.CAPTURE.REFUNDED', [
        'id' => 'REFUND_X',
        'status' => 'COMPLETED',
        'amount' => ['currency_code' => 'USD', 'value' => '999.00'],
        'supplementary_data' => ['related_ids' => ['capture_id' => 'CAPTURE_TO_REFUND']],
    ], 'WH-REFUND');

    $response = app(PaymentController::class)->webhook(paypalSignedRequest($event), 'paypal');

    expect($response->getStatusCode())->toBe(200);
    expect($sub->fresh()->payment_status)->toBe('refunded');
    expect(UserMembership::where('user_id', 7700)->first()->is_active)->toBeFalse();
});

it('paypal webhook PAYMENT.CAPTURE.REFUNDED falls back to links rel=up when supplementary_data missing', function () {
    $plan = seedPayPalPlan();
    $sub = seedPayPalSubscription(7700, $plan->id, 'ORDER_X', [
        'gateway_metadata' => [
            'paypal_order_id' => 'ORDER_X',
            'paypal_capture_id' => 'CAP_LINKS',
            'paypal_status' => 'COMPLETED',
        ],
        'payment_status' => 'paid',
        'is_active' => true,
    ]);

    Http::fake([
        'api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS'], 200),
    ]);

    $event = paypalEvent('PAYMENT.CAPTURE.REFUNDED', [
        'id' => 'REFUND_LINKS',
        'status' => 'COMPLETED',
        'links' => [
            ['rel' => 'self', 'href' => 'https://api.../refunds/REFUND_LINKS'],
            ['rel' => 'up', 'href' => 'https://api.sandbox.paypal.com/v2/payments/captures/CAP_LINKS'],
        ],
    ], 'WH-REF-LINKS');

    $response = app(PaymentController::class)->webhook(paypalSignedRequest($event), 'paypal');

    expect($response->getStatusCode())->toBe(200);
    expect($sub->fresh()->payment_status)->toBe('refunded');
});

/* ==================================================================
 |  PayPal webhook — unknown event types
 | ================================================================== */

it('paypal webhook returns 200 ignored for unknown event types (e.g. CHECKOUT.ORDER.APPROVED)', function () {
    Http::fake([
        'api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS'], 200),
    ]);

    $event = paypalEvent('CHECKOUT.ORDER.APPROVED', [
        'id' => 'ORDER_APPROVED',
        'status' => 'APPROVED',
    ], 'WH-APPROVED');

    $response = app(PaymentController::class)->webhook(paypalSignedRequest($event), 'paypal');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('ignored');

    $row = WebhookEvent::where('event_id', 'WH-APPROVED')->first();
    expect($row)->not->toBeNull();
    expect($row->status)->toBe('ignored');
});
