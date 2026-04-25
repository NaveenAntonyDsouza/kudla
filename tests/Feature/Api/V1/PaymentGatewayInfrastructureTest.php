<?php

use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\RazorpayService;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Payment-gateway infrastructure tests
|--------------------------------------------------------------------------
| Locks the multi-gateway architecture before any concrete gateway service
| comes to depend on it. Covers:
|
|   - PaymentGatewayManager: register / forSlug / getAll / getConfigured
|   - RazorpayService: signature verification math (offline pure),
|     isConfigured() against config presence/absence, createOrder() via
|     Http::fake() (no real Razorpay API call)
|
| Real Razorpay end-to-end flow is verified by Bruno smoke against
| sandbox keys (Phase 2c launch prep).
|
| Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-04-razorpay-order-verify.md
*/

/* ==================================================================
 |  PaymentGatewayManager
 | ================================================================== */

it('manager forSlug returns the registered gateway', function () {
    $manager = new PaymentGatewayManager();
    $manager->register(new RazorpayService());

    $resolved = $manager->forSlug('razorpay');

    expect($resolved)->toBeInstanceOf(PaymentGatewayInterface::class);
    expect($resolved->getSlug())->toBe('razorpay');
});

it('manager forSlug returns null for unknown slug', function () {
    $manager = new PaymentGatewayManager();
    $manager->register(new RazorpayService());

    expect($manager->forSlug('stripe'))->toBeNull();
    expect($manager->forSlug('nonexistent'))->toBeNull();
});

it('manager getAll returns every registered gateway', function () {
    $manager = new PaymentGatewayManager();
    $manager->register(new RazorpayService());

    $all = $manager->getAll();

    expect($all)->toHaveKey('razorpay');
    expect($all['razorpay'])->toBeInstanceOf(PaymentGatewayInterface::class);
});

it('manager getConfigured filters out disabled / unconfigured gateways', function () {
    $manager = new PaymentGatewayManager();
    $manager->register(new RazorpayService());

    // Strip Razorpay credentials → isConfigured() returns false → excluded.
    config(['services.razorpay.key' => null, 'services.razorpay.secret' => null]);

    expect($manager->getConfigured())->toBe([]);

    // Restore.
    config(['services.razorpay.key' => 'rzp_test_x', 'services.razorpay.secret' => 'secret']);

    expect($manager->getConfigured())->toHaveKey('razorpay');
});

it('manager register is last-write-wins on slug collision', function () {
    $manager = new PaymentGatewayManager();

    $r1 = new RazorpayService();
    $r2 = new RazorpayService();

    $manager->register($r1);
    $manager->register($r2);

    // Same slug, different instance — second overrides first.
    expect(spl_object_id($manager->forSlug('razorpay')))->toBe(spl_object_id($r2));
});

/* ==================================================================
 |  RazorpayService — slug + name + isConfigured
 | ================================================================== */

it('Razorpay slug is "razorpay" and name is "Razorpay"', function () {
    $svc = new RazorpayService();

    expect($svc->getSlug())->toBe('razorpay');
    expect($svc->getName())->toBe('Razorpay');
});

it('Razorpay isConfigured returns true when key and secret are present', function () {
    config([
        'services.razorpay.key' => 'rzp_test_xxxxxx',
        'services.razorpay.secret' => 'super-secret',
    ]);

    expect((new RazorpayService())->isConfigured())->toBeTrue();
});

it('Razorpay isConfigured returns false when key is missing', function () {
    config([
        'services.razorpay.key' => null,
        'services.razorpay.secret' => 'super-secret',
    ]);

    expect((new RazorpayService())->isConfigured())->toBeFalse();
});

it('Razorpay isConfigured returns false when secret is missing', function () {
    config([
        'services.razorpay.key' => 'rzp_test_xxxxxx',
        'services.razorpay.secret' => null,
    ]);

    expect((new RazorpayService())->isConfigured())->toBeFalse();
});

/* ==================================================================
 |  Razorpay verifyPayment — offline signature math
 | ================================================================== */

it('Razorpay verifyPayment accepts a correctly-signed payload', function () {
    $secret = 'razorpay-test-secret-12345';
    config(['services.razorpay.key' => 'rzp_test_x', 'services.razorpay.secret' => $secret]);

    $orderId = 'order_M1zXabcdef';
    $paymentId = 'pay_M1zXqrstuv';
    $signature = hash_hmac('sha256', $orderId.'|'.$paymentId, $secret);

    $svc = new RazorpayService();

    expect($svc->verifyPayment([
        'razorpay_order_id' => $orderId,
        'razorpay_payment_id' => $paymentId,
        'razorpay_signature' => $signature,
    ]))->toBeTrue();
});

it('Razorpay verifyPayment rejects a tampered signature', function () {
    config(['services.razorpay.key' => 'rzp_test_x', 'services.razorpay.secret' => 'secret']);

    $svc = new RazorpayService();

    expect($svc->verifyPayment([
        'razorpay_order_id' => 'order_X',
        'razorpay_payment_id' => 'pay_X',
        'razorpay_signature' => 'totally-wrong-signature',
    ]))->toBeFalse();
});

it('Razorpay verifyPayment rejects when order_id or payment_id is empty', function () {
    config(['services.razorpay.key' => 'rzp_test_x', 'services.razorpay.secret' => 'secret']);

    $svc = new RazorpayService();

    expect($svc->verifyPayment([
        'razorpay_order_id' => '',
        'razorpay_payment_id' => 'pay_X',
        'razorpay_signature' => 'whatever',
    ]))->toBeFalse();
});

it('Razorpay verifyPayment rejects when not configured', function () {
    config(['services.razorpay.key' => null, 'services.razorpay.secret' => null]);

    $svc = new RazorpayService();

    expect($svc->verifyPayment([
        'razorpay_order_id' => 'order_X',
        'razorpay_payment_id' => 'pay_X',
        'razorpay_signature' => 'sig',
    ]))->toBeFalse();
});

/* ==================================================================
 |  Razorpay createOrder — Http::fake()
 | ================================================================== */

it('Razorpay createOrder returns a Flutter-friendly payload on success', function () {
    config(['services.razorpay.key' => 'rzp_test_xxx', 'services.razorpay.secret' => 'secret']);

    Http::fake([
        'api.razorpay.com/v1/orders' => Http::response([
            'id' => 'order_M1zXabcdef',
            'amount' => 99900,
            'currency' => 'INR',
            'status' => 'created',
        ], 200),
    ]);

    $svc = new RazorpayService();
    $payload = $svc->createOrder(99900, ['receipt' => 'rcpt_42']);

    expect($payload)->toHaveKeys(['order_id', 'key_id', 'amount', 'currency', 'status']);
    expect($payload['order_id'])->toBe('order_M1zXabcdef');
    expect($payload['key_id'])->toBe('rzp_test_xxx');
    expect($payload['amount'])->toBe(99900);
});

it('Razorpay createOrder throws RuntimeException when not configured', function () {
    config(['services.razorpay.key' => null, 'services.razorpay.secret' => null]);

    expect(fn () => (new RazorpayService())->createOrder(99900))
        ->toThrow(\RuntimeException::class, 'Razorpay is not configured');
});

it('Razorpay createOrder throws RuntimeException on non-2xx response', function () {
    config(['services.razorpay.key' => 'rzp_test_x', 'services.razorpay.secret' => 'secret']);

    Http::fake([
        'api.razorpay.com/v1/orders' => Http::response(['error' => 'Bad request'], 400),
    ]);

    expect(fn () => (new RazorpayService())->createOrder(99900))
        ->toThrow(\RuntimeException::class, 'Razorpay order creation failed');
});

it('Razorpay createOrder throws when response has no id', function () {
    config(['services.razorpay.key' => 'rzp_test_x', 'services.razorpay.secret' => 'secret']);

    Http::fake([
        'api.razorpay.com/v1/orders' => Http::response([], 200),
    ]);

    expect(fn () => (new RazorpayService())->createOrder(99900))
        ->toThrow(\RuntimeException::class, 'no order id');
});

/* ==================================================================
 |  verifyValidationRules contract
 | ================================================================== */

it('Razorpay verifyValidationRules requires the 3 standard fields', function () {
    $rules = (new RazorpayService())->verifyValidationRules();

    expect($rules)->toHaveKeys([
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
    ]);
    expect($rules['razorpay_order_id'])->toContain('required');
});
