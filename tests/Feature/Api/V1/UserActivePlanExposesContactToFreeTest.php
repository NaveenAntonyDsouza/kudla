<?php

use App\Models\MembershipPlan;
use App\Models\User;
use App\Models\UserMembership;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| User::activePlanExposesContactToFree() — Plus-tier flag check
|--------------------------------------------------------------------------
| Locks the helper that backs the Shaadi.com Plus convention: free members
| can VIEW the plan-holder's phone + email on the profile page when this
| flag is set on the holder's active plan.
|
| Independent from activePlanAllowsFreeMemberChat — the two flags can
| be combined (typical Plus tier has both) or used independently. This
| test is a structural mirror of the chat-flag test, keeping the
| coverage surface symmetrical.
|
| Reference research:
|   https://support.shaadi.com/support/solutions/articles/48000953202
*/

function createContactExposeTables(): void
{
    if (! Schema::hasTable('membership_plans')) {
        Schema::create('membership_plans', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('plan_name');
            $t->boolean('can_view_contact')->default(false);
            $t->boolean('allows_free_member_chat')->default(false);
            $t->boolean('exposes_contact_to_free')->default(false);
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
}

function buildContactExposeUser(int $id = 5500): User
{
    $user = new User();
    $user->exists = true;
    $user->forceFill([
        'id' => $id,
        'email' => "exposehelper{$id}@example.com",
        'phone' => "98000000{$id}",
        'is_active' => true,
    ]);

    return $user;
}

function seedContactExposureMembership(
    int $userId,
    bool $exposesToFree,
    ?Carbon $endsAt = null,
    bool $membershipActive = true,
    string $planSlug = 'test-plan-expose',
): int {
    $plan = MembershipPlan::create([
        'slug' => $planSlug,
        'plan_name' => ucfirst(str_replace('-', ' ', $planSlug)),
        'can_view_contact' => true,
        'allows_free_member_chat' => false,  // independent — set false to prove orthogonality
        'exposes_contact_to_free' => $exposesToFree,
        'is_active' => true,
    ]);

    return UserMembership::create([
        'user_id' => $userId,
        'plan_id' => $plan->id,
        'is_active' => $membershipActive,
        'starts_at' => Carbon::now()->subMonths(1),
        'ends_at' => $endsAt,
    ])->id;
}

beforeEach(function () {
    createContactExposeTables();
});

afterEach(function () {
    Schema::dropIfExists('user_memberships');
    Schema::dropIfExists('membership_plans');
});

/* ==================================================================
 |  No membership / wrong-plan / right-plan — the three core states
 | ================================================================== */

it('returns false when the user has no UserMembership row', function () {
    $user = buildContactExposeUser();

    expect($user->activePlanExposesContactToFree())->toBeFalse();
});

it('returns false when the active plan does not expose contact to free', function () {
    $user = buildContactExposeUser();
    seedContactExposureMembership($user->id, exposesToFree: false, planSlug: 'standard');

    expect($user->activePlanExposesContactToFree())->toBeFalse();
});

it('returns true when the active plan exposes contact to free', function () {
    $user = buildContactExposeUser();
    seedContactExposureMembership($user->id, exposesToFree: true, planSlug: 'diamond-plus');

    expect($user->activePlanExposesContactToFree())->toBeTrue();
});

/* ==================================================================
 |  Time + activity gating
 | ================================================================== */

it('returns false when the membership has expired (ends_at past)', function () {
    $user = buildContactExposeUser();
    seedContactExposureMembership(
        $user->id,
        exposesToFree: true,
        endsAt: Carbon::now()->subDay(),
        planSlug: 'expired-diamond-plus',
    );

    expect($user->activePlanExposesContactToFree())->toBeFalse();
});

it('returns false when the membership is_active flag is false', function () {
    $user = buildContactExposeUser();
    seedContactExposureMembership(
        $user->id,
        exposesToFree: true,
        membershipActive: false,
        planSlug: 'cancelled-diamond-plus',
    );

    expect($user->activePlanExposesContactToFree())->toBeFalse();
});

it('returns true when ends_at is in the future', function () {
    $user = buildContactExposeUser();
    seedContactExposureMembership(
        $user->id,
        exposesToFree: true,
        endsAt: Carbon::now()->addMonths(3),
        planSlug: 'active-diamond-plus',
    );

    expect($user->activePlanExposesContactToFree())->toBeTrue();
});

/* ==================================================================
 |  Defensive — missing tables → false (no exception thrown)
 | ================================================================== */

it('returns false defensively when user_memberships table is missing', function () {
    Schema::dropIfExists('user_memberships');
    Schema::dropIfExists('membership_plans');

    $user = buildContactExposeUser();

    expect(fn () => $user->activePlanExposesContactToFree())
        ->not->toThrow(\Throwable::class);
    expect($user->activePlanExposesContactToFree())->toBeFalse();
});

/* ==================================================================
 |  Orthogonality lock — flag is independent from allows_free_member_chat
 | ================================================================== */

it('is independent from activePlanAllowsFreeMemberChat (orthogonal flags)', function () {
    $user = buildContactExposeUser();
    // Plan with exposes_contact_to_free=true but allows_free_member_chat=false.
    seedContactExposureMembership($user->id, exposesToFree: true, planSlug: 'contact-only');

    expect($user->activePlanExposesContactToFree())->toBeTrue();
    // The companion helper should report false — proves the flags are
    // independently checked.
    expect($user->activePlanAllowsFreeMemberChat())->toBeFalse();
});
