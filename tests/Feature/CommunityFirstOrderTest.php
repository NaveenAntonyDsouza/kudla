<?php

use App\Models\Profile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Same community first in the default order (owner's choice 2026-10-05)
|--------------------------------------------------------------------------
| The viewer's caste (their partner-preference castes if they picked a few,
| else their own), then the rest of their religion, then everyone else —
| paid boosts, finished, photo, active, newest inside each group.
| Christians: denomination instead of caste. Guests: unchanged.
*/

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->timestamp('last_login_at')->nullable();
        $t->timestamps();
    });
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id')->nullable();
        $t->string('matri_id')->nullable();
        $t->boolean('onboarding_completed')->default(true);
        $t->boolean('is_vip')->default(false);
        $t->boolean('is_featured')->default(false);
        $t->softDeletes();
        $t->timestamps();
    });
    Schema::create('religious_info', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('religion')->nullable();
        $t->string('caste')->nullable();
        $t->string('denomination')->nullable();
        $t->timestamps();
    });
    Schema::create('partner_preferences', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->json('caste')->nullable();
        $t->json('denomination')->nullable();
        $t->timestamps();
    });
    Schema::create('profile_photos', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->boolean('is_primary')->default(false);
        $t->boolean('is_visible')->default(true);
        $t->string('approval_status')->default('approved');
        $t->timestamps();
    });
    Schema::create('user_memberships', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->unsignedBigInteger('plan_id');
        $t->boolean('is_active')->default(true);
        $t->timestamp('ends_at')->nullable();
        $t->timestamps();
    });
    Schema::create('membership_plans', function (Blueprint $t) {
        $t->id();
        $t->boolean('is_highlighted')->default(false);
        $t->timestamps();
    });
});

afterEach(function () {
    foreach (['users', 'profiles', 'religious_info', 'partner_preferences', 'profile_photos', 'user_memberships', 'membership_plans'] as $table) {
        Schema::dropIfExists($table);
    }
});

function coMember(string $matri, ?string $religion, ?string $caste = null, ?string $denomination = null, array $profile = [], ?array $wantCastes = null, ?array $wantDenominations = null): Profile
{
    $userId = DB::table('users')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
    $id = DB::table('profiles')->insertGetId(array_merge(['user_id' => $userId, 'matri_id' => $matri, 'created_at' => now(), 'updated_at' => now()], $profile));
    DB::table('religious_info')->insert(['profile_id' => $id, 'religion' => $religion, 'caste' => $caste, 'denomination' => $denomination]);
    if ($wantCastes !== null || $wantDenominations !== null) {
        DB::table('partner_preferences')->insert(['profile_id' => $id, 'caste' => json_encode($wantCastes), 'denomination' => json_encode($wantDenominations)]);
    }

    return Profile::find($id);
}

function coOrder(?Profile $viewer): array
{
    return Profile::query()->when($viewer, fn ($q) => $q->where('profiles.id', '!=', $viewer->id))
        ->defaultOrder($viewer)->pluck('matri_id')->all();
}

function coPeople(): void
{
    coMember('HINDU-BUNT', 'Hindu', 'Bunts', profile: ['created_at' => now()->subDays(1)]);
    coMember('CHRISTIAN-RC', 'Christian', null, 'Roman Catholic', ['created_at' => now()->subDays(2)]);
    coMember('HINDU-BILLAVA', 'Hindu', 'Billava', profile: ['created_at' => now()->subDays(3)]);
    coMember('HINDU-OTHER', 'Hindu', 'Other / Not Listed', profile: ['created_at' => now()->subDays(4)]);
    coMember('CHRISTIAN-CSI', 'Christian', null, 'CSI', ['created_at' => now()->subDays(5)]);
    coMember('VIP-HINDU-KULAL', 'Hindu', 'Kulal', profile: ['is_vip' => true, 'created_at' => now()->subDays(6)]);
}

it('puts the viewer\'s own caste first, then their religion, paid members first inside each group', function () {
    coPeople();
    $viewer = coMember('ME', 'Hindu', 'Billava');

    expect(coOrder($viewer))->toBe(['HINDU-BILLAVA', 'VIP-HINDU-KULAL', 'HINDU-BUNT', 'HINDU-OTHER', 'CHRISTIAN-RC', 'CHRISTIAN-CSI']);
});

it('uses the castes they asked for in partner preferences, unless they ticked nearly all', function () {
    coPeople();
    $picky = coMember('ME', 'Hindu', 'Billava', wantCastes: ['Bunts']);
    expect(coOrder($picky)[0])->toBe('HINDU-BUNT');

    // Ticked 30 castes = open to all → their own caste (Billava, incl. ME above) first
    $open = coMember('ME2', 'Hindu', 'Billava', wantCastes: array_map(fn ($i) => "Caste {$i}", range(1, 30)));
    expect(array_slice(coOrder($open), 0, 2))->toEqualCanonicalizing(['HINDU-BILLAVA', 'ME']);
});

it('never groups on "Other / Not Listed"', function () {
    coPeople();
    $viewer = coMember('ME', 'Hindu', 'Other / Not Listed');

    // No caste group: Hindus first (VIP first among them), then Christians
    expect(coOrder($viewer))->toBe(['VIP-HINDU-KULAL', 'HINDU-BUNT', 'HINDU-BILLAVA', 'HINDU-OTHER', 'CHRISTIAN-RC', 'CHRISTIAN-CSI']);
});

it('uses denomination for Christians', function () {
    coPeople();
    $viewer = coMember('ME', 'Christian', null, 'Roman Catholic');

    expect(array_slice(coOrder($viewer), 0, 2))->toBe(['CHRISTIAN-RC', 'CHRISTIAN-CSI']);
});

it('leaves the order unchanged for guests', function () {
    coPeople();

    expect(coOrder(null))->toBe(['VIP-HINDU-KULAL', 'HINDU-BUNT', 'CHRISTIAN-RC', 'HINDU-BILLAVA', 'HINDU-OTHER', 'CHRISTIAN-CSI']);
});
