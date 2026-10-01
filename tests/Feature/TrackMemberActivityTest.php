<?php

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| TrackMemberActivity — last_login_at means "last active"
|--------------------------------------------------------------------------
| OTP logins use remember-me and registration logs in without a "login",
| so members used the site while last_login_at stayed old/empty — and got
| "we miss you" emails. Any signed-in member request now refreshes it
| (throttled), on web and API. Staff/admin are left alone.
*/

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('password')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->boolean('is_active')->default(true);
        $t->timestamp('last_login_at')->nullable();
        $t->integer('reengagement_level')->default(0);
        $t->rememberToken();
        $t->timestamps();
    });

    Route::middleware(['web', 'auth'])->get('/__activity-probe', fn () => 'ok');
    Route::middleware('web')->get('/__activity-probe-guest', fn () => 'ok');
    Route::middleware(['api', 'auth:sanctum'])->get('/api/__activity-probe', fn () => response()->json(['ok' => true]));
});

afterEach(function () {
    Schema::dropIfExists('users');
});

function activityMember(array $attrs = []): User
{
    $user = User::create(array_merge([
        'name' => 'M', 'email' => 'm@site.test', 'password' => 'x', 'role' => 'user', 'is_active' => true,
    ], $attrs));
    // Simulate an old row: updated_at in the past so we can tell it isn't touched
    DB::table('users')->where('id', $user->id)->update(['updated_at' => now()->subDays(10)]);

    return $user->fresh();
}

function activityRow(User $user): object
{
    return DB::table('users')->where('id', $user->id)->first();
}

it('records activity for a signed-in member who never logged in (registered / remember-me)', function () {
    $user = activityMember(['last_login_at' => null, 'reengagement_level' => 2]);

    $this->actingAs($user)->get('/__activity-probe')->assertOk();

    $row = activityRow($user);
    expect($row->last_login_at)->not->toBeNull()
        ->and(now()->diffInSeconds($row->last_login_at, true))->toBeLessThan(60)
        ->and((int) $row->reengagement_level)->toBe(0)
        // query-builder update: the profile row's updated_at is not touched
        ->and($row->updated_at)->toBe($user->updated_at->format('Y-m-d H:i:s'));
});

it('refreshes a stale last-active time', function () {
    $user = activityMember(['last_login_at' => now()->subDays(20), 'reengagement_level' => 1]);

    $this->actingAs($user)->get('/__activity-probe')->assertOk();

    $row = activityRow($user);
    expect(now()->diffInSeconds($row->last_login_at, true))->toBeLessThan(60)
        ->and((int) $row->reengagement_level)->toBe(0);
});

it('writes at most once per throttle window', function () {
    $recent = now()->subMinutes(5)->startOfSecond();
    $user = activityMember(['last_login_at' => $recent]);

    $this->actingAs($user)->get('/__activity-probe')->assertOk();

    expect(activityRow($user)->last_login_at)->toBe($recent->format('Y-m-d H:i:s'));
});

it('leaves staff and the site admin alone', function () {
    $old = now()->subDays(3)->startOfSecond();
    $staff = activityMember(['email' => 'staff@site.test', 'staff_role_id' => 7, 'last_login_at' => $old]);
    $admin = activityMember(['email' => 'admin@site.test', 'role' => 'admin', 'last_login_at' => $old]);

    $this->actingAs($staff)->get('/__activity-probe')->assertOk();
    $this->actingAs($admin)->get('/__activity-probe')->assertOk();

    expect(activityRow($staff)->last_login_at)->toBe($old->format('Y-m-d H:i:s'))
        ->and(activityRow($admin)->last_login_at)->toBe($old->format('Y-m-d H:i:s'));
});

it('does nothing for guests', function () {
    $this->get('/__activity-probe-guest')->assertOk();
});

it('records activity for app (API token) requests too', function () {
    $user = activityMember(['last_login_at' => now()->subDays(9)]);
    Sanctum::actingAs($user);

    $this->getJson('/api/__activity-probe')->assertOk();

    expect(now()->diffInSeconds(activityRow($user)->last_login_at, true))->toBeLessThan(60);
});
