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
              'subscriptions' => fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('user_id'), $t->string('plan_id')->nullable(), $t->string('plan_name')->nullable(),
                  $t->string('gateway')->nullable(), $t->unsignedBigInteger('coupon_id')->nullable(), $t->string('coupon_code')->nullable(), $t->integer('discount_amount')->default(0),
                  $t->integer('amount')->default(0), $t->string('payment_status')->default('pending'), $t->json('gateway_metadata')->nullable(),
                  $t->date('starts_at')->nullable(), $t->date('expires_at')->nullable(), $t->boolean('is_active')->default(false), $t->timestamps()],
             ] as $table => $cols) {
        Schema::create($table, function (Blueprint $t) use ($cols) { $cols($t); });
    }
    Cache::flush();
});

afterEach(function () {
    foreach (['users', 'profiles', 'login_history', 'site_settings', 'membership_plans', 'subscriptions'] as $t) {
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

    expect($html)->toContain("gtag('event', e.name, p)")
        ->and($html)->toContain("fbq(e.meta_standard ? 'track' : 'trackCustom', e.meta, p)")
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

it('every member-facing page with its own <head> loads the tracking tags', function () {
    // Pages that don't use a layout must include the partial themselves, or
    // GA never counts them (registration step 1 was missed this way).
    $exempt = [
        'components/partials/tracking-head.blade.php', // the partial itself
        'emails/', 'scribe/', 'welcome.blade.php',     // emails, API docs, unused stock page
        'membership/checkout.blade.php',               // instant hand-off to Razorpay
        'profile/print.blade.php', 'unsubscribe/',     // print view; one-click unsubscribe
    ];
    $missing = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS)) as $file) {
        $rel = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen(resource_path('views')) + 1));
        if (! str_ends_with($rel, '.blade.php') || collect($exempt)->contains(fn ($e) => str_starts_with($rel, $e))) {
            continue;
        }
        $src = file_get_contents($file->getPathname());
        if (preg_match('/<head[\s>]/i', $src) && ! str_contains($src, 'tracking-head')) {
            $missing[] = $rel;
        }
    }

    expect($missing)->toBe([]);
});

/*
| A payment the gateway's webhook finished (weekly review 23 Nov 2026: KM100793's
| Razorpay payment in the Android app never reached GA4, PostHog or Meta).
*/
/** The tracking partial with the queued events' JSON unescaped, to read them. */
function trackedHtml(array $ids = []): string
{
    return str_replace('\u0022', '"', renderTracking($ids));
}

function paidByWebhook(int $userId = 1, int $amount = 100000): Subscription
{
    DB::table('membership_plans')->insertOrIgnore(['id' => 2, 'plan_name' => 'Silver']);
    $sub = Subscription::create(['user_id' => $userId, 'plan_id' => '2', 'plan_name' => 'Silver', 'gateway' => 'razorpay', 'amount' => $amount, 'payment_status' => 'pending']);
    app(\App\Services\Payment\SubscriptionActivator::class)->activate($sub); // no browser in a webhook

    return $sub->fresh();
}

it("a payment the webhook finished is sent as a purchase from the member's next page, once", function () {
    $member = User::create(['name' => 'M', 'email' => 'm@site.test', 'role' => 'user']);
    $sub = paidByWebhook($member->id);

    withBrowserSession();
    $this->actingAs($member);
    $html = trackedHtml(['gaId' => 'G-TEST123', 'posthogKey' => 'phc_test']);

    expect($sub->payment_status)->toBe('paid')
        ->and($html)->toContain('"purchase"')
        ->and($html)->toContain('"transaction_id":"'.$sub->id.'"')
        ->and($html)->toContain('"value":1000')
        ->and($html)->toContain('Silver')
        ->and($html)->not->toContain('m@site.test');

    expect(trackedHtml(['gaId' => 'G-TEST123']))->not->toContain('"purchase"');
});

it("counts a payment once when both the webhook and the member's browser finish it", function () {
    $member = User::create(['name' => 'M', 'email' => 'm@site.test', 'role' => 'user']);
    $sub = paidByWebhook($member->id);

    withBrowserSession();
    $this->actingAs($member);
    $track = new ReflectionMethod(MembershipController::class, 'trackPurchase');
    $track->invoke(app(MembershipController::class), $sub);   // the verify page, after the webhook
    $track->invoke(app(MembershipController::class), $sub);   // and a refresh of it

    $html = trackedHtml(['gaId' => 'G-TEST123']);
    expect(substr_count($html, '"transaction_id":"'.$sub->id.'"'))->toBe(1)
        ->and(trackedHtml(['gaId' => 'G-TEST123']))->not->toContain('"purchase"');
});

it("another member's page never sends someone else's purchase", function () {
    $buyer = User::create(['name' => 'B', 'email' => 'b@site.test', 'role' => 'user']);
    $other = User::create(['name' => 'O', 'email' => 'o@site.test', 'role' => 'user']);
    $sub = paidByWebhook($buyer->id);

    withBrowserSession();
    $this->actingAs($other);
    expect(trackedHtml(['gaId' => 'G-TEST123']))->not->toContain('"purchase"');

    $this->actingAs($buyer);
    expect(trackedHtml(['gaId' => 'G-TEST123']))->toContain('"transaction_id":"'.$sub->id.'"');
});

it('a payment that is not paid is never sent', function () {
    $member = User::create(['name' => 'M', 'email' => 'm@site.test', 'role' => 'user']);
    $sub = Subscription::create(['user_id' => $member->id, 'plan_id' => '2', 'amount' => 100000, 'payment_status' => 'failed']);
    \App\Support\PurchaseTracking::due($sub);

    withBrowserSession();
    $this->actingAs($member);
    expect(trackedHtml(['gaId' => 'G-TEST123']))->not->toContain('"purchase"');
});
