<?php

use App\Models\Profile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Completed registrations first, half-filled ones after
|--------------------------------------------------------------------------
| Owner's choice (2026-10-02): unfinished profiles stay listed in search,
| discover, matches and the homepage, but after the complete ones — kudla
| had 112 unfinished profiles among 650 search results, 34 with only a
| name and date of birth.
*/

beforeEach(function () {
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id')->nullable();
        $t->string('matri_id')->nullable();
        $t->boolean('onboarding_completed')->default(false);
        $t->boolean('is_vip')->default(false);
        $t->softDeletes();
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
});

afterEach(function () {
    Schema::dropIfExists('profiles');
    Schema::dropIfExists('profile_photos');
});

function cfProfile(string $id, bool $completed, string $created, bool $vip = false): void
{
    DB::table('profiles')->insert([
        'matri_id' => $id, 'onboarding_completed' => $completed, 'is_vip' => $vip,
        'created_at' => $created, 'updated_at' => $created,
    ]);
}

it('lists completed profiles before unfinished ones, then by the chosen sort', function () {
    cfProfile('OLD-DONE', true, '2026-01-01');
    cfProfile('NEW-HALF', false, '2026-10-01');   // newest, but unfinished
    cfProfile('NEW-DONE', true, '2026-09-01');
    cfProfile('VIP-HALF', false, '2026-02-01', vip: true);

    $order = Profile::query()->completedFirst()
        ->orderBy('is_vip', 'desc')->orderBy('created_at', 'desc')
        ->pluck('matri_id')->all();

    expect($order)->toBe(['NEW-DONE', 'OLD-DONE', 'VIP-HALF', 'NEW-HALF']);
});

it('ranks scored matches the same way: completed first, then best score', function () {
    $make = fn (string $id, bool $done, int $score) => tap(new Profile, function ($p) use ($id, $done, $score) {
        $p->matri_id = $id; $p->onboarding_completed = $done; $p->match_score = $score;
    });

    $sorted = Profile::sortCompletedFirst(collect([
        $make('HALF-95', false, 95),
        $make('DONE-60', true, 60),
        $make('DONE-80', true, 80),
        $make('HALF-70', false, 70),
    ]), 'match_score');

    expect($sorted->pluck('matri_id')->all())->toBe(['DONE-80', 'DONE-60', 'HALF-95', 'HALF-70']);
});

function cfPhoto(string $matriId, array $attrs = []): void
{
    DB::table('profile_photos')->insert(array_merge([
        'profile_id' => DB::table('profiles')->where('matri_id', $matriId)->value('id'),
        'is_primary' => true, 'is_visible' => true, 'approval_status' => 'approved',
    ], $attrs));
}

it('puts profiles with a photo before those without, after the paid boosts', function () {
    cfProfile('NEW-NOPHOTO', true, '2026-10-01');
    cfProfile('OLD-PHOTO', true, '2026-01-01');
    cfProfile('VIP-NOPHOTO', true, '2026-02-01', vip: true);
    cfProfile('PENDING-PHOTO', true, '2026-09-01');
    cfProfile('HALF-PHOTO', false, '2026-09-15');
    cfPhoto('OLD-PHOTO');
    cfPhoto('HALF-PHOTO');
    cfPhoto('PENDING-PHOTO', ['approval_status' => 'pending']); // not approved = no photo yet

    $order = Profile::query()->completedFirst()
        ->orderBy('is_vip', 'desc')->photoFirst()->orderBy('created_at', 'desc')
        ->pluck('matri_id')->all();

    expect($order)->toBe(['VIP-NOPHOTO', 'OLD-PHOTO', 'NEW-NOPHOTO', 'PENDING-PHOTO', 'HALF-PHOTO']);
});

it('ranks matches: finished, then with a photo, then best score', function () {
    $make = fn (string $id, bool $done, bool $photo, int $score) => tap(new Profile, function ($p) use ($id, $done, $photo, $score) {
        $p->matri_id = $id; $p->onboarding_completed = $done; $p->has_photo = $photo; $p->match_score = $score;
    });

    $sorted = Profile::sortCompletedFirst(collect([
        $make('DONE-NOPHOTO-95', true, false, 95),
        $make('HALF-PHOTO-99', false, true, 99),
        $make('DONE-PHOTO-60', true, true, 60),
        $make('DONE-PHOTO-80', true, true, 80),
    ]), 'match_score');

    expect($sorted->pluck('matri_id')->all())->toBe(['DONE-PHOTO-80', 'DONE-PHOTO-60', 'DONE-NOPHOTO-95', 'HALF-PHOTO-99']);
});

it('default order: paid boosts, then finished, then with photo, then recently active, then newest', function () {
    Schema::table('profiles', fn (Blueprint $t) => $t->boolean('is_featured')->default(false));
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->timestamp('last_login_at')->nullable();
    });
    Schema::create('membership_plans', function (Blueprint $t) {
        $t->id();
        $t->boolean('is_highlighted')->default(false);
    });
    Schema::create('user_memberships', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->unsignedBigInteger('plan_id');
        $t->boolean('is_active')->default(true);
        $t->timestamp('ends_at')->nullable();
    });
    DB::table('membership_plans')->insert(['id' => 1, 'is_highlighted' => false]);

    $add = function (string $id, bool $done, array $p = [], ?string $login = null, bool $photo = false, bool $paid = false) {
        $uid = DB::table('users')->insertGetId(['last_login_at' => $login]);
        DB::table('profiles')->insert(array_merge([
            'user_id' => $uid, 'matri_id' => $id, 'onboarding_completed' => $done,
            'created_at' => '2026-09-01', 'updated_at' => '2026-09-01',
        ], $p));
        if ($photo) {
            cfPhoto($id);
        }
        if ($paid) {
            DB::table('user_memberships')->insert(['user_id' => $uid, 'plan_id' => 1, 'is_active' => true, 'ends_at' => now()->addMonth()]);
        }
    };
    $add('DONE-NOPHOTO', true);
    $add('HALF-PHOTO', false, photo: true);
    $add('DONE-PHOTO-OLDLOGIN', true, login: '2026-01-01', photo: true);
    $add('DONE-PHOTO-RECENT', true, login: '2026-09-30', photo: true);
    $add('PREMIUM-DONE', true, paid: true);
    $add('FEATURED-DONE', true, ['is_featured' => true]);
    $add('VIP-HALF', false, ['is_vip' => true]);   // paid boost beats "finished"

    expect(Profile::query()->defaultOrder()->pluck('matri_id')->all())->toBe([
        'VIP-HALF', 'FEATURED-DONE', 'PREMIUM-DONE',
        'DONE-PHOTO-RECENT', 'DONE-PHOTO-OLDLOGIN', 'DONE-NOPHOTO',
        'HALF-PHOTO',
    ]);

    foreach (['users', 'membership_plans', 'user_memberships'] as $t) {
        Schema::dropIfExists($t);
    }
});
