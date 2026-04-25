<?php

use App\Models\MembershipPlan;
use App\Models\User;
use App\Models\UserMembership;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| User::activePlanAllowsFreeMemberChat() — premium-tier flag check
|--------------------------------------------------------------------------
| Locks the helper that backs the BharatMatrimony-Platinum convention:
| free members can send custom-text interest messages and chat replies
| to a paid member if that member is on a plan with
| allows_free_member_chat=true.
|
| The helper is consumed by App\Services\InterestService::send and
| ::sendMessage. End-to-end behaviour of those service methods is
| covered by Bruno smoke in week-04 step-15 against a migrated MySQL
| instance — these tests cover only the helper's correctness.
|
| Reference research:
|   - https://www.bharatmatrimony.com/faq.php (Platinum tier rules)
|   - https://support.shaadi.com/support/solutions/articles/48000953202
|     (Plus-tier benefits)
*/

function createMembershipTables(): void
{
    if (! Schema::hasTable('membership_plans')) {
        Schema::create('membership_plans', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('plan_name');
            $t->boolean('can_view_contact')->default(false);
            $t->boolean('allows_free_member_chat')->default(false);
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

/** Build an in-memory User. The helper uses ->userMemberships() (relation
 *  query, which hits the DB), so we DON'T pre-set the relation — we let
 *  it query the inline table we set up in beforeEach. */
function buildHelperUser(int $id = 4400): User
{
    $user = new User();
    $user->exists = true;
    $user->forceFill([
        'id' => $id,
        'email' => "helper{$id}@example.com",
        'phone' => "98000000{$id}",
        'is_active' => true,
    ]);

    return $user;
}

/** Insert a plan + membership row pair, returning the membership's id. */
function seedMembership(
    int $userId,
    bool $allowsFreeChat,
    ?Carbon $endsAt = null,
    bool $membershipActive = true,
    string $planSlug = 'test-plan',
): int {
    $plan = MembershipPlan::create([
        'slug' => $planSlug,
        'plan_name' => ucfirst(str_replace('-', ' ', $planSlug)),
        'can_view_contact' => true,
        'allows_free_member_chat' => $allowsFreeChat,
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
    createMembershipTables();
});

afterEach(function () {
    Schema::dropIfExists('user_memberships');
    Schema::dropIfExists('membership_plans');
});

/* ==================================================================
 |  No membership → false
 | ================================================================== */

it('returns false when the user has no UserMembership row', function () {
    $user = buildHelperUser();

    expect($user->activePlanAllowsFreeMemberChat())->toBeFalse();
});

/* ==================================================================
 |  Active membership on a plan WITHOUT the flag → false
 | ================================================================== */

it('returns false when the active plan does not have allows_free_member_chat', function () {
    $user = buildHelperUser();
    seedMembership($user->id, allowsFreeChat: false, planSlug: 'standard');

    expect($user->activePlanAllowsFreeMemberChat())->toBeFalse();
});

/* ==================================================================
 |  Active membership on a plan WITH the flag → true
 | ================================================================== */

it('returns true when the active plan has allows_free_member_chat', function () {
    $user = buildHelperUser();
    seedMembership($user->id, allowsFreeChat: true, planSlug: 'diamond-plus');

    expect($user->activePlanAllowsFreeMemberChat())->toBeTrue();
});

/* ==================================================================
 |  Expired membership (ends_at in the past) → false
 | ================================================================== */

it('returns false when the membership has expired (ends_at in past)', function () {
    $user = buildHelperUser();
    seedMembership(
        $user->id,
        allowsFreeChat: true,
        endsAt: Carbon::now()->subDay(),  // expired yesterday
        planSlug: 'expired-diamond-plus',
    );

    expect($user->activePlanAllowsFreeMemberChat())->toBeFalse();
});

/* ==================================================================
 |  Inactive membership (is_active=false) → false
 | ================================================================== */

it('returns false when the membership is_active flag is false', function () {
    $user = buildHelperUser();
    seedMembership(
        $user->id,
        allowsFreeChat: true,
        membershipActive: false,
        planSlug: 'cancelled-diamond-plus',
    );

    expect($user->activePlanAllowsFreeMemberChat())->toBeFalse();
});

/* ==================================================================
 |  Future ends_at (still active) → true
 | ================================================================== */

it('returns true when ends_at is in the future', function () {
    $user = buildHelperUser();
    seedMembership(
        $user->id,
        allowsFreeChat: true,
        endsAt: Carbon::now()->addMonths(3),
        planSlug: 'active-diamond-plus',
    );

    expect($user->activePlanAllowsFreeMemberChat())->toBeTrue();
});

/* ==================================================================
 |  Defensive: missing tables → false (no exception thrown)
 | ================================================================== */

it('returns false defensively when user_memberships table is missing', function () {
    Schema::dropIfExists('user_memberships');
    Schema::dropIfExists('membership_plans');

    $user = buildHelperUser();

    // Must not throw — wrapped in try/catch in the helper. Defensive
    // behaviour mirrors the test-DB-safe pattern across the service layer.
    expect(fn () => $user->activePlanAllowsFreeMemberChat())
        ->not->toThrow(\Throwable::class);
    expect($user->activePlanAllowsFreeMemberChat())->toBeFalse();
});
