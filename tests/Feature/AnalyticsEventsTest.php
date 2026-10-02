<?php

use App\Http\Controllers\MembershipController;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Analytics;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Key-moment analytics (GA4 / GTM / Meta Pixel / PostHog)
|--------------------------------------------------------------------------
| Controllers queue events in the session; the tracking partial sends them
| once from the next page, only to the tools the site has switched on, and
| never with personal data.
*/

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('phone')->nullable();
        $t->string('password')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->boolean('is_active')->default(true);
        $t->timestamp('last_login_at')->nullable();
        $t->integer('reengagement_level')->default(0);
        $t->rememberToken();
        $t->timestamps();
    });
    foreach (['profiles' => fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('user_id'), $t->unsignedBigInteger('branch_id')->nullable(),
                  $t->string('matri_id')->nullable(), $t->boolean('is_active')->default(true), $t->string('suspension_status')->nullable(),
                  $t->boolean('onboarding_completed')->default(true), $t->integer('onboarding_step_completed')->default(5), $t->softDeletes(), $t->timestamps()],
              'login_history' => fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('user_id'), $t->string('login_method')->nullable(),
                  $t->string('ip_address')->nullable(), $t->text('user_agent')->nullable(), $t->timestamp('logged_in_at')->nullable(), $t->timestamps()],
              'site_settings' => fn (Blueprint $t) => [$t->id(), $t->string('key')->unique(), $t->text('value')->nullable(), $t->timestamps()],
              'membership_plans' => fn (Blueprint $t) => [$t->id(), $t->string('plan_name')->nullable(), $t->timestamps()],
             ] as $table => $cols) {
        Schema::create($table, function (Blueprint $t) use ($cols) { $cols($t); });
    }
    Cache::flush();
});

afterEach(function () {
    foreach (['users', 'profiles', 'login_history', 'site_settings', 'membership_plans'] as $t) {
        Schema::dropIfExists($t);
    }
});

/** Give the current request a started session, as the StartSession middleware does. */
function withBrowserSession(): void
{
    $store = app('session')->driver();
    $store->start();
    request()->setLaravelSession($store);
}

/** Render just the tracking partial with the given IDs. */
function renderTracking(array $ids = []): string
{
    return view('components.partials.tracking-head', array_merge(
        ['gaId' => '', 'gtmId' => '', 'fbPixelId' => '', 'posthogKey' => '', 'posthogHost' => 'https://us.i.posthog.com'],
        $ids,
    ))->render();
}

it('queues events and the next page sends them once, to the switched-on tools', function () {
    withBrowserSession();
    Analytics::track('interest_sent', [], 'InterestSent');
    Analytics::track('purchase', ['value' => 499, 'currency' => 'INR'], 'Purchase');

    $html = renderTracking(['gaId' => 'G-TEST123', 'fbPixelId' => '123456']);

    expect($html)->toContain("gtag('event', e.name, e.params)")
        ->and($html)->toContain("fbq(e.meta_standard ? 'track' : 'trackCustom', e.meta, e.params)")
        ->and($html)->toContain('interest_sent')
        ->and($html)->toContain('InterestSent')
        ->and($html)->not->toContain('posthog.capture(e.name')   // PostHog not switched on
        ->and($html)->not->toContain('dataLayer.push(Object.assign'); // nor GTM

    // Sent once: the next page has nothing left to send
    expect(renderTracking(['gaId' => 'G-TEST123']))->not->toContain('interest_sent');
});

it('clears queued events even when no analytics tool is configured', function () {
    withBrowserSession();
    Analytics::track('login', ['method' => 'password']);

    expect(renderTracking())->not->toContain('login')
        ->and(session(Analytics::SESSION_KEY))->toBeNull();
});

it('does nothing outside a browser request (console, queue, webhooks)', function () {
    app()->instance('request', \Illuminate\Http\Request::create('/api/webhook', 'POST')); // no session
    Analytics::track('purchase', ['value' => 1]); // must not throw

    expect(app('session')->driver()->get(Analytics::SESSION_KEY))->toBeNull();
});

it('a password login records a login event with the method only', function () {
    User::create(['name' => 'M', 'email' => 'm@site.test', 'password' => Hash::make('secret123'), 'role' => 'user']);
    DB::table('profiles')->insert(['user_id' => 1, 'matri_id' => 'KM1', 'created_at' => now(), 'updated_at' => now()]);

    $this->post('/login', ['login' => 'm@site.test', 'password' => 'secret123'])->assertRedirect();

    expect(session(Analytics::SESSION_KEY))->toBe([
        ['name' => 'login', 'params' => ['method' => 'password'], 'meta' => null, 'meta_standard' => false],
    ]);
});

it('a purchase records value in rupees, the order id and the plan — nothing personal', function () {
    withBrowserSession();
    DB::table('membership_plans')->insert(['id' => 3, 'plan_name' => 'Gold']);
    $sub = new Subscription(['plan_id' => 3, 'amount' => 149900, 'plan_name' => 'Gold']);
    $sub->id = 77;

    $track = new ReflectionMethod(MembershipController::class, 'trackPurchase');
    $track->invoke(app(MembershipController::class), $sub);

    $event = session(Analytics::SESSION_KEY)[0];
    expect($event['name'])->toBe('purchase')
        ->and($event['meta'])->toBe('Purchase')
        ->and($event['meta_standard'])->toBeTrue()
        ->and($event['params']['value'])->toEqual(1499.0)
        ->and($event['params']['currency'])->toBe('INR')
        ->and($event['params']['transaction_id'])->toBe('77')
        ->and($event['params']['items'][0]['item_name'])->toBe('Gold');
});
