<?php

use App\Http\Controllers\Api\V1\MembershipController;
use App\Models\Coupon;
use App\Models\DailyInterestUsage;
use App\Models\MembershipPlan;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserMembership;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| MembershipController — /plans, /me, /coupon/validate
|--------------------------------------------------------------------------
| Inline Schema::create for membership_plans + user_memberships +
| coupons + coupon_usages + daily_interest_usage so the controller
| can run real queries against SQLite :memory: without needing the
| full project schema.
|
| Coupon validation goes through the real Coupon::validateFor() so
| these tests double as integration coverage of the model's 6-rule
| validation matrix.
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
*/

function createFullMembershipTables(): void
{
    if (! Schema::hasTable('membership_plans')) {
        Schema::create('membership_plans', function (Blueprint $t) {
            $t->id();
            $t->string('plan_name');
            $t->string('slug')->unique();
            $t->integer('duration_months')->default(0);
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
    if (! Schema::hasTable('user_memberships')) {
        Schema::create('user_memberships', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('plan_id');
            $t->boolean('is_active')->default(true);
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->timestamps();
            $t->index('user_id');
            $t->index('plan_id');
        });
    }
    if (! Schema::hasTable('coupons')) {
        Schema::create('coupons', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->string('code', 50)->unique();
            $t->string('description', 200)->nullable();
            $t->string('discount_type', 20);  // percentage | fixed
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
            $t->index('coupon_id');
            $t->index('user_id');
        });
    }
    if (! Schema::hasTable('daily_interest_usage')) {
        Schema::create('daily_interest_usage', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('profile_id');
            $t->date('usage_date');
            $t->integer('count')->default(0);
            $t->timestamps();
            $t->index(['profile_id', 'usage_date']);
        });
    }
}

function dropMembershipTables(): void
{
    Schema::dropIfExists('daily_interest_usage');
    Schema::dropIfExists('coupon_usages');
    Schema::dropIfExists('coupons');
    Schema::dropIfExists('user_memberships');
    Schema::dropIfExists('membership_plans');
}

/** Build a User+Profile pair (or no-profile variant). */
function buildMembershipUser(int $id = 4400, bool $withProfile = true): User
{
    $u = new User();
    $u->exists = true;
    $u->forceFill(['id' => $id, 'email' => "m{$id}@e.com", 'is_active' => true]);

    if ($withProfile) {
        $p = new Profile();
        $p->exists = true;
        $p->forceFill([
            'id' => $id,
            'user_id' => $id,
            'matri_id' => 'AM'.str_pad((string) $id, 6, '0', STR_PAD_LEFT),
            'gender' => 'male',
            'is_active' => true,
        ]);
        $p->setRelation('user', $u);
        $u->setRelation('profile', $p);
    } else {
        $u->setRelation('profile', null);
    }

    return $u;
}

/** Seed a plan row, return the model. */
function seedPlan(array $overrides = []): MembershipPlan
{
    return MembershipPlan::create(array_merge([
        'plan_name' => 'Test Plan',
        'slug' => 'test-plan-'.uniqid(),
        'duration_months' => 1,
        'price_inr' => 999,
        'strike_price_inr' => 1999,
        'daily_interest_limit' => 20,
        'can_view_contact' => true,
        'is_active' => true,
        'sort_order' => 1,
    ], $overrides));
}

function membershipRequest(User $user, string $method = 'GET', array $body = [], string $path = '/api/v1/membership/me'): Request
{
    $r = Request::create($path, $method, $body);
    $r->setUserResolver(fn () => $user);

    return $r;
}

beforeEach(function () {
    createFullMembershipTables();
});

afterEach(function () {
    dropMembershipTables();
});

/* ==================================================================
 |  GET /membership/plans (public)
 | ================================================================== */

it('plans returns only active plans', function () {
    seedPlan(['plan_name' => 'Active', 'slug' => 'active', 'is_active' => true]);
    seedPlan(['plan_name' => 'Inactive', 'slug' => 'inactive', 'is_active' => false]);

    $response = app(MembershipController::class)->plans();
    $data = $response->getData(true)['data'];

    expect(count($data))->toBe(1);
    expect($data[0]['name'])->toBe('Active');
});

it('plans sorted by sort_order ascending', function () {
    seedPlan(['plan_name' => 'Third', 'slug' => 'third', 'sort_order' => 30]);
    seedPlan(['plan_name' => 'First', 'slug' => 'first', 'sort_order' => 10]);
    seedPlan(['plan_name' => 'Second', 'slug' => 'second', 'sort_order' => 20]);

    $response = app(MembershipController::class)->plans();
    $names = collect($response->getData(true)['data'])->pluck('name')->all();

    expect($names)->toBe(['First', 'Second', 'Third']);
});

it('plans response includes the new tier flags + discount_pct', function () {
    seedPlan([
        'plan_name' => 'Diamond Plus',
        'slug' => 'diamond-plus',
        'price_inr' => 600,
        'strike_price_inr' => 1000,
        'allows_free_member_chat' => true,
        'exposes_contact_to_free' => true,
    ]);

    $response = app(MembershipController::class)->plans();
    $plan = $response->getData(true)['data'][0];

    expect($plan)->toHaveKeys([
        'id', 'slug', 'name', 'duration_months',
        'price_inr', 'strike_price_inr', 'discount_pct',
        'daily_interest_limit', 'view_contacts_limit', 'daily_contact_views',
        'can_view_contact', 'personalized_messages',
        'allows_free_member_chat', 'exposes_contact_to_free',
        'featured_profile', 'priority_support',
        'is_popular', 'is_highlighted',
        'features',
    ]);
    expect($plan['allows_free_member_chat'])->toBeTrue();
    expect($plan['exposes_contact_to_free'])->toBeTrue();
    expect($plan['discount_pct'])->toBe(40);  // (1000-600)/1000 = 40%
});

it('plans returns empty array when membership_plans table is missing', function () {
    Schema::dropIfExists('membership_plans');

    $response = app(MembershipController::class)->plans();

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data'])->toBe([]);
});

/* ==================================================================
 |  GET /membership/me (auth)
 | ================================================================== */

it('me returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildMembershipUser(withProfile: false);

    $response = app(MembershipController::class)->mine(membershipRequest($user));

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

it('me returns Free defaults when no active membership', function () {
    $user = buildMembershipUser(4401);

    $response = app(MembershipController::class)->mine(membershipRequest($user));
    $data = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(200);
    expect($data['membership']['plan_name'])->toBe('Free');
    expect($data['membership']['is_premium'])->toBeFalse();
    expect($data['membership']['plan_id'])->toBeNull();
    expect($data['membership']['ends_at'])->toBeNull();
    expect($data['membership']['days_remaining'])->toBeNull();
    expect($data['usage_today']['interests_limit'])->toBe(
        (int) config('matrimony.daily_interest_limit_free', 5)
    );
});

it('me returns full block when user has active membership', function () {
    $user = buildMembershipUser(4402);

    $plan = seedPlan(['plan_name' => 'Diamond', 'slug' => 'diamond', 'daily_interest_limit' => 50, 'daily_contact_views' => 30]);
    UserMembership::create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'is_active' => true,
        'starts_at' => Carbon::now()->subMonths(1),
        'ends_at' => Carbon::now()->addMonths(2),
    ]);

    $response = app(MembershipController::class)->mine(membershipRequest($user));
    $m = $response->getData(true)['data']['membership'];
    $u = $response->getData(true)['data']['usage_today'];

    expect($m['plan_name'])->toBe('Diamond');
    expect($m['is_premium'])->toBeTrue();
    expect($m['days_remaining'])->toBeGreaterThan(0);
    expect($u['interests_limit'])->toBe(50);
    expect($u['contacts_limit'])->toBe(30);
});

it('me reflects today interests_sent count from daily_interest_usage', function () {
    $user = buildMembershipUser(4403);

    DailyInterestUsage::create([
        'profile_id' => $user->profile->id,
        'usage_date' => Carbon::today(),
        'count' => 7,
    ]);

    $response = app(MembershipController::class)->mine(membershipRequest($user));

    expect($response->getData(true)['data']['usage_today']['interests_sent'])->toBe(7);
});

/* ==================================================================
 |  POST /membership/coupon/validate (auth)
 | ================================================================== */

it('validateCoupon throws ValidationException on missing fields', function () {
    $user = buildMembershipUser();

    expect(fn () => app(MembershipController::class)->validateCoupon(
        membershipRequest($user, 'POST', body: ['coupon_code' => 'X'], path: '/api/v1/membership/coupon/validate'),
    ))->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(fn () => app(MembershipController::class)->validateCoupon(
        membershipRequest($user, 'POST', body: ['plan_id' => 1], path: '/api/v1/membership/coupon/validate'),
    ))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('validateCoupon returns 404 NOT_FOUND when plan id does not exist', function () {
    $user = buildMembershipUser();

    $response = app(MembershipController::class)->validateCoupon(
        membershipRequest($user, 'POST', body: [
            'plan_id' => 9999,
            'coupon_code' => 'WELCOME20',
        ], path: '/api/v1/membership/coupon/validate'),
    );

    expect($response->getStatusCode())->toBe(404);
    expect($response->getData(true)['error']['code'])->toBe('NOT_FOUND');
});

it('validateCoupon returns 400 COUPON_INVALID when code does not exist', function () {
    $user = buildMembershipUser();
    $plan = seedPlan();

    $response = app(MembershipController::class)->validateCoupon(
        membershipRequest($user, 'POST', body: [
            'plan_id' => $plan->id,
            'coupon_code' => 'NONEXISTENT',
        ], path: '/api/v1/membership/coupon/validate'),
    );

    expect($response->getStatusCode())->toBe(400);
    expect($response->getData(true)['error']['code'])->toBe('COUPON_INVALID');
});

it('validateCoupon returns 400 with message from validateFor when expired', function () {
    $user = buildMembershipUser();
    $plan = seedPlan(['price_inr' => 1000]);

    Coupon::create([
        'code' => 'EXPIRED',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'usage_limit_per_user' => 1,
        'valid_until' => Carbon::now()->subDay(),  // yesterday — expired
        'is_active' => true,
    ]);

    $response = app(MembershipController::class)->validateCoupon(
        membershipRequest($user, 'POST', body: [
            'plan_id' => $plan->id,
            'coupon_code' => 'EXPIRED',
        ], path: '/api/v1/membership/coupon/validate'),
    );

    expect($response->getStatusCode())->toBe(400);
    expect($response->getData(true)['error']['code'])->toBe('COUPON_INVALID');
    expect($response->getData(true)['error']['message'])->toContain('expired');
});

it('validateCoupon returns 200 with correct discount math for percentage coupon', function () {
    $user = buildMembershipUser();
    $plan = seedPlan(['price_inr' => 1000]);  // ₹1000 → 100000 paise

    Coupon::create([
        'code' => 'WELCOME20',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'usage_limit_per_user' => 1,
        'is_active' => true,
    ]);

    $response = app(MembershipController::class)->validateCoupon(
        membershipRequest($user, 'POST', body: [
            'plan_id' => $plan->id,
            'coupon_code' => 'WELCOME20',
        ], path: '/api/v1/membership/coupon/validate'),
    );
    $data = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(200);
    expect($data['valid'])->toBeTrue();
    expect($data['discount_type'])->toBe('percentage');
    expect($data['discount_value'])->toBe(20);
    expect($data['original_amount_inr'])->toBe(1000);
    expect($data['discount_amount_inr'])->toBe(200);  // 20% of 1000
    expect($data['final_amount_inr'])->toBe(800);
});

it('validateCoupon returns 200 with correct discount math for fixed coupon', function () {
    $user = buildMembershipUser();
    $plan = seedPlan(['price_inr' => 1000]);

    Coupon::create([
        'code' => 'FLAT300',
        'discount_type' => 'fixed',
        'discount_value' => 30000,  // ₹300 in paise
        'usage_limit_per_user' => 1,
        'is_active' => true,
    ]);

    $response = app(MembershipController::class)->validateCoupon(
        membershipRequest($user, 'POST', body: [
            'plan_id' => $plan->id,
            'coupon_code' => 'FLAT300',
        ], path: '/api/v1/membership/coupon/validate'),
    );
    $data = $response->getData(true)['data'];

    expect($data['discount_type'])->toBe('fixed');
    expect($data['discount_value'])->toBe(300);  // exposed in INR for fixed
    expect($data['discount_amount_inr'])->toBe(300);
    expect($data['final_amount_inr'])->toBe(700);
});

it('validateCoupon is case-insensitive on the code', function () {
    $user = buildMembershipUser();
    $plan = seedPlan();

    Coupon::create([
        'code' => 'WELCOME20',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'usage_limit_per_user' => 1,
        'is_active' => true,
    ]);

    $response = app(MembershipController::class)->validateCoupon(
        membershipRequest($user, 'POST', body: [
            'plan_id' => $plan->id,
            'coupon_code' => 'welcome20',  // lowercase input
        ], path: '/api/v1/membership/coupon/validate'),
    );

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['valid'])->toBeTrue();
});
