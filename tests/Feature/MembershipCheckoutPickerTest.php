<?php

use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Models\ThemeSetting;
use App\Models\User;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Web checkout — gateway picker + dispatch
|--------------------------------------------------------------------------
| The web MembershipController used to hard-code Razorpay. From this
| commit it routes through PaymentGatewayManager so:
|
|   • Only one gateway enabled → auto-pick, render that flow.
|   • Two or more enabled      → render gateway-picker view, which posts
|                                back with `gateway` set.
|   • `gateway` posted but not in getConfigured() → 422 (defends against
|                                a stale picker form submitted after an
|                                admin disabled the gateway).
|
| Razorpay-specific verify() and the Razorpay JS view are unchanged.
| PhonePe's redirect-based return URL has its own test below.
*/

class FakeWebGateway implements PaymentGatewayInterface
{
    public function __construct(
        public string $slug = 'fake',
        public string $name = 'Fake Gateway',
        public bool $configured = true,
        public array $orderResponse = [],
    ) {}

    public function getSlug(): string { return $this->slug; }
    public function getName(): string { return $this->name; }
    public function isConfigured(): bool { return $this->configured; }

    public function createOrder(int $amountInPaise, array $metadata = []): array
    {
        return $this->orderResponse ?: [
            'order_id' => 'fake_order_'.uniqid(),
            'key_id' => 'fake_key',
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'status' => 'created',
        ];
    }

    public function verifyPayment(array $data, Subscription $subscription): bool { return true; }
    public function verifyValidationRules(): array { return []; }
    public function applyOrderIdsToSubscription(Subscription $subscription, array $orderResponse): void
    {
        // For tests: stash whatever the order returned into a known column
        // so we can assert it later.
        $subscription->update(['razorpay_order_id' => $orderResponse['order_id'] ?? '']);
    }
    public function applyVerifiedIdsToSubscription(Subscription $subscription, array $verifyData): void {}
    public function handleWebhook(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}

function createMembershipCheckoutSchema(): void
{
    if (! Schema::hasTable('users')) {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable()->unique();
            $t->string('phone')->nullable();
            $t->string('password')->nullable();
            $t->string('role')->default('user');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->unsignedBigInteger('staff_role_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    // EnsureProfileComplete middleware does `$user->profile`. If the in-memory
    // setRelation isn't honoured (Filament / framework internals may re-resolve
    // via $request->user() through a different binding), the User::profile()
    // hasOne falls back to a real SELECT against this table. We seed a row
    // with onboarding_completed=true so the middleware passes either way.
    if (! Schema::hasTable('profiles')) {
        Schema::create('profiles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->string('matri_id')->nullable();
            $t->string('full_name')->nullable();
            $t->string('gender')->nullable();
            $t->boolean('is_active')->default(true);
            $t->boolean('is_approved')->default(true);
            $t->boolean('onboarding_completed')->default(false);
            $t->integer('onboarding_step_completed')->default(0);
            $t->timestamp('deleted_at')->nullable();  // SoftDeletes
            $t->timestamps();
            $t->index('user_id');
        });
    }

    if (! Schema::hasTable('membership_plans')) {
        Schema::create('membership_plans', function (Blueprint $t) {
            $t->id();
            $t->string('plan_name');
            $t->integer('price_inr');
            $t->integer('duration_months')->default(1);
            $t->integer('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    // The app layout reads several SiteSetting keys (tagline, tracking IDs,
    // etc.). Easier to bootstrap the table than try to pre-warm every key
    // the layout might call out to.
    if (! Schema::hasTable('site_settings')) {
        Schema::create('site_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });
    }

    // The app layout renders a notification bell that queries unread count.
    // Without this table the picker view 500s on render — even though the
    // controller logic itself succeeded.
    if (! Schema::hasTable('notifications')) {
        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->boolean('is_read')->default(false);
            $t->string('title')->nullable();
            $t->string('body')->nullable();
            $t->timestamps();
            $t->index('user_id');
        });
    }

    if (! Schema::hasTable('subscriptions')) {
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->string('plan_id');
            $t->string('plan_name')->nullable();
            $t->unsignedBigInteger('coupon_id')->nullable();
            $t->string('coupon_code')->nullable();
            $t->integer('discount_amount')->default(0);
            $t->integer('original_amount')->default(0);
            $t->integer('amount')->default(0);
            $t->string('gateway')->nullable();
            $t->json('gateway_metadata')->nullable();
            $t->string('razorpay_order_id')->nullable();
            $t->string('razorpay_payment_id')->nullable();
            $t->string('razorpay_signature')->nullable();
            $t->string('payment_status')->default('pending');
            $t->date('starts_at')->nullable();
            $t->date('expires_at')->nullable();
            $t->boolean('is_active')->default(false);
            $t->timestamps();
        });
    }
}

beforeEach(function () {
    createMembershipCheckoutSchema();

    // Fresh manager per test — no shared state across cases.
    app()->instance(PaymentGatewayManager::class, new PaymentGatewayManager());

    // Pre-warm caches the app layout queries (theme_settings + tracking
    // site_settings). Without these the gateway-picker render 500s on a
    // missing table even though the controller logic itself succeeded.
    $theme = new ThemeSetting([
        'site_name' => 'Test Matrimony',
        'tagline' => 'Test',
        'primary_color' => '#dc2626',
        'primary_hover' => '#b91c1c',
        'primary_light' => '#fee2e2',
        'secondary_color' => '#fbbf24',
        'secondary_hover' => '#f59e0b',
        'secondary_light' => '#fef3c7',
        'heading_font' => 'Inter',
        'body_font' => 'Inter',
    ]);
    Cache::put('theme_settings', $theme, 3600);
    Cache::put('site_setting.site_name', 'Test Matrimony', 3600);
    Cache::put('site_setting.google_analytics_id', '', 3600);
    Cache::put('site_setting.google_tag_manager_id', '', 3600);
    Cache::put('site_setting.facebook_pixel_id', '', 3600);
    Cache::put('site_setting.posthog_api_key', '', 3600);
    Cache::put('site_setting.posthog_host', 'https://us.i.posthog.com', 3600);
});

afterEach(function () {
    \DB::table('subscriptions')->delete();
    \DB::table('membership_plans')->delete();
    \DB::table('users')->delete();
    \DB::table('profiles')->delete();
    \DB::table('site_settings')->delete();
    Cache::flush();
});

function makeMembershipCheckoutUser(int $id = 8001): User
{
    // Real users row so auth + BranchScopable creating-event behave as in
    // production (the in-memory forceFill alone isn't enough — the
    // EnsureProfileComplete middleware lazy-loads `$user->profile` via
    // hasOne which falls back to a DB query if the relation isn't already
    // loaded; safer to just seed real rows than fight the framework).
    \DB::table('users')->insert([
        'id' => $id,
        'email' => "checkout-{$id}@example.com",
        'phone' => '9700000000',
        'name' => 'Checkout Test',
        'is_active' => true,
        'branch_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Real profiles row with onboarding_completed=true so the middleware
    // doesn't redirect us to /register before we reach the controller.
    \DB::table('profiles')->insert([
        'id' => $id,
        'user_id' => $id,
        'branch_id' => 1,
        'matri_id' => 'KM900'.$id,
        'full_name' => 'Checkout Test',
        'gender' => 'female',
        'is_active' => true,
        'is_approved' => true,
        'onboarding_completed' => true,
        'onboarding_step_completed' => 5,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return User::find($id);
}

function makeMembershipCheckoutPlan(): MembershipPlan
{
    $id = \DB::table('membership_plans')->insertGetId([
        'plan_name' => 'Premium',
        'price_inr' => 1000,
        'duration_months' => 1,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    return MembershipPlan::find($id);
}

it('auto-picks the only enabled gateway and skips the picker', function () {
    $user = makeMembershipCheckoutUser(8001);
    $plan = makeMembershipCheckoutPlan();

    $mgr = app(PaymentGatewayManager::class);
    $mgr->register(new FakeWebGateway(slug: 'razorpay', name: 'Razorpay'));

    $this->actingAs($user);

    $response = $this->post(route('membership.checkout'), [
        'plan_id' => $plan->id,
    ]);

    // Razorpay flow returns the checkout.blade.php view (200), not a redirect.
    $response->assertOk();
    // Subscription row was created with gateway stamped.
    $sub = \DB::table('subscriptions')->where('user_id', $user->id)->first();
    expect($sub)->not->toBeNull();
    expect($sub->gateway)->toBe('razorpay');
    expect($sub->payment_status)->toBe('pending');
});

it('shows the picker when two or more gateways are enabled', function () {
    $user = makeMembershipCheckoutUser(8002);
    $plan = makeMembershipCheckoutPlan();

    $mgr = app(PaymentGatewayManager::class);
    $mgr->register(new FakeWebGateway(slug: 'razorpay', name: 'Razorpay'));
    $mgr->register(new FakeWebGateway(slug: 'phonepe', name: 'PhonePe'));

    $this->actingAs($user);

    $response = $this->post(route('membership.checkout'), [
        'plan_id' => $plan->id,
    ]);

    $response->assertOk();
    $response->assertSee('Choose a payment method');
    $response->assertSee('Pay with Razorpay');
    $response->assertSee('Pay with PhonePe');

    // No Subscription created yet — that happens only after gateway picked.
    expect(\DB::table('subscriptions')->where('user_id', $user->id)->count())->toBe(0);
});

it('rejects a stale picker submission for a now-disabled gateway', function () {
    $user = makeMembershipCheckoutUser(8003);
    $plan = makeMembershipCheckoutPlan();

    // Only Razorpay enabled; "phonepe" was enabled when the picker was
    // rendered but admin has since disabled it.
    $mgr = app(PaymentGatewayManager::class);
    $mgr->register(new FakeWebGateway(slug: 'razorpay', name: 'Razorpay'));

    $this->actingAs($user);

    $response = $this->post(route('membership.checkout'), [
        'plan_id' => $plan->id,
        'gateway' => 'phonepe',
    ]);

    $response->assertSessionHasErrors('payment');
    expect(\DB::table('subscriptions')->where('user_id', $user->id)->count())->toBe(0);
});

it('errors gracefully when no gateways are enabled at all', function () {
    $user = makeMembershipCheckoutUser(8004);
    $plan = makeMembershipCheckoutPlan();

    // Empty manager — no gateways registered.
    $this->actingAs($user);

    $response = $this->post(route('membership.checkout'), [
        'plan_id' => $plan->id,
    ]);

    $response->assertSessionHasErrors('payment');
});

/*
|--------------------------------------------------------------------------
| Per-gateway "Show in mobile app" filter
|--------------------------------------------------------------------------
| Inside the Android WebView app (User-Agent carries "KudlaMatrimonyApp"),
| checkout() drops any gateway whose services.{slug}.show_in_app is false.
| PhonePe defaults off (GatewayConfigProvider). Web requests are untouched
| (covered by the picker tests above). A safety net keeps app users from
| ever being left with zero options.
*/

it('hides PhonePe inside the Android app and auto-picks Razorpay', function () {
    $user = makeMembershipCheckoutUser(8005);
    $plan = makeMembershipCheckoutPlan();

    $mgr = app(PaymentGatewayManager::class);
    $mgr->register(new FakeWebGateway(slug: 'razorpay', name: 'Razorpay'));
    $mgr->register(new FakeWebGateway(slug: 'phonepe', name: 'PhonePe'));

    // PhonePe is hidden in the app by default. In production
    // GatewayConfigProvider sets this from site_settings; here we set it
    // directly because that provider skips when the site_settings table is
    // created after the app has already booted (as it is in these tests).
    config(['services.phonepe.show_in_app' => false]);

    $this->actingAs($user);

    // The app's WebView appends "KudlaMatrimonyApp/<version>" to the UA.
    $response = $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 13; wv) Chrome/120 Mobile KudlaMatrimonyApp/1.0.4')
        ->post(route('membership.checkout'), [
            'plan_id' => $plan->id,
        ]);

    // Only Razorpay remains → auto-pick (renders the Razorpay flow), no picker.
    $response->assertOk();
    $response->assertDontSee('Choose a payment method');
    $sub = \DB::table('subscriptions')->where('user_id', $user->id)->first();
    expect($sub)->not->toBeNull();
    expect($sub->gateway)->toBe('razorpay');
});

it('shows PhonePe in the app when the admin re-enables its app toggle', function () {
    $user = makeMembershipCheckoutUser(8006);
    $plan = makeMembershipCheckoutPlan();

    $mgr = app(PaymentGatewayManager::class);
    $mgr->register(new FakeWebGateway(slug: 'razorpay', name: 'Razorpay'));
    $mgr->register(new FakeWebGateway(slug: 'phonepe', name: 'PhonePe'));

    // Admin flipped "Show in mobile app" back on for PhonePe.
    config(['services.phonepe.show_in_app' => true]);

    $this->actingAs($user);

    $response = $this->withHeader('User-Agent', 'Android wv KudlaMatrimonyApp/1.0.4')
        ->post(route('membership.checkout'), [
            'plan_id' => $plan->id,
        ]);

    // Both visible → the picker renders with PhonePe present.
    $response->assertOk();
    $response->assertSee('Pay with PhonePe');
});

it('never strands app users with zero gateways when all are app-hidden', function () {
    $user = makeMembershipCheckoutUser(8007);
    $plan = makeMembershipCheckoutPlan();

    $mgr = app(PaymentGatewayManager::class);
    $mgr->register(new FakeWebGateway(slug: 'razorpay', name: 'Razorpay'));

    // Admin hid the only configured gateway in the app — the fallback must
    // keep it rather than show "no payment method available".
    config(['services.razorpay.show_in_app' => false]);

    $this->actingAs($user);

    $response = $this->withHeader('User-Agent', 'KudlaMatrimonyApp/1.0.4')
        ->post(route('membership.checkout'), [
            'plan_id' => $plan->id,
        ]);

    $response->assertOk();
    $response->assertSessionHasNoErrors();
    $sub = \DB::table('subscriptions')->where('user_id', $user->id)->first();
    expect($sub)->not->toBeNull();
    expect($sub->gateway)->toBe('razorpay');
});
