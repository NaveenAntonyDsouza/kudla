<?php

use App\Http\Controllers\Api\V1\PaymentController;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserMembership;
use App\Models\WebhookEvent;
use App\Services\Payment\PhonePeService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| PhonePe V2 Standard Checkout — service + webhook tests
|--------------------------------------------------------------------------
| Same shape as the other gateway tests, adapted for V2 specifics:
|
|   1. PhonePeService unit slice — isConfigured, createOrder
|      (Http::fake on /checkout/v2/pay, OAuth pre-cached), verifyPayment
|      via /checkout/v2/order/{id}/status state==='COMPLETED'.
|
|   2. OAuth flow — token fetched on cache miss; expires_at drives TTL.
|
|   3. Webhook auth — Authorization: SHA256(username:password) compared
|      with hash_equals; 503 / 401 / 422 paths.
|
|   4. Webhook dispatch — checkout.order.completed → activate (lookup
|      by phonepe_merchant_order_id in gateway_metadata), .failed →
|      markFailed, pg.refund.completed → markRefunded, pg.refund.failed
|      → ignored.
|
|   5. Idempotency — duplicate transactionId+event → 200 'duplicate'.
|
| Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-05-razorpay-webhook.md
| (PhonePe V2 addition — step-05d)
*/

function createPhonePeTables(): void
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

function dropPhonePeTables(): void
{
    Schema::dropIfExists('webhook_events');
    Schema::dropIfExists('user_memberships');
    Schema::dropIfExists('subscriptions');
    Schema::dropIfExists('membership_plans');
}

function seedPhonePePlan(array $overrides = []): MembershipPlan
{
    return MembershipPlan::create(array_merge([
        'plan_name' => 'PhonePe Plan',
        'slug' => 'phonepe-plan-'.uniqid(),
        'duration_months' => 6,
        'price_inr' => 999,
        'is_active' => true,
    ], $overrides));
}

/** Pending PhonePe subscription with phonepe_merchant_order_id in gateway_metadata. */
function seedPhonePeSubscription(int $userId, int $planId, string $merchantOrderId = 'phonepe_1_xx', array $overrides = []): Subscription
{
    return Subscription::create(array_merge([
        'user_id' => $userId,
        'plan_id' => $planId,
        'gateway' => 'phonepe',
        'gateway_metadata' => ['phonepe_merchant_order_id' => $merchantOrderId],
        'amount' => 99900,
        'original_amount' => 99900,
        'payment_status' => 'pending',
    ], $overrides));
}

/** Build a PhonePe webhook event payload (top-level shape). */
function phonepeEvent(string $type, array $payload): array
{
    return [
        'event' => $type,
        'payload' => $payload,
    ];
}

/**
 * Build a request signed with the PhonePe webhook auth header
 * (SHA256(username:password) hex). Optionally tampered to test rejection.
 */
function phonepeSignedRequest(array $event, string $username = 'webhook_user', string $password = 'webhook_pass', ?string $forceAuthHeader = null): Request
{
    $auth = $forceAuthHeader ?? hash('sha256', $username.':'.$password);

    return Request::create('/api/v1/webhooks/phonepe', 'POST', [], [], [], [
        'HTTP_Authorization' => $auth,
        'CONTENT_TYPE' => 'application/json',
    ], json_encode($event));
}

beforeEach(function () {
    createPhonePeTables();
    config([
        'services.phonepe.client_id' => 'pp_client_id_xxx',
        'services.phonepe.client_secret' => 'pp_client_secret_xxx',
        'services.phonepe.client_version' => '1',
        'services.phonepe.mode' => 'sandbox',
        'services.phonepe.webhook_username' => 'webhook_user',
        'services.phonepe.webhook_password' => 'webhook_pass',
    ]);
    // Pre-populate OAuth token to keep Http::fake() blocks focused on
    // the API calls under test (same trick used in PayPal tests).
    Cache::put('phonepe_access_token_sandbox', 'O-BEARER-TOKEN-XXX', 3600);
});

afterEach(function () {
    Cache::forget('phonepe_access_token_sandbox');
    Cache::forget('phonepe_access_token_production');
    dropPhonePeTables();
});

/* ==================================================================
 |  PhonePeService — isConfigured + createOrder + verifyPayment
 | ================================================================== */

it('phonepe isConfigured returns true when client_id, secret, version present', function () {
    expect(app(PhonePeService::class)->isConfigured())->toBeTrue();
});

it('phonepe isConfigured returns false when client_secret missing', function () {
    config(['services.phonepe.client_secret' => null]);
    expect(app(PhonePeService::class)->isConfigured())->toBeFalse();
});

it('phonepe createOrder posts /checkout/v2/pay + returns Flutter-friendly payload', function () {
    Http::fake([
        'api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/pay' => Http::response([
            'orderId' => 'OMO123456789',
            'state' => 'PENDING',
            'expireAt' => 1703756259307,
            'redirectUrl' => 'https://mercury-uat.phonepe.com/transact/uat_v2?token=eyJabc',
        ], 200),
    ]);

    $payload = app(PhonePeService::class)->createOrder(99900, [
        'subscription_id' => 12,
        'user_id' => 7,
        'plan_id' => 3,
        'plan_name' => 'Diamond',
    ]);

    expect($payload['phonepe_merchant_order_id'])->toStartWith('phonepe_12_');
    expect($payload['phonepe_order_id'])->toBe('OMO123456789');
    expect($payload['redirect_url'])->toContain('phonepe.com/transact');
    expect($payload['state'])->toBe('PENDING');
    expect($payload['amount'])->toBe(99900);
    expect($payload['currency'])->toBe('INR');
    expect($payload['mode'])->toBe('sandbox');

    Http::assertSent(function ($req) {
        return str_contains($req->url(), '/checkout/v2/pay')
            && $req->method() === 'POST'
            && $req['amount'] === 99900
            && $req['paymentFlow']['type'] === 'PG_CHECKOUT'
            && $req->hasHeader('Authorization', 'O-Bearer O-BEARER-TOKEN-XXX')
            && $req['metaInfo']['udf1'] === '7'
            && $req['metaInfo']['udf5'] === '12';
    });
});

it('phonepe createOrder uses production base URL when mode=production', function () {
    config(['services.phonepe.mode' => 'production']);
    Cache::put('phonepe_access_token_production', 'PROD-TOKEN', 3600);

    Http::fake([
        'api.phonepe.com/apis/pg/checkout/v2/pay' => Http::response([
            'orderId' => 'OMO_X',
            'state' => 'PENDING',
            'expireAt' => 0,
            'redirectUrl' => 'https://phonepe.com/x',
        ], 200),
    ]);

    app(PhonePeService::class)->createOrder(99900, []);

    Http::assertSent(function ($req) {
        return str_starts_with($req->url(), 'https://api.phonepe.com/apis/pg/')
            && ! str_contains($req->url(), 'sandbox');
    });
});

it('phonepe createOrder throws on API error', function () {
    Http::fake([
        'api-preprod.phonepe.com/*' => Http::response(['error' => 'bad request'], 400),
    ]);

    expect(fn () => app(PhonePeService::class)->createOrder(99900, []))
        ->toThrow(\RuntimeException::class);
});

it('phonepe createOrder throws when redirectUrl missing in response', function () {
    Http::fake([
        'api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/pay' => Http::response([
            'orderId' => 'X',
            'state' => 'PENDING',
            // no redirectUrl
        ], 200),
    ]);

    expect(fn () => app(PhonePeService::class)->createOrder(99900, []))
        ->toThrow(\RuntimeException::class);
});

it('phonepe createOrder triggers OAuth fetch when token cache empty', function () {
    Cache::forget('phonepe_access_token_sandbox');

    $futureExpiresAt = time() + 3000;
    Http::fake([
        'api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token' => Http::response([
            'access_token' => 'NEWLY-FETCHED-TOKEN',
            'token_type' => 'O-Bearer',
            'expires_at' => $futureExpiresAt,
            'issued_at' => time(),
        ], 200),
        'api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/pay' => Http::response([
            'orderId' => 'X', 'state' => 'PENDING', 'redirectUrl' => 'https://x',
        ], 200),
    ]);

    app(PhonePeService::class)->createOrder(99900, []);

    Http::assertSent(fn ($req) => str_contains($req->url(), '/v1/oauth/token'));
    Http::assertSent(function ($req) {
        return str_contains($req->url(), '/checkout/v2/pay')
            && $req->hasHeader('Authorization', 'O-Bearer NEWLY-FETCHED-TOKEN');
    });
    expect(Cache::get('phonepe_access_token_sandbox'))->toBe('NEWLY-FETCHED-TOKEN');
});

it('phonepe verifyPayment GETs order status + returns true on COMPLETED', function () {
    Http::fake([
        'api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/order/MOI_X/status' => Http::response([
            'orderId' => 'OMO_X',
            'state' => 'COMPLETED',
            'amount' => 99900,
            'paymentDetails' => [['transactionId' => 'TX_X', 'state' => 'COMPLETED']],
        ], 200),
    ]);

    $ok = app(PhonePeService::class)->verifyPayment(
        ['phonepe_merchant_order_id' => 'MOI_X'],
        phonepeSubscription('MOI_X'),
    );

    expect($ok)->toBeTrue();
});

it('phonepe verifyPayment returns false when state is not COMPLETED', function () {
    Http::fake([
        'api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/order/MOI_X/status' => Http::response([
            'orderId' => 'OMO_X',
            'state' => 'PENDING',
        ], 200),
    ]);

    expect(app(PhonePeService::class)->verifyPayment(
        ['phonepe_merchant_order_id' => 'MOI_X'],
        phonepeSubscription('MOI_X'),
    ))->toBeFalse();
});

it('phonepe verifyPayment returns false when phonepe_merchant_order_id is empty', function () {
    expect(app(PhonePeService::class)->verifyPayment([], phonepeSubscription('MOI_X')))->toBeFalse();
});

it('phonepe verifyPayment REJECTS replay across subscriptions (Vuln 1 anti-substitution)', function () {
    Http::fake([
        'api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/order/MOI_PAID/status' => Http::response([
            'orderId' => 'MOI_PAID',
            'state' => 'COMPLETED',
        ], 200),
    ]);

    expect(app(PhonePeService::class)->verifyPayment(
        ['phonepe_merchant_order_id' => 'MOI_PAID'],
        phonepeSubscription('MOI_OTHER'),
    ))->toBeFalse();
});

/**
 * Helper: in-memory Subscription with phonepe_merchant_order_id in metadata.
 * (Phase 2a Vuln 1.)
 */
function phonepeSubscription(string $merchantOrderId = 'MOI_X'): \App\Models\Subscription
{
    $sub = new \App\Models\Subscription();
    $sub->forceFill([
        'id' => 1,
        'gateway_metadata' => ['phonepe_merchant_order_id' => $merchantOrderId],
    ]);
    return $sub;
}

/* ==================================================================
 |  PhonePe webhook — auth
 | ================================================================== */

it('phonepe webhook returns 503 when webhook credentials are not configured', function () {
    config(['services.phonepe.webhook_password' => null]);

    $request = phonepeSignedRequest(phonepeEvent('checkout.order.completed', ['merchantOrderId' => 'X']));

    $response = app(PaymentController::class)->webhook($request, 'phonepe');

    expect($response->getStatusCode())->toBe(503);
});

it('phonepe webhook returns 401 when Authorization header missing', function () {
    $body = json_encode(phonepeEvent('checkout.order.completed', ['merchantOrderId' => 'X']));
    $request = Request::create('/api/v1/webhooks/phonepe', 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], $body);

    $response = app(PaymentController::class)->webhook($request, 'phonepe');

    expect($response->getStatusCode())->toBe(401);
});

it('phonepe webhook returns 401 when Authorization hash does not match', function () {
    $request = phonepeSignedRequest(
        phonepeEvent('checkout.order.completed', ['merchantOrderId' => 'X']),
        forceAuthHeader: 'wrong-hash-value-here',
    );

    $response = app(PaymentController::class)->webhook($request, 'phonepe');

    expect($response->getStatusCode())->toBe(401);
});

it('phonepe webhook returns 422 on malformed JSON', function () {
    $request = Request::create('/api/v1/webhooks/phonepe', 'POST', [], [], [], [
        'HTTP_Authorization' => hash('sha256', 'webhook_user:webhook_pass'),
        'CONTENT_TYPE' => 'application/json',
    ], 'not-json');

    $response = app(PaymentController::class)->webhook($request, 'phonepe');

    expect($response->getStatusCode())->toBe(422);
});

it('phonepe webhook returns 422 when merchantOrderId missing', function () {
    $request = phonepeSignedRequest(phonepeEvent('checkout.order.completed', ['orderId' => 'OMO_X']));

    $response = app(PaymentController::class)->webhook($request, 'phonepe');

    expect($response->getStatusCode())->toBe(422);
});

/* ==================================================================
 |  PhonePe webhook — checkout.order.completed
 | ================================================================== */

it('phonepe webhook checkout.order.completed activates subscription', function () {
    $plan = seedPhonePePlan(['duration_months' => 6]);
    $sub = seedPhonePeSubscription(7700, $plan->id, 'phonepe_42_001');

    $event = phonepeEvent('checkout.order.completed', [
        'orderId' => 'OMO_TEST_001',
        'merchantId' => 'M_X',
        'merchantOrderId' => 'phonepe_42_001',
        'state' => 'COMPLETED',
        'amount' => 99900,
        'paymentDetails' => [
            [
                'paymentMode' => 'UPI_INTENT',
                'transactionId' => 'TX_001',
                'state' => 'COMPLETED',
                'amount' => 99900,
            ],
        ],
    ]);

    $response = app(PaymentController::class)->webhook(phonepeSignedRequest($event), 'phonepe');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('processed');

    $sub->refresh();
    expect($sub->payment_status)->toBe('paid');
    expect($sub->is_active)->toBeTrue();
    expect($sub->gateway_metadata['phonepe_transaction_id'])->toBe('TX_001');
    expect($sub->gateway_metadata['phonepe_payment_mode'])->toBe('UPI_INTENT');
    expect($sub->gateway_metadata['phonepe_state'])->toBe('COMPLETED');
    expect(UserMembership::where('user_id', 7700)->where('is_active', true)->exists())->toBeTrue();
});

it('phonepe webhook checkout.order.completed is idempotent — duplicate transactionId returns 200 duplicate', function () {
    $plan = seedPhonePePlan();
    seedPhonePeSubscription(7700, $plan->id, 'phonepe_42_DUP');

    $event = phonepeEvent('checkout.order.completed', [
        'merchantOrderId' => 'phonepe_42_DUP',
        'state' => 'COMPLETED',
        'paymentDetails' => [['transactionId' => 'TX_DUP', 'state' => 'COMPLETED']],
    ]);

    $r1 = app(PaymentController::class)->webhook(phonepeSignedRequest($event), 'phonepe');
    expect($r1->getData(true)['status'])->toBe('processed');

    $r2 = app(PaymentController::class)->webhook(phonepeSignedRequest($event), 'phonepe');
    expect($r2->getData(true)['status'])->toBe('duplicate');

    expect(WebhookEvent::where('gateway', 'phonepe')->count())->toBe(1);
    expect(UserMembership::where('user_id', 7700)->count())->toBe(1);
});

it('phonepe webhook checkout.order.completed returns 200 ignored when subscription not found', function () {
    $event = phonepeEvent('checkout.order.completed', [
        'merchantOrderId' => 'phonepe_orphan_001',
        'state' => 'COMPLETED',
        'paymentDetails' => [['transactionId' => 'TX_ORPHAN', 'state' => 'COMPLETED']],
    ]);

    $response = app(PaymentController::class)->webhook(phonepeSignedRequest($event), 'phonepe');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('ignored');
});

/* ==================================================================
 |  PhonePe webhook — checkout.order.failed
 | ================================================================== */

it('phonepe webhook checkout.order.failed marks subscription failed', function () {
    $plan = seedPhonePePlan();
    $sub = seedPhonePeSubscription(7700, $plan->id, 'phonepe_42_FAIL');

    $event = phonepeEvent('checkout.order.failed', [
        'merchantOrderId' => 'phonepe_42_FAIL',
        'state' => 'FAILED',
        'paymentDetails' => [
            ['transactionId' => 'TX_FAIL', 'state' => 'FAILED', 'errorCode' => 'AUTHORIZATION_DECLINED'],
        ],
    ]);

    $response = app(PaymentController::class)->webhook(phonepeSignedRequest($event), 'phonepe');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('processed');
    expect($sub->fresh()->payment_status)->toBe('failed');
});

/* ==================================================================
 |  PhonePe webhook — refund events
 | ================================================================== */

it('phonepe webhook pg.refund.completed marks subscription refunded + deactivates membership', function () {
    $plan = seedPhonePePlan();
    $sub = seedPhonePeSubscription(7700, $plan->id, 'phonepe_42_REFUND', [
        'gateway_metadata' => [
            'phonepe_merchant_order_id' => 'phonepe_42_REFUND',
            'phonepe_transaction_id' => 'TX_PAID',
            'phonepe_state' => 'COMPLETED',
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

    $event = phonepeEvent('pg.refund.completed', [
        'merchantOrderId' => 'phonepe_42_REFUND',
        'state' => 'COMPLETED',
        'paymentDetails' => [
            ['transactionId' => 'TX_REFUND_001', 'state' => 'COMPLETED'],
        ],
    ]);

    $response = app(PaymentController::class)->webhook(phonepeSignedRequest($event), 'phonepe');

    expect($response->getStatusCode())->toBe(200);
    expect($sub->fresh()->payment_status)->toBe('refunded');
    expect($sub->fresh()->gateway_metadata['phonepe_state'])->toBe('REFUND_COMPLETED');
    expect(UserMembership::where('user_id', 7700)->first()->is_active)->toBeFalse();
});

it('phonepe webhook pg.refund.failed is ignored — does not undo paid state', function () {
    $plan = seedPhonePePlan();
    $sub = seedPhonePeSubscription(7700, $plan->id, 'phonepe_42_REFFAIL', [
        'payment_status' => 'paid',
        'is_active' => true,
    ]);

    $event = phonepeEvent('pg.refund.failed', [
        'merchantOrderId' => 'phonepe_42_REFFAIL',
        'state' => 'FAILED',
        'paymentDetails' => [['transactionId' => 'TX_REFFAIL', 'state' => 'FAILED']],
    ]);

    $response = app(PaymentController::class)->webhook(phonepeSignedRequest($event), 'phonepe');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('ignored');
    expect($sub->fresh()->payment_status)->toBe('paid');  // unchanged

    $row = WebhookEvent::where('event_id', 'TX_REFFAIL:pg.refund.failed')->first();
    expect($row)->not->toBeNull();
    expect($row->status)->toBe('ignored');
});

/* ==================================================================
 |  PhonePe webhook — unknown event types
 | ================================================================== */

it('phonepe webhook returns 200 ignored for unknown event types', function () {
    $plan = seedPhonePePlan();
    seedPhonePeSubscription(7700, $plan->id, 'phonepe_42_UNK');

    $event = phonepeEvent('subscription.updated', [
        'merchantOrderId' => 'phonepe_42_UNK',
        'state' => 'ACTIVE',
        'paymentDetails' => [['transactionId' => 'TX_UNK']],
    ]);

    $response = app(PaymentController::class)->webhook(phonepeSignedRequest($event), 'phonepe');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('ignored');
});

it('phonepe webhook synthesizes event_id from merchantOrderId when transactionId absent', function () {
    $plan = seedPhonePePlan();
    seedPhonePeSubscription(7700, $plan->id, 'phonepe_42_NOTX');

    // Refund event lacking paymentDetails — exercises the fallback id.
    $event = phonepeEvent('pg.refund.failed', [
        'merchantOrderId' => 'phonepe_42_NOTX',
        'state' => 'FAILED',
    ]);

    $response = app(PaymentController::class)->webhook(phonepeSignedRequest($event), 'phonepe');

    expect($response->getStatusCode())->toBe(200);
    $row = WebhookEvent::where('event_id', 'phonepe_42_NOTX:pg.refund.failed')->first();
    expect($row)->not->toBeNull();
});
