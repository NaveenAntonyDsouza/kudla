<?php

use App\Http\Controllers\Api\V1\PaymentController;
use App\Models\Coupon;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserMembership;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentGatewayManager;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| PaymentController — /payment/{gateway}/order + /verify
|--------------------------------------------------------------------------
| Tests dispatch logic with a FAKE PaymentGatewayInterface (NOT real
| Razorpay) so we can:
|   - Drive every controller branch (success, signature-invalid,
|     gateway-not-configured, unknown-gateway)
|   - Verify the controller calls applyOrderIdsToSubscription /
|     applyVerifiedIdsToSubscription on the right hooks
|   - Assert the orchestration: pending Subscription → gateway call →
|     paid Subscription + UserMembership creation
|
| RazorpayService's signature math + HTTP layer are covered separately
| in PaymentGatewayInfrastructureTest. Real-gateway end-to-end smoke
| lives in Bruno (Phase 2c).
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
*/

/**
 * Recording fake gateway. Captures every method call so tests can
 * assert the controller orchestrates correctly. Configurable to
 * pretend it's "configured" or not, and to make verifyPayment
 * succeed or fail.
 */
class FakeGateway implements PaymentGatewayInterface
{
    public bool $verifySucceeds = true;
    public bool $configured = true;
    public string $slug = 'fake';
    public string $name = 'Fake Gateway';
    /** @var array<int, array{method: string, args: array}> */
    public array $calls = [];
    /** Set to a Throwable to make createOrder throw on next call. */
    public ?\Throwable $createOrderThrowable = null;

    public function getSlug(): string { return $this->slug; }
    public function getName(): string { return $this->name; }
    public function isConfigured(): bool { return $this->configured; }

    public function createOrder(int $amountInPaise, array $metadata = []): array
    {
        $this->calls[] = ['method' => 'createOrder', 'args' => compact('amountInPaise', 'metadata')];
        if ($this->createOrderThrowable) {
            $t = $this->createOrderThrowable;
            $this->createOrderThrowable = null;
            throw $t;
        }
        return [
            'order_id' => 'fake_order_'.uniqid(),
            'key_id' => 'fake_key_xxx',
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'status' => 'created',
        ];
    }

    public function verifyPayment(array $data): bool
    {
        $this->calls[] = ['method' => 'verifyPayment', 'args' => $data];
        return $this->verifySucceeds;
    }

    public function verifyValidationRules(): array
    {
        return [
            'fake_signature' => 'required|string',
        ];
    }

    public function applyOrderIdsToSubscription(\App\Models\Subscription $subscription, array $orderResponse): void
    {
        $this->calls[] = ['method' => 'applyOrderIdsToSubscription', 'args' => ['sub_id' => $subscription->id]];
        // Persist via gateway_metadata since we're not Razorpay.
        $existing = $subscription->gateway_metadata ?? [];
        $subscription->update([
            'gateway_metadata' => array_merge((array) $existing, [
                'fake_order_id' => $orderResponse['order_id'],
            ]),
        ]);
    }

    public function applyVerifiedIdsToSubscription(\App\Models\Subscription $subscription, array $verifyData): void
    {
        $this->calls[] = ['method' => 'applyVerifiedIdsToSubscription', 'args' => ['sub_id' => $subscription->id]];
        $existing = $subscription->gateway_metadata ?? [];
        $subscription->update([
            'gateway_metadata' => array_merge((array) $existing, [
                'fake_signature' => $verifyData['fake_signature'] ?? null,
            ]),
        ]);
    }
}

function createPaymentTables(): void
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
            $t->index('user_id');
            $t->index('gateway');
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
}

function dropPaymentTables(): void
{
    Schema::dropIfExists('coupon_usages');
    Schema::dropIfExists('coupons');
    Schema::dropIfExists('user_memberships');
    Schema::dropIfExists('subscriptions');
    Schema::dropIfExists('membership_plans');
}

function buildPaymentUser(int $id = 7700): User
{
    $u = new User();
    $u->exists = true;
    $u->forceFill(['id' => $id, 'email' => "p{$id}@e.com", 'is_active' => true]);
    return $u;
}

function seedPaymentPlan(array $overrides = []): MembershipPlan
{
    return MembershipPlan::create(array_merge([
        'plan_name' => 'Test Plan',
        'slug' => 'test-plan-'.uniqid(),
        'duration_months' => 1,
        'price_inr' => 1000,
        'is_active' => true,
    ], $overrides));
}

function bindFakeGatewayManager(FakeGateway $fake): PaymentGatewayManager
{
    $manager = new PaymentGatewayManager();
    $manager->register($fake);
    app()->instance(PaymentGatewayManager::class, $manager);
    return $manager;
}

function paymentRequest(User $user, string $method, array $body, string $path): Request
{
    $r = Request::create($path, $method, $body);
    $r->setUserResolver(fn () => $user);
    return $r;
}

beforeEach(function () {
    createPaymentTables();
});

afterEach(function () {
    dropPaymentTables();
});

/* ==================================================================
 |  POST /payment/{gateway}/order — guard paths
 | ================================================================== */

it('createOrder returns 404 NOT_FOUND for unknown gateway slug', function () {
    $user = buildPaymentUser();
    bindFakeGatewayManager(new FakeGateway());

    $response = app(PaymentController::class)->createOrder(
        paymentRequest($user, 'POST', ['plan_id' => 1], '/api/v1/payment/nonexistent/order'),
        'nonexistent',
    );

    expect($response->getStatusCode())->toBe(404);
    expect($response->getData(true)['error']['code'])->toBe('NOT_FOUND');
});

it('createOrder returns 422 GATEWAY_NOT_CONFIGURED when gateway is disabled', function () {
    $user = buildPaymentUser();
    $fake = new FakeGateway();
    $fake->configured = false;  // simulate "not configured"
    bindFakeGatewayManager($fake);

    $response = app(PaymentController::class)->createOrder(
        paymentRequest($user, 'POST', ['plan_id' => 1], '/api/v1/payment/fake/order'),
        'fake',
    );

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('GATEWAY_NOT_CONFIGURED');
});

it('createOrder returns 404 NOT_FOUND when plan does not exist', function () {
    $user = buildPaymentUser();
    bindFakeGatewayManager(new FakeGateway());

    $response = app(PaymentController::class)->createOrder(
        paymentRequest($user, 'POST', ['plan_id' => 9999], '/api/v1/payment/fake/order'),
        'fake',
    );

    expect($response->getStatusCode())->toBe(404);
    expect($response->getData(true)['error']['message'])->toBe('Plan not found.');
});

/* ==================================================================
 |  POST /payment/{gateway}/order — happy path
 | ================================================================== */

it('createOrder happy path persists pending Subscription + delegates to gateway', function () {
    $user = buildPaymentUser();
    $plan = seedPaymentPlan(['price_inr' => 999]);
    $fake = new FakeGateway();
    bindFakeGatewayManager($fake);

    $response = app(PaymentController::class)->createOrder(
        paymentRequest($user, 'POST', ['plan_id' => $plan->id], '/api/v1/payment/fake/order'),
        'fake',
    );
    $data = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(201);
    expect($data)->toHaveKeys(['subscription_id', 'gateway', 'amount_inr', 'currency', 'gateway_data']);
    expect($data['gateway'])->toBe('fake');
    expect($data['amount_inr'])->toBe(999);

    // Subscription persisted as pending.
    $sub = Subscription::find($data['subscription_id']);
    expect($sub->payment_status)->toBe('pending');
    expect($sub->gateway)->toBe('fake');
    expect($sub->amount)->toBe(99900);  // paise

    // Gateway was called.
    $methods = collect($fake->calls)->pluck('method')->all();
    expect($methods)->toContain('createOrder');
    expect($methods)->toContain('applyOrderIdsToSubscription');
});

it('createOrder applies a valid coupon and reduces the persisted amount', function () {
    $user = buildPaymentUser();
    $plan = seedPaymentPlan(['price_inr' => 1000]);
    Coupon::create([
        'code' => 'WELCOME20',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'usage_limit_per_user' => 1,
        'is_active' => true,
    ]);
    bindFakeGatewayManager(new FakeGateway());

    $response = app(PaymentController::class)->createOrder(
        paymentRequest($user, 'POST', [
            'plan_id' => $plan->id,
            'coupon_code' => 'WELCOME20',
        ], '/api/v1/payment/fake/order'),
        'fake',
    );
    $data = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(201);
    expect($data['amount_inr'])->toBe(800);  // ₹1000 - 20% = ₹800

    $sub = Subscription::find($data['subscription_id']);
    expect($sub->original_amount)->toBe(100000);  // paise
    expect($sub->discount_amount)->toBe(20000);   // paise
    expect($sub->amount)->toBe(80000);            // paise
    expect($sub->coupon_code)->toBe('WELCOME20');
});

it('createOrder rejects an invalid coupon with 422 COUPON_INVALID', function () {
    $user = buildPaymentUser();
    $plan = seedPaymentPlan();
    Coupon::create([
        'code' => 'EXPIRED',
        'discount_type' => 'percentage',
        'discount_value' => 50,
        'usage_limit_per_user' => 1,
        'valid_until' => Carbon::now()->subDay(),
        'is_active' => true,
    ]);
    bindFakeGatewayManager(new FakeGateway());

    $response = app(PaymentController::class)->createOrder(
        paymentRequest($user, 'POST', [
            'plan_id' => $plan->id,
            'coupon_code' => 'EXPIRED',
        ], '/api/v1/payment/fake/order'),
        'fake',
    );

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('COUPON_INVALID');
});

it('createOrder marks the Subscription as failed + returns 502 when gateway throws', function () {
    $user = buildPaymentUser();
    $plan = seedPaymentPlan();
    $fake = new FakeGateway();
    $fake->createOrderThrowable = new \RuntimeException('Razorpay API down');
    bindFakeGatewayManager($fake);

    $response = app(PaymentController::class)->createOrder(
        paymentRequest($user, 'POST', ['plan_id' => $plan->id], '/api/v1/payment/fake/order'),
        'fake',
    );

    expect($response->getStatusCode())->toBe(502);
    expect($response->getData(true)['error']['code'])->toBe('GATEWAY_ERROR');

    // Subscription persisted but marked failed.
    $sub = Subscription::where('user_id', $user->id)->first();
    expect($sub)->not->toBeNull();
    expect($sub->payment_status)->toBe('failed');
});

/* ==================================================================
 |  POST /payment/{gateway}/verify — guard paths
 | ================================================================== */

it('verify returns 404 for unknown gateway', function () {
    $user = buildPaymentUser();
    bindFakeGatewayManager(new FakeGateway());

    $response = app(PaymentController::class)->verifyPayment(
        paymentRequest($user, 'POST', [
            'subscription_id' => 1,
            'fake_signature' => 'sig',
        ], '/api/v1/payment/nonexistent/verify'),
        'nonexistent',
    );

    expect($response->getStatusCode())->toBe(404);
});

it('verify returns 404 when subscription does not exist', function () {
    $user = buildPaymentUser();
    bindFakeGatewayManager(new FakeGateway());

    $response = app(PaymentController::class)->verifyPayment(
        paymentRequest($user, 'POST', [
            'subscription_id' => 9999,
            'fake_signature' => 'sig',
        ], '/api/v1/payment/fake/verify'),
        'fake',
    );

    expect($response->getStatusCode())->toBe(404);
});

it('verify returns 403 when subscription belongs to a different user', function () {
    $owner = buildPaymentUser(7700);
    $stranger = buildPaymentUser(7799);
    $plan = seedPaymentPlan();
    $sub = Subscription::create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'gateway' => 'fake',
        'amount' => 99900,
        'payment_status' => 'pending',
    ]);
    bindFakeGatewayManager(new FakeGateway());

    $response = app(PaymentController::class)->verifyPayment(
        paymentRequest($stranger, 'POST', [
            'subscription_id' => $sub->id,
            'fake_signature' => 'sig',
        ], '/api/v1/payment/fake/verify'),
        'fake',
    );

    expect($response->getStatusCode())->toBe(403);
    expect($response->getData(true)['error']['code'])->toBe('UNAUTHORIZED');
});

/* ==================================================================
 |  POST /payment/{gateway}/verify — happy + idempotent + signature
 | ================================================================== */

it('verify happy path marks subscription paid + creates UserMembership', function () {
    $user = buildPaymentUser();
    $plan = seedPaymentPlan(['duration_months' => 6]);
    $sub = Subscription::create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'gateway' => 'fake',
        'amount' => 99900,
        'payment_status' => 'pending',
    ]);
    $fake = new FakeGateway();
    bindFakeGatewayManager($fake);

    $response = app(PaymentController::class)->verifyPayment(
        paymentRequest($user, 'POST', [
            'subscription_id' => $sub->id,
            'fake_signature' => 'valid-sig',
        ], '/api/v1/payment/fake/verify'),
        'fake',
    );
    $data = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(200);
    expect($data['payment_status'])->toBe('paid');
    expect($data['is_active'])->toBeTrue();
    expect($data['membership']['plan_id'])->toBe($plan->id);

    // Subscription updated.
    $sub->refresh();
    expect($sub->payment_status)->toBe('paid');
    expect($sub->is_active)->toBeTrue();
    expect($sub->starts_at)->not->toBeNull();
    expect($sub->expires_at)->not->toBeNull();

    // UserMembership created + active.
    $membership = UserMembership::where('user_id', $user->id)->first();
    expect($membership)->not->toBeNull();
    expect($membership->is_active)->toBeTrue();

    // Gateway called.
    $methods = collect($fake->calls)->pluck('method')->all();
    expect($methods)->toContain('verifyPayment');
    expect($methods)->toContain('applyVerifiedIdsToSubscription');
});

it('verify is idempotent — second call returns already_verified=true', function () {
    $user = buildPaymentUser();
    $plan = seedPaymentPlan();
    $sub = Subscription::create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'gateway' => 'fake',
        'amount' => 99900,
        'payment_status' => 'paid',  // already paid
        'is_active' => true,
    ]);
    bindFakeGatewayManager(new FakeGateway());

    $response = app(PaymentController::class)->verifyPayment(
        paymentRequest($user, 'POST', [
            'subscription_id' => $sub->id,
            'fake_signature' => 'whatever',
        ], '/api/v1/payment/fake/verify'),
        'fake',
    );
    $data = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(200);
    expect($data['already_verified'])->toBeTrue();
    expect($data['subscription_id'])->toBe($sub->id);
});

it('verify returns 422 SIGNATURE_INVALID when gateway rejects', function () {
    $user = buildPaymentUser();
    $plan = seedPaymentPlan();
    $sub = Subscription::create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'gateway' => 'fake',
        'amount' => 99900,
        'payment_status' => 'pending',
    ]);
    $fake = new FakeGateway();
    $fake->verifySucceeds = false;
    bindFakeGatewayManager($fake);

    $response = app(PaymentController::class)->verifyPayment(
        paymentRequest($user, 'POST', [
            'subscription_id' => $sub->id,
            'fake_signature' => 'bad-sig',
        ], '/api/v1/payment/fake/verify'),
        'fake',
    );

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('SIGNATURE_INVALID');

    // Subscription remains pending.
    $sub->refresh();
    expect($sub->payment_status)->toBe('pending');
});

it('verify deactivates prior active memberships before creating the new one', function () {
    $user = buildPaymentUser();
    $oldPlan = seedPaymentPlan(['plan_name' => 'Old Plan']);
    $newPlan = seedPaymentPlan(['plan_name' => 'New Plan', 'duration_months' => 6]);

    // Pre-existing active membership on old plan.
    UserMembership::create([
        'user_id' => $user->id,
        'plan_id' => $oldPlan->id,
        'is_active' => true,
        'starts_at' => Carbon::now()->subMonths(2),
        'ends_at' => Carbon::now()->addMonths(1),
    ]);

    $sub = Subscription::create([
        'user_id' => $user->id,
        'plan_id' => $newPlan->id,
        'gateway' => 'fake',
        'amount' => 99900,
        'payment_status' => 'pending',
    ]);
    bindFakeGatewayManager(new FakeGateway());

    app(PaymentController::class)->verifyPayment(
        paymentRequest($user, 'POST', [
            'subscription_id' => $sub->id,
            'fake_signature' => 'valid',
        ], '/api/v1/payment/fake/verify'),
        'fake',
    );

    // Old membership now inactive; new one active.
    $oldMem = UserMembership::where('plan_id', $oldPlan->id)->first();
    $newMem = UserMembership::where('plan_id', $newPlan->id)->first();

    expect($oldMem->is_active)->toBeFalse();
    expect($newMem)->not->toBeNull();
    expect($newMem->is_active)->toBeTrue();
});

it('verify records coupon usage when subscription has a coupon', function () {
    $user = buildPaymentUser();
    $plan = seedPaymentPlan();
    $coupon = Coupon::create([
        'code' => 'WELCOME20',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'usage_limit_per_user' => 1,
        'is_active' => true,
    ]);
    $sub = Subscription::create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'gateway' => 'fake',
        'coupon_id' => $coupon->id,
        'coupon_code' => 'WELCOME20',
        'discount_amount' => 20000,
        'original_amount' => 100000,
        'amount' => 80000,
        'payment_status' => 'pending',
    ]);
    bindFakeGatewayManager(new FakeGateway());

    app(PaymentController::class)->verifyPayment(
        paymentRequest($user, 'POST', [
            'subscription_id' => $sub->id,
            'fake_signature' => 'valid',
        ], '/api/v1/payment/fake/verify'),
        'fake',
    );

    // Coupon usage row created + times_used incremented.
    expect(\App\Models\CouponUsage::where('coupon_id', $coupon->id)->count())->toBe(1);
    expect($coupon->fresh()->times_used)->toBe(1);
});
