<?php

use App\Http\Controllers\Api\V1\PaymentController;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserMembership;
use App\Models\WebhookEvent;
use App\Services\Payment\PaytmService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Paytm — service + webhook (S2S callback) tests
|--------------------------------------------------------------------------
| Covers the same shape as the other gateways but adapted to Paytm's
| quirks:
|
|   1. PaytmService unit slice — isConfigured, createOrder (Http::fake
|      on /theia/api/v1/initiateTransaction), verifyPayment via
|      /v3/order/status with resultCode '01' = success.
|
|   2. Checksum math — round-trip via the public generateSignature /
|      verifyCallbackSignature so we know the AES-128-CBC + SHA256
|      pipeline is correct end-to-end.
|
|   3. Webhook (S2S callback) — form-encoded body, CHECKSUMHASH
|      signature, dispatch on STATUS=TXN_SUCCESS / TXN_FAILURE / refund.
|      Subscription lookup by paytm_order_id in gateway_metadata.
|
|   4. Idempotency — duplicate TXNID → 200 'duplicate'.
|
| Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-05-razorpay-webhook.md
| (Paytm addition — step-05c)
*/

function createPaytmTables(): void
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

function dropPaytmTables(): void
{
    Schema::dropIfExists('webhook_events');
    Schema::dropIfExists('user_memberships');
    Schema::dropIfExists('subscriptions');
    Schema::dropIfExists('membership_plans');
}

function seedPaytmPlan(array $overrides = []): MembershipPlan
{
    return MembershipPlan::create(array_merge([
        'plan_name' => 'Paytm Plan',
        'slug' => 'paytm-plan-'.uniqid(),
        'duration_months' => 6,
        'price_inr' => 999,
        'is_active' => true,
    ], $overrides));
}

/** Pending Paytm subscription with paytm_order_id in gateway_metadata. */
function seedPaytmSubscription(int $userId, int $planId, string $orderId = 'sub_1_xx', array $overrides = []): Subscription
{
    return Subscription::create(array_merge([
        'user_id' => $userId,
        'plan_id' => $planId,
        'gateway' => 'paytm',
        'gateway_metadata' => ['paytm_order_id' => $orderId],
        'amount' => 99900,
        'original_amount' => 99900,
        'payment_status' => 'pending',
    ], $overrides));
}

/**
 * Build a signed Paytm callback Request. Uses the SAME checksum code
 * as production (PaytmService::generateSignature applied to the
 * canonical pipe-joined string), so signature verification round-trips.
 */
function paytmCallbackRequest(array $params, string $key): Request
{
    /** @var \App\Services\Payment\PaytmService $service */
    $service = app(PaytmService::class);
    // Build the same pipe-string the verifier will reconstruct, then
    // sign it with generateSignature (which adds salt + AES + base64).
    $string = $service->buildCallbackString($params);
    $checksum = $service->generateSignature($string, $key);
    $params['CHECKSUMHASH'] = $checksum;

    return Request::create('/api/v1/webhooks/paytm', 'POST', $params, [], [], [
        'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
    ]);
}

beforeEach(function () {
    createPaytmTables();
    config([
        'services.paytm.mid' => 'TestMid12345',
        // 16 chars — Paytm merchant keys are usually exactly 16 chars; AES-128-CBC needs a 16-byte key.
        'services.paytm.key' => 'merchant-key-xx!',
        'services.paytm.mode' => 'sandbox',
        'services.paytm.website' => 'WEBSTAGING',
        'services.paytm.industry_type' => 'Retail',
        'services.paytm.channel_id' => 'WAP',
    ]);
});

afterEach(function () {
    dropPaytmTables();
});

/* ==================================================================
 |  PaytmService — isConfigured + checksum math
 | ================================================================== */

it('paytm isConfigured returns true when all credentials present', function () {
    expect(app(PaytmService::class)->isConfigured())->toBeTrue();
});

it('paytm isConfigured returns false when merchant key is missing', function () {
    config(['services.paytm.key' => null]);
    expect(app(PaytmService::class)->isConfigured())->toBeFalse();
});

it('paytm isConfigured returns false when mid is missing', function () {
    config(['services.paytm.mid' => null]);
    expect(app(PaytmService::class)->isConfigured())->toBeFalse();
});

it('paytm checksum round-trips: generateSignature / verifyCallbackSignature agree', function () {
    $service = app(PaytmService::class);
    $key = 'merchant-key-xx!';
    $params = [
        'MID' => 'TestMid12345',
        'ORDERID' => 'sub_1_xxx',
        'TXNID' => 'TXN_TEST_001',
        'TXNAMOUNT' => '999.00',
        'STATUS' => 'TXN_SUCCESS',
        'BANKTXNID' => 'BANKTXN_X',
        'PAYMENTMODE' => 'UPI',
    ];

    // Sign the canonical pipe-joined string the same way the verifier reconstructs it.
    $checksum = $service->generateSignature($service->buildCallbackString($params), $key);
    $params['CHECKSUMHASH'] = $checksum;

    expect($service->verifyCallbackSignature($params, $key, $checksum))->toBeTrue();

    // Tamper with a field — must reject.
    $tampered = $params;
    $tampered['TXNAMOUNT'] = '0.01';
    expect($service->verifyCallbackSignature($tampered, $key, $checksum))->toBeFalse();
});

/* ==================================================================
 |  PaytmService — createOrder
 | ================================================================== */

it('paytm createOrder posts initiateTransaction + returns Flutter-friendly payload', function () {
    Http::fake([
        'securegw-stage.paytm.in/theia/api/v1/initiateTransaction*' => Http::response([
            'head' => ['responseTimestamp' => '1234', 'version' => 'v1', 'signature' => 'xyz'],
            'body' => [
                'resultInfo' => ['resultStatus' => 'S', 'resultCode' => '0000', 'resultMsg' => 'Success'],
                'txnToken' => 'TXNTOKEN_ABCDEF123456',
            ],
        ], 200),
    ]);

    $payload = app(PaytmService::class)->createOrder(99900, [
        'subscription_id' => 12,
        'user_id' => 7,
    ]);

    expect($payload['paytm_order_id'])->toStartWith('sub_12_');
    expect($payload['txn_token'])->toBe('TXNTOKEN_ABCDEF123456');
    expect($payload['mid'])->toBe('TestMid12345');
    expect($payload['amount'])->toBe('999.00');
    expect($payload['currency'])->toBe('INR');
    expect($payload['channel_id'])->toBe('WAP');
    expect($payload['mode'])->toBe('sandbox');

    Http::assertSent(function ($req) {
        return str_contains($req->url(), 'initiateTransaction')
            && str_contains($req->url(), 'mid=TestMid12345')
            && $req['body']['mid'] === 'TestMid12345'
            && $req['body']['websiteName'] === 'WEBSTAGING'
            && $req['body']['txnAmount']['value'] === '999.00'
            && $req['body']['txnAmount']['currency'] === 'INR'
            && ! empty($req['head']['signature']);
    });
});

it('paytm createOrder uses production base URL when mode=production', function () {
    config(['services.paytm.mode' => 'production']);

    Http::fake([
        'securegw.paytm.in/theia/api/v1/initiateTransaction*' => Http::response([
            'head' => [],
            'body' => [
                'resultInfo' => ['resultStatus' => 'S'],
                'txnToken' => 'TOKEN',
            ],
        ], 200),
    ]);

    app(PaytmService::class)->createOrder(99900, []);

    Http::assertSent(function ($req) {
        return str_starts_with($req->url(), 'https://securegw.paytm.in/')
            && ! str_contains($req->url(), 'stage');
    });
});

it('paytm createOrder throws when API returns HTTP error', function () {
    Http::fake([
        'securegw-stage.paytm.in/*' => Http::response(['error' => 'server'], 500),
    ]);

    expect(fn () => app(PaytmService::class)->createOrder(99900, []))
        ->toThrow(\RuntimeException::class);
});

it('paytm createOrder throws when initiate returns non-success resultStatus', function () {
    Http::fake([
        'securegw-stage.paytm.in/*' => Http::response([
            'head' => [],
            'body' => [
                'resultInfo' => ['resultStatus' => 'F', 'resultCode' => '501', 'resultMsg' => 'system error'],
            ],
        ], 200),
    ]);

    expect(fn () => app(PaytmService::class)->createOrder(99900, []))
        ->toThrow(\RuntimeException::class);
});

/* ==================================================================
 |  PaytmService — verifyPayment
 | ================================================================== */

it('paytm verifyPayment returns true when status API resultCode=01', function () {
    Http::fake([
        'securegw-stage.paytm.in/v3/order/status' => Http::response([
            'head' => [],
            'body' => [
                'resultInfo' => ['resultStatus' => 'TXN_SUCCESS', 'resultCode' => '01', 'resultMsg' => 'Txn Success'],
                'orderId' => 'sub_1_xxx',
                'txnId' => 'TXN_X',
            ],
        ], 200),
    ]);

    $ok = app(PaytmService::class)->verifyPayment(['paytm_order_id' => 'sub_1_xxx']);

    expect($ok)->toBeTrue();
});

it('paytm verifyPayment returns false when resultCode is not 01', function () {
    Http::fake([
        'securegw-stage.paytm.in/v3/order/status' => Http::response([
            'head' => [],
            'body' => [
                'resultInfo' => ['resultStatus' => 'TXN_FAILURE', 'resultCode' => '227', 'resultMsg' => 'Failure'],
            ],
        ], 200),
    ]);

    expect(app(PaytmService::class)->verifyPayment(['paytm_order_id' => 'sub_1_xxx']))->toBeFalse();
});

it('paytm verifyPayment returns false when paytm_order_id is empty', function () {
    expect(app(PaytmService::class)->verifyPayment([]))->toBeFalse();
});

/* ==================================================================
 |  Paytm webhook — auth + dispatch
 | ================================================================== */

it('paytm webhook returns 503 when not configured', function () {
    config(['services.paytm.key' => null]);

    $request = Request::create('/api/v1/webhooks/paytm', 'POST', ['ORDERID' => 'x']);

    $response = app(PaymentController::class)->webhook($request, 'paytm');

    expect($response->getStatusCode())->toBe(503);
});

it('paytm webhook returns 401 when CHECKSUMHASH is missing', function () {
    $request = Request::create('/api/v1/webhooks/paytm', 'POST', [
        'ORDERID' => 'sub_1_xxx',
        'TXNID' => 'TXN_X',
        'STATUS' => 'TXN_SUCCESS',
    ]);

    $response = app(PaymentController::class)->webhook($request, 'paytm');

    expect($response->getStatusCode())->toBe(401);
});

it('paytm webhook returns 401 when CHECKSUMHASH does not match', function () {
    $request = Request::create('/api/v1/webhooks/paytm', 'POST', [
        'ORDERID' => 'sub_1_xxx',
        'TXNID' => 'TXN_X',
        'STATUS' => 'TXN_SUCCESS',
        'CHECKSUMHASH' => 'AAAA-WRONG-CHECKSUM-AAAA',  // base64-decoded would be junk
    ]);

    $response = app(PaymentController::class)->webhook($request, 'paytm');

    expect($response->getStatusCode())->toBe(401);
});

it('paytm webhook returns 422 when ORDERID is missing', function () {
    $key = 'merchant-key-xx!';
    $params = [
        'TXNID' => 'TXN_X',
        'STATUS' => 'TXN_SUCCESS',
    ];

    $request = paytmCallbackRequest($params, $key);

    $response = app(PaymentController::class)->webhook($request, 'paytm');

    expect($response->getStatusCode())->toBe(422);
});

/* ==================================================================
 |  Paytm webhook — payment dispatch
 | ================================================================== */

it('paytm webhook STATUS=TXN_SUCCESS marks subscription paid + creates membership', function () {
    $plan = seedPaytmPlan(['duration_months' => 6]);
    $sub = seedPaytmSubscription(7700, $plan->id, 'sub_42_X');

    $params = [
        'MID' => 'TestMid12345',
        'ORDERID' => 'sub_42_X',
        'TXNID' => 'TXN_001',
        'TXNAMOUNT' => '999.00',
        'STATUS' => 'TXN_SUCCESS',
        'RESPCODE' => '01',
        'RESPMSG' => 'Txn Success',
        'BANKTXNID' => 'BANK_TXN_001',
        'PAYMENTMODE' => 'UPI',
        'CURRENCY' => 'INR',
    ];

    $request = paytmCallbackRequest($params, 'merchant-key-xx!');

    $response = app(PaymentController::class)->webhook($request, 'paytm');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('processed');

    $sub->refresh();
    expect($sub->payment_status)->toBe('paid');
    expect($sub->is_active)->toBeTrue();
    expect($sub->gateway_metadata['paytm_txn_id'])->toBe('TXN_001');
    expect($sub->gateway_metadata['paytm_bank_txn_id'])->toBe('BANK_TXN_001');
    expect($sub->gateway_metadata['paytm_payment_mode'])->toBe('UPI');
    expect($sub->gateway_metadata['paytm_status'])->toBe('TXN_SUCCESS');
    expect(UserMembership::where('user_id', 7700)->where('is_active', true)->exists())->toBeTrue();
});

it('paytm webhook STATUS=TXN_FAILURE marks subscription failed', function () {
    $plan = seedPaytmPlan();
    $sub = seedPaytmSubscription(7700, $plan->id, 'sub_42_F');

    $params = [
        'MID' => 'TestMid12345',
        'ORDERID' => 'sub_42_F',
        'TXNID' => 'TXN_FAIL_001',
        'TXNAMOUNT' => '999.00',
        'STATUS' => 'TXN_FAILURE',
        'RESPCODE' => '227',
        'RESPMSG' => 'Bank declined',
    ];

    $request = paytmCallbackRequest($params, 'merchant-key-xx!');

    $response = app(PaymentController::class)->webhook($request, 'paytm');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('processed');
    expect($sub->fresh()->payment_status)->toBe('failed');
});

it('paytm webhook STATUS=PENDING returns 200 ignored', function () {
    $plan = seedPaytmPlan();
    $sub = seedPaytmSubscription(7700, $plan->id, 'sub_42_P');

    $params = [
        'MID' => 'TestMid12345',
        'ORDERID' => 'sub_42_P',
        'TXNID' => 'TXN_PENDING',
        'TXNAMOUNT' => '999.00',
        'STATUS' => 'PENDING',
    ];

    $request = paytmCallbackRequest($params, 'merchant-key-xx!');

    $response = app(PaymentController::class)->webhook($request, 'paytm');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('ignored');
    expect($sub->fresh()->payment_status)->toBe('pending');
});

it('paytm webhook is idempotent — duplicate TXNID returns 200 duplicate', function () {
    $plan = seedPaytmPlan();
    $sub = seedPaytmSubscription(7700, $plan->id, 'sub_42_DUP');

    $params = [
        'MID' => 'TestMid12345',
        'ORDERID' => 'sub_42_DUP',
        'TXNID' => 'TXN_DUP_001',
        'TXNAMOUNT' => '999.00',
        'STATUS' => 'TXN_SUCCESS',
        'PAYMENTMODE' => 'UPI',
    ];

    $r1 = app(PaymentController::class)->webhook(paytmCallbackRequest($params, 'merchant-key-xx!'), 'paytm');
    expect($r1->getStatusCode())->toBe(200);
    expect($r1->getData(true)['status'])->toBe('processed');

    $r2 = app(PaymentController::class)->webhook(paytmCallbackRequest($params, 'merchant-key-xx!'), 'paytm');
    expect($r2->getStatusCode())->toBe(200);
    expect($r2->getData(true)['status'])->toBe('duplicate');

    expect(WebhookEvent::where('event_id', 'TXN_DUP_001')->count())->toBe(1);
    expect(UserMembership::where('user_id', 7700)->count())->toBe(1);
});

it('paytm webhook returns 200 ignored when subscription not found for ORDERID', function () {
    $params = [
        'MID' => 'TestMid12345',
        'ORDERID' => 'sub_99999_orphan',
        'TXNID' => 'TXN_ORPHAN',
        'STATUS' => 'TXN_SUCCESS',
    ];

    $response = app(PaymentController::class)->webhook(paytmCallbackRequest($params, 'merchant-key-xx!'), 'paytm');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('ignored');
});

/* ==================================================================
 |  Paytm webhook — refund dispatch
 | ================================================================== */

it('paytm webhook with REFUNDID + STATUS=TXN_SUCCESS marks subscription refunded', function () {
    $plan = seedPaytmPlan();
    $sub = seedPaytmSubscription(7700, $plan->id, 'sub_42_R', [
        'gateway_metadata' => [
            'paytm_order_id' => 'sub_42_R',
            'paytm_txn_id' => 'TXN_PAID',
            'paytm_status' => 'TXN_SUCCESS',
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

    $params = [
        'MID' => 'TestMid12345',
        'ORDERID' => 'sub_42_R',
        'TXNID' => 'TXN_PAID',
        'REFUNDID' => 'REFUND_001',
        'TXNAMOUNT' => '999.00',
        'STATUS' => 'TXN_SUCCESS',
    ];

    $response = app(PaymentController::class)->webhook(paytmCallbackRequest($params, 'merchant-key-xx!'), 'paytm');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('processed');

    $sub->refresh();
    expect($sub->payment_status)->toBe('refunded');
    expect($sub->gateway_metadata['paytm_refund_id'])->toBe('REFUND_001');
    expect($sub->gateway_metadata['paytm_status'])->toBe('REFUND_SUCCESS');
    expect(UserMembership::where('user_id', 7700)->first()->is_active)->toBeFalse();
});

it('paytm webhook refund + payment dedupe by separate event_ids — both fire', function () {
    $plan = seedPaytmPlan();
    $sub = seedPaytmSubscription(7700, $plan->id, 'sub_42_DEDUPE');

    // Payment first.
    $payment = [
        'MID' => 'TestMid12345',
        'ORDERID' => 'sub_42_DEDUPE',
        'TXNID' => 'TXN_PAY_X',
        'TXNAMOUNT' => '999.00',
        'STATUS' => 'TXN_SUCCESS',
        'PAYMENTMODE' => 'UPI',
    ];
    $r1 = app(PaymentController::class)->webhook(paytmCallbackRequest($payment, 'merchant-key-xx!'), 'paytm');
    expect($r1->getData(true)['status'])->toBe('processed');

    // Then refund — different event_id (REFUNDID), should NOT dedupe.
    $refund = [
        'MID' => 'TestMid12345',
        'ORDERID' => 'sub_42_DEDUPE',
        'TXNID' => 'TXN_PAY_X',
        'REFUNDID' => 'REFUND_X',
        'TXNAMOUNT' => '999.00',
        'STATUS' => 'TXN_SUCCESS',
    ];
    $r2 = app(PaymentController::class)->webhook(paytmCallbackRequest($refund, 'merchant-key-xx!'), 'paytm');
    expect($r2->getData(true)['status'])->toBe('processed');

    expect($sub->fresh()->payment_status)->toBe('refunded');
    // Two webhook rows — one for the payment TXNID, one for the REFUNDID.
    expect(WebhookEvent::where('gateway', 'paytm')->count())->toBe(2);
});
