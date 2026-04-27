<?php

use App\Http\Controllers\Api\V1\PaymentController;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserMembership;
use App\Models\WebhookEvent;
use App\Services\Payment\StripeService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Stripe — service + webhook tests
|--------------------------------------------------------------------------
| Mirrors the Razorpay coverage in PaymentWebhookTest.php — same
| structure, adapted for Stripe specifics:
|
|   1. StripeService unit slice — isConfigured, createOrder (Http::fake),
|      verifyPayment (Http::fake), apply* persisters.
|
|   2. Webhook signature scheme — Stripe-Signature parsing (t=...,v1=...),
|      timestamp tolerance (replay protection), missing/invalid sig.
|
|   3. Webhook dispatch — payment_intent.succeeded → activate,
|      payment_intent.payment_failed / payment_intent.canceled →
|      markFailed, charge.refunded → markRefunded, unknown → ignored.
|
|   4. Idempotency — duplicate event_id returns 200 'duplicate', single
|      WebhookEvent row, single UserMembership.
|
| Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-05-razorpay-webhook.md
| (Stripe addition — step-05a)
*/

function createStripeTables(): void
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

function dropStripeTables(): void
{
    Schema::dropIfExists('webhook_events');
    Schema::dropIfExists('user_memberships');
    Schema::dropIfExists('subscriptions');
    Schema::dropIfExists('membership_plans');
}

function seedStripePlan(array $overrides = []): MembershipPlan
{
    return MembershipPlan::create(array_merge([
        'plan_name' => 'Stripe Plan',
        'slug' => 'stripe-plan-'.uniqid(),
        'duration_months' => 6,
        'price_inr' => 999,
        'is_active' => true,
    ], $overrides));
}

/** Pending Stripe subscription with payment_intent_id in gateway_metadata. */
function seedStripeSubscription(int $userId, int $planId, string $intentId = 'pi_test_xx', array $overrides = []): Subscription
{
    return Subscription::create(array_merge([
        'user_id' => $userId,
        'plan_id' => $planId,
        'gateway' => 'stripe',
        'gateway_metadata' => ['payment_intent_id' => $intentId],
        'amount' => 99900,
        'original_amount' => 99900,
        'payment_status' => 'pending',
    ], $overrides));
}

/** Build a Stripe event payload (top-level shape). */
function stripeEvent(string $type, array $object, string $eventId = 'evt_test_001'): array
{
    return [
        'id' => $eventId,
        'object' => 'event',
        'type' => $type,
        'data' => ['object' => $object],
    ];
}

/**
 * Build a request signed with Stripe-Signature for the given secret.
 * Optional $timestampOffset lets tests forge old timestamps to test
 * replay protection.
 */
function signedStripeRequest(array $event, string $secret, int $timestampOffset = 0): Request
{
    $body = json_encode($event);
    $timestamp = time() + $timestampOffset;
    $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);
    $header = "t={$timestamp},v1={$signature}";

    return Request::create('/api/v1/webhooks/stripe', 'POST', [], [], [], [
        'HTTP_Stripe-Signature' => $header,
        'CONTENT_TYPE' => 'application/json',
    ], $body);
}

beforeEach(function () {
    createStripeTables();
    config([
        'services.stripe.key' => 'pk_test_xxx',
        'services.stripe.secret' => 'sk_test_xxx',
        'services.stripe.webhook_secret' => 'whsec_test_xxx',
    ]);
});

afterEach(function () {
    dropStripeTables();
});

/* ==================================================================
 |  StripeService — isConfigured + createOrder + verifyPayment
 | ================================================================== */

it('stripe isConfigured returns true when secret is present', function () {
    expect(app(StripeService::class)->isConfigured())->toBeTrue();
});

it('stripe isConfigured returns false when secret is missing', function () {
    config(['services.stripe.secret' => null]);
    expect(app(StripeService::class)->isConfigured())->toBeFalse();
});

it('stripe createOrder posts a PaymentIntent + returns Flutter-friendly payload', function () {
    Http::fake([
        'api.stripe.com/v1/payment_intents' => Http::response([
            'id' => 'pi_3M_test_xxx',
            'client_secret' => 'pi_3M_test_xxx_secret_yyy',
            'amount' => 99900,
            'currency' => 'inr',
            'status' => 'requires_payment_method',
        ], 200),
    ]);

    $payload = app(StripeService::class)->createOrder(99900, [
        'user_id' => 7,
        'plan_id' => 3,
        'subscription_id' => 12,
    ]);

    expect($payload['payment_intent_id'])->toBe('pi_3M_test_xxx');
    expect($payload['client_secret'])->toBe('pi_3M_test_xxx_secret_yyy');
    expect($payload['publishable_key'])->toBe('pk_test_xxx');
    expect($payload['amount'])->toBe(99900);
    expect($payload['currency'])->toBe('inr');

    Http::assertSent(function ($req) {
        return str_contains($req->url(), 'payment_intents')
            && $req->method() === 'POST'
            && $req['amount'] === 99900
            && $req['currency'] === 'inr'
            && $req['metadata']['user_id'] === '7';
    });
});

it('stripe createOrder throws when API returns error', function () {
    Http::fake([
        'api.stripe.com/v1/payment_intents' => Http::response(
            ['error' => ['message' => 'Invalid API key']],
            401,
        ),
    ]);

    expect(fn () => app(StripeService::class)->createOrder(99900, []))
        ->toThrow(\RuntimeException::class);
});

/**
 * Build an in-memory Subscription with payment_intent_id in
 * gateway_metadata — verifyPayment now binds against it (Phase 2a Vuln 1).
 */
function stripeSubscription(string $intentId = 'pi_M_xxx'): \App\Models\Subscription
{
    $sub = new \App\Models\Subscription();
    $sub->forceFill([
        'id' => 1,
        'gateway_metadata' => ['payment_intent_id' => $intentId],
    ]);
    return $sub;
}

it('stripe verifyPayment returns true when intent status=succeeded', function () {
    Http::fake([
        'api.stripe.com/v1/payment_intents/pi_M_xxx' => Http::response([
            'id' => 'pi_M_xxx',
            'status' => 'succeeded',
        ], 200),
    ]);

    $ok = app(StripeService::class)->verifyPayment(
        ['payment_intent_id' => 'pi_M_xxx'],
        stripeSubscription('pi_M_xxx'),
    );

    expect($ok)->toBeTrue();
});

it('stripe verifyPayment returns false when intent is not succeeded', function () {
    Http::fake([
        'api.stripe.com/v1/payment_intents/pi_M_xxx' => Http::response([
            'id' => 'pi_M_xxx',
            'status' => 'requires_action',
        ], 200),
    ]);

    expect(app(StripeService::class)->verifyPayment(
        ['payment_intent_id' => 'pi_M_xxx'],
        stripeSubscription('pi_M_xxx'),
    ))->toBeFalse();
});

it('stripe verifyPayment REJECTS replay across subscriptions (Vuln 1 anti-substitution)', function () {
    Http::fake([
        'api.stripe.com/v1/payment_intents/pi_PAID' => Http::response([
            'id' => 'pi_PAID',
            'status' => 'succeeded',
        ], 200),
    ]);

    // Subscription B has its own payment_intent_id; attacker submits sub_A's.
    expect(app(StripeService::class)->verifyPayment(
        ['payment_intent_id' => 'pi_PAID'],
        stripeSubscription('pi_DIFFERENT'),
    ))->toBeFalse();
});

/* ==================================================================
 |  Stripe webhook — signature scheme
 | ================================================================== */

it('stripe webhook returns 401 when Stripe-Signature header is missing', function () {
    $request = Request::create('/api/v1/webhooks/stripe', 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode(['id' => 'evt_x', 'type' => 'payment_intent.succeeded', 'data' => ['object' => []]]));

    $response = app(PaymentController::class)->webhook($request, 'stripe');

    expect($response->getStatusCode())->toBe(401);
});

it('stripe webhook returns 401 when signature does not match', function () {
    $body = json_encode(['id' => 'evt_x', 'type' => 'payment_intent.succeeded', 'data' => ['object' => []]]);
    $request = Request::create('/api/v1/webhooks/stripe', 'POST', [], [], [], [
        'HTTP_Stripe-Signature' => 't='.time().',v1=deadbeef',
        'CONTENT_TYPE' => 'application/json',
    ], $body);

    $response = app(PaymentController::class)->webhook($request, 'stripe');

    expect($response->getStatusCode())->toBe(401);
});

it('stripe webhook rejects timestamps outside the tolerance window (replay)', function () {
    // Forge a timestamp 10 minutes in the past — tolerance is 300s.
    $request = signedStripeRequest(
        stripeEvent('payment_intent.succeeded', ['id' => 'pi_old']),
        'whsec_test_xxx',
        timestampOffset: -600,
    );

    $response = app(PaymentController::class)->webhook($request, 'stripe');

    expect($response->getStatusCode())->toBe(401);
});

it('stripe webhook returns 503 when webhook_secret is not configured', function () {
    config(['services.stripe.webhook_secret' => null]);

    $request = signedStripeRequest(
        stripeEvent('payment_intent.succeeded', ['id' => 'pi_xx']),
        'whatever',
    );

    $response = app(PaymentController::class)->webhook($request, 'stripe');

    expect($response->getStatusCode())->toBe(503);
});

it('stripe webhook returns 422 on malformed JSON', function () {
    $body = 'not-json';
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_test_xxx');
    $request = Request::create('/api/v1/webhooks/stripe', 'POST', [], [], [], [
        'HTTP_Stripe-Signature' => "t={$timestamp},v1={$signature}",
        'CONTENT_TYPE' => 'application/json',
    ], $body);

    $response = app(PaymentController::class)->webhook($request, 'stripe');

    expect($response->getStatusCode())->toBe(422);
});

it('stripe webhook accepts second v1 signature during secret rotation', function () {
    $event = stripeEvent('subscription.updated', ['id' => 'sub_x'], 'evt_rotation');
    $body = json_encode($event);
    $timestamp = time();
    $oldSig = hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_old_secret');
    $newSig = hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_test_xxx');
    // Stripe sends both during rotation: t=...,v1=old,v1=new
    $request = Request::create('/api/v1/webhooks/stripe', 'POST', [], [], [], [
        'HTTP_Stripe-Signature' => "t={$timestamp},v1={$oldSig},v1={$newSig}",
        'CONTENT_TYPE' => 'application/json',
    ], $body);

    $response = app(PaymentController::class)->webhook($request, 'stripe');

    // Either signature matching the configured secret should pass —
    // here the new one matches, so it's accepted (and ignored for type).
    expect($response->getStatusCode())->toBe(200);
});

/* ==================================================================
 |  Stripe webhook — payment_intent.succeeded
 | ================================================================== */

it('stripe webhook payment_intent.succeeded marks subscription paid + creates membership', function () {
    $plan = seedStripePlan(['duration_months' => 6]);
    $sub = seedStripeSubscription(7700, $plan->id, 'pi_M1zXabc');

    $event = stripeEvent('payment_intent.succeeded', [
        'id' => 'pi_M1zXabc',
        'status' => 'succeeded',
        'latest_charge' => 'ch_M1zXqrstuv',
    ], 'evt_001');
    $request = signedStripeRequest($event, 'whsec_test_xxx');

    $response = app(PaymentController::class)->webhook($request, 'stripe');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('processed');

    $sub->refresh();
    expect($sub->payment_status)->toBe('paid');
    expect($sub->is_active)->toBeTrue();
    expect($sub->gateway_metadata['payment_intent_id'])->toBe('pi_M1zXabc');
    expect($sub->gateway_metadata['charge_id'])->toBe('ch_M1zXqrstuv');
    expect(UserMembership::where('user_id', 7700)->where('is_active', true)->exists())->toBeTrue();
});

it('stripe webhook payment_intent.succeeded falls back to charges.data[0].id for older API shape', function () {
    $plan = seedStripePlan();
    $sub = seedStripeSubscription(7700, $plan->id, 'pi_old_api');

    $event = stripeEvent('payment_intent.succeeded', [
        'id' => 'pi_old_api',
        'status' => 'succeeded',
        // Older API: no latest_charge, charges nested instead
        'charges' => ['data' => [['id' => 'ch_legacy_001']]],
    ], 'evt_legacy');
    $request = signedStripeRequest($event, 'whsec_test_xxx');

    $response = app(PaymentController::class)->webhook($request, 'stripe');

    expect($response->getStatusCode())->toBe(200);
    expect($sub->fresh()->gateway_metadata['charge_id'])->toBe('ch_legacy_001');
});

it('stripe webhook payment_intent.succeeded is idempotent — duplicate event_id returns 200 duplicate', function () {
    $plan = seedStripePlan();
    seedStripeSubscription(7700, $plan->id, 'pi_dup');

    $event = stripeEvent('payment_intent.succeeded', [
        'id' => 'pi_dup',
        'status' => 'succeeded',
    ], 'evt_dup');

    $r1 = app(PaymentController::class)->webhook(signedStripeRequest($event, 'whsec_test_xxx'), 'stripe');
    expect($r1->getStatusCode())->toBe(200);
    expect($r1->getData(true)['status'])->toBe('processed');

    $r2 = app(PaymentController::class)->webhook(signedStripeRequest($event, 'whsec_test_xxx'), 'stripe');
    expect($r2->getStatusCode())->toBe(200);
    expect($r2->getData(true)['status'])->toBe('duplicate');

    expect(WebhookEvent::where('event_id', 'evt_dup')->count())->toBe(1);
    expect(UserMembership::where('user_id', 7700)->count())->toBe(1);
});

it('stripe webhook payment_intent.succeeded returns 200 ignored when subscription not found', function () {
    $event = stripeEvent('payment_intent.succeeded', [
        'id' => 'pi_orphan',
        'status' => 'succeeded',
    ], 'evt_orphan');

    $response = app(PaymentController::class)->webhook(signedStripeRequest($event, 'whsec_test_xxx'), 'stripe');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('ignored');
});

/* ==================================================================
 |  Stripe webhook — payment_intent.payment_failed / canceled
 | ================================================================== */

it('stripe webhook payment_intent.payment_failed marks subscription failed', function () {
    $plan = seedStripePlan();
    $sub = seedStripeSubscription(7700, $plan->id, 'pi_fail');

    $event = stripeEvent('payment_intent.payment_failed', [
        'id' => 'pi_fail',
    ], 'evt_failed');

    $response = app(PaymentController::class)->webhook(signedStripeRequest($event, 'whsec_test_xxx'), 'stripe');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('processed');
    expect($sub->fresh()->payment_status)->toBe('failed');
});

it('stripe webhook payment_intent.canceled also marks subscription failed', function () {
    $plan = seedStripePlan();
    $sub = seedStripeSubscription(7700, $plan->id, 'pi_cancel');

    $event = stripeEvent('payment_intent.canceled', [
        'id' => 'pi_cancel',
    ], 'evt_canceled');

    $response = app(PaymentController::class)->webhook(signedStripeRequest($event, 'whsec_test_xxx'), 'stripe');

    expect($response->getStatusCode())->toBe(200);
    expect($sub->fresh()->payment_status)->toBe('failed');
});

/* ==================================================================
 |  Stripe webhook — charge.refunded
 | ================================================================== */

it('stripe webhook charge.refunded marks subscription refunded + deactivates membership', function () {
    $plan = seedStripePlan();
    $sub = seedStripeSubscription(7700, $plan->id, 'pi_refund', [
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

    $event = stripeEvent('charge.refunded', [
        'id' => 'ch_refund',
        'payment_intent' => 'pi_refund',
        'amount_refunded' => 99900,
        'refunded' => true,
    ], 'evt_refund');

    $response = app(PaymentController::class)->webhook(signedStripeRequest($event, 'whsec_test_xxx'), 'stripe');

    expect($response->getStatusCode())->toBe(200);
    expect($sub->fresh()->payment_status)->toBe('refunded');
    expect(UserMembership::where('user_id', 7700)->first()->is_active)->toBeFalse();
});

/* ==================================================================
 |  Stripe webhook — unknown event types
 | ================================================================== */

it('stripe webhook returns 200 ignored for unknown event types', function () {
    $event = stripeEvent('customer.created', ['id' => 'cus_x'], 'evt_unknown');

    $response = app(PaymentController::class)->webhook(signedStripeRequest($event, 'whsec_test_xxx'), 'stripe');

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe('ignored');

    $row = WebhookEvent::where('event_id', 'evt_unknown')->first();
    expect($row)->not->toBeNull();
    expect($row->status)->toBe('ignored');
});
