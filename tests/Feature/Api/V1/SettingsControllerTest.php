<?php

use App\Http\Controllers\Api\V1\SettingsController;
use App\Models\Profile;
use App\Models\User;
use App\Services\AuthService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| SettingsController — 7 endpoints
|--------------------------------------------------------------------------
| Inline `users` + `profiles` tables for real persistence (visibility +
| alerts + delete change DB rows we want to verify). Sanctum tokens are
| stubbed via the controller's `revokeOtherTokens` test seam +
| FakeAuthService — no personal_access_tokens table needed.
*/

class FakeSettingsAuthService extends AuthService
{
    public int $revokeAllCalledCount = 0;

    public function __construct() {}

    public function revokeAllTokens(User $user): int
    {
        $this->revokeAllCalledCount++;
        return 3;  // canned: pretend 3 tokens were revoked
    }
}

function buildSettingsController(?FakeSettingsAuthService $auth = null, int $stubbedOtherTokens = 0): SettingsController
{
    $auth ??= new FakeSettingsAuthService();

    return new class($auth, $stubbedOtherTokens) extends SettingsController {
        public function __construct(
            FakeSettingsAuthService $auth,
            private int $stubbedOtherTokens,
        ) {
            parent::__construct($auth);
        }

        protected function revokeOtherTokens(User $user): int
        {
            return $this->stubbedOtherTokens;
        }
    };
}

function buildSettingsUser(int $id = 5500, bool $withProfile = true, ?string $password = null, ?array $prefs = null, array $userOverrides = []): User
{
    $u = User::create(array_merge([
        'id' => $id,
        'name' => "User {$id}",
        'email' => "u{$id}@e.com",
        'phone' => '90000'.$id,
        'password' => $password ? Hash::make($password) : null,
        'is_active' => true,
        'phone_verified_at' => Carbon::parse('2026-04-01'),
        'email_verified_at' => null,
        'notification_preferences' => $prefs,
    ], $userOverrides));

    if ($withProfile) {
        Profile::create([
            'id' => $id,
            'user_id' => $u->id,
            'matri_id' => 'AM'.str_pad((string) $id, 6, '0', STR_PAD_LEFT),
            'gender' => 'male',
            'date_of_birth' => Carbon::parse('1995-01-01'),
            'is_active' => true,
            'is_approved' => true,
            'is_hidden' => false,
            'show_profile_to' => 'all',
            'only_same_religion' => false,
            'only_same_denomination' => false,
            'only_same_mother_tongue' => false,
        ]);
        $u->load('profile');
    }

    return $u;
}

function settingsRequest(User $user, string $method = 'GET', array $body = [], string $path = '/api/v1/settings'): Request
{
    $r = Request::create($path, $method, $body);
    $r->setUserResolver(fn () => $user);

    return $r;
}

beforeEach(function () {
    if (! Schema::hasTable('users')) {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->string('phone')->nullable();
            $t->string('role')->nullable();
            $t->unsignedBigInteger('staff_role_id')->nullable();
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->timestamp('phone_verified_at')->nullable();
            $t->timestamp('email_verified_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamp('last_login_at')->nullable();
            $t->timestamp('last_reengagement_sent_at')->nullable();
            $t->integer('reengagement_level')->default(0);
            $t->timestamp('last_weekly_match_sent_at')->nullable();
            $t->integer('nudges_sent_count')->default(0);
            $t->timestamp('last_nudge_sent_at')->nullable();
            $t->json('notification_preferences')->nullable();
            $t->timestamps();
        });
    }
    if (! Schema::hasTable('profiles')) {
        Schema::create('profiles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('matri_id', 20)->unique();
            $t->string('full_name')->nullable();
            $t->string('gender', 10)->nullable();
            $t->date('date_of_birth')->nullable();
            $t->string('marital_status', 30)->nullable();
            $t->integer('profile_completion_pct')->default(0);
            $t->boolean('is_approved')->default(true);
            $t->boolean('is_active')->default(true);
            $t->boolean('is_hidden')->default(false);
            $t->boolean('is_verified')->default(false);
            $t->boolean('is_vip')->default(false);
            $t->boolean('is_featured')->default(false);
            $t->string('suspension_status', 20)->default('active');
            $t->string('show_profile_to', 30)->default('all');
            $t->boolean('only_same_religion')->default(false);
            $t->boolean('only_same_denomination')->default(false);
            $t->boolean('only_same_mother_tongue')->default(false);
            $t->string('deletion_reason', 200)->nullable();
            $t->timestamp('deleted_at')->nullable();
            $t->timestamps();
        });
    }
});

afterEach(function () {
    Schema::dropIfExists('profiles');
    Schema::dropIfExists('users');
});

/* ==================================================================
 |  GET /settings
 | ================================================================== */

it('index returns 422 PROFILE_REQUIRED when viewer has no profile', function () {
    $user = buildSettingsUser(withProfile: false);

    $response = buildSettingsController()->index(settingsRequest($user));

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

it('index returns all 4 sections with sensible defaults', function () {
    $user = buildSettingsUser(password: 'secret123');

    $response = buildSettingsController()->index(settingsRequest($user));
    $data = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(200);
    expect($data)->toHaveKeys(['visibility', 'alerts', 'auth', 'account']);

    // Visibility — defaults
    expect($data['visibility']['show_profile_to'])->toBe('all');
    expect($data['visibility']['is_hidden'])->toBeFalse();

    // Alerts — informational true, promotional false (defaults), quiet hours null
    expect($data['alerts']['email_interest'])->toBeTrue();
    expect($data['alerts']['email_promotions'])->toBeFalse();
    expect($data['alerts']['push_views'])->toBeFalse();
    expect($data['alerts']['quiet_hours_start'])->toBeNull();
    expect($data['alerts']['quiet_hours_end'])->toBeNull();

    // Auth + account
    expect($data['auth']['has_password'])->toBeTrue();
    expect($data['account']['email'])->toBe('u5500@e.com');
    expect($data['account']['phone_verified'])->toBeTrue();
    expect($data['account']['email_verified'])->toBeFalse();
});

it('index reflects the users actual stored prefs', function () {
    $user = buildSettingsUser(prefs: [
        'email_interest' => false,
        'push_promotions' => true,
        'quiet_hours_start' => '22:00',
        'quiet_hours_end' => '07:00',
    ]);

    $alerts = buildSettingsController()->index(settingsRequest($user))->getData(true)['data']['alerts'];

    expect($alerts['email_interest'])->toBeFalse();
    expect($alerts['push_promotions'])->toBeTrue();
    expect($alerts['quiet_hours_start'])->toBe('22:00');
    expect($alerts['quiet_hours_end'])->toBe('07:00');
    // Untouched keys keep their default
    expect($alerts['email_accepted'])->toBeTrue();
});

/* ==================================================================
 |  PUT /settings/visibility
 | ================================================================== */

it('visibility accepts partial PATCH-style update', function () {
    $user = buildSettingsUser();

    $response = buildSettingsController()->visibility(settingsRequest(
        $user, 'PUT', body: ['only_same_religion' => true],
    ));

    expect($response->getStatusCode())->toBe(200);
    $user->profile->refresh();
    expect($user->profile->only_same_religion)->toBeTrue();
    expect($user->profile->show_profile_to)->toBe('all');  // unchanged
});

it('visibility validates show_profile_to enum', function () {
    $user = buildSettingsUser();

    expect(fn () => buildSettingsController()->visibility(settingsRequest(
        $user, 'PUT', body: ['show_profile_to' => 'gibberish'],
    )))->toThrow(\Illuminate\Validation\ValidationException::class);
});

/* ==================================================================
 |  PUT /settings/alerts
 | ================================================================== */

it('alerts merges with existing prefs (no clobber)', function () {
    $user = buildSettingsUser(prefs: [
        'email_interest' => false,
        'email_views' => true,  // we'll leave this alone — must survive
    ]);

    buildSettingsController()->alerts(settingsRequest(
        $user, 'PUT', body: ['email_interest' => true, 'push_promotions' => true],
    ));

    $user->refresh();
    expect($user->notification_preferences['email_interest'])->toBeTrue();
    expect($user->notification_preferences['push_promotions'])->toBeTrue();
    expect($user->notification_preferences['email_views'])->toBeTrue();  // preserved
});

it('alerts accepts and persists quiet_hours window', function () {
    $user = buildSettingsUser();

    buildSettingsController()->alerts(settingsRequest(
        $user, 'PUT', body: ['quiet_hours_start' => '22:30', 'quiet_hours_end' => '06:45'],
    ));

    $user->refresh();
    expect($user->notification_preferences['quiet_hours_start'])->toBe('22:30');
    expect($user->notification_preferences['quiet_hours_end'])->toBe('06:45');
});

it('alerts rejects invalid quiet_hours format', function () {
    $user = buildSettingsUser();

    expect(fn () => buildSettingsController()->alerts(settingsRequest(
        $user, 'PUT', body: ['quiet_hours_start' => '25:00'],  // invalid
    )))->toThrow(\Illuminate\Validation\ValidationException::class);
});

/* ==================================================================
 |  PUT /settings/password
 | ================================================================== */

it('password change requires correct current password', function () {
    $user = buildSettingsUser(password: 'correct-pass');

    $response = buildSettingsController()->password(settingsRequest($user, 'PUT', body: [
        'current_password' => 'wrong',
        'new_password' => 'newpass99',
        'new_password_confirmation' => 'newpass99',
    ]));

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('VALIDATION_FAILED');
    expect($response->getData(true)['error']['fields'])->toHaveKey('current_password');

    // Password unchanged on disk.
    expect(Hash::check('correct-pass', $user->fresh()->password))->toBeTrue();
});

it('password change updates password + reports revoked token count', function () {
    $user = buildSettingsUser(password: 'correct-pass');
    $controller = buildSettingsController(stubbedOtherTokens: 4);

    $response = $controller->password(settingsRequest($user, 'PUT', body: [
        'current_password' => 'correct-pass',
        'new_password' => 'newpass99',
        'new_password_confirmation' => 'newpass99',
    ]));
    $data = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(200);
    expect($data['password_changed'])->toBeTrue();
    expect($data['tokens_revoked_count'])->toBe(4);

    expect(Hash::check('newpass99', $user->fresh()->password))->toBeTrue();
});

it('password change validates min length', function () {
    $user = buildSettingsUser(password: 'correct-pass');

    expect(fn () => buildSettingsController()->password(settingsRequest($user, 'PUT', body: [
        'current_password' => 'correct-pass',
        'new_password' => '123',  // too short
        'new_password_confirmation' => '123',
    ])))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('password change validates confirmation matches', function () {
    $user = buildSettingsUser(password: 'correct-pass');

    expect(fn () => buildSettingsController()->password(settingsRequest($user, 'PUT', body: [
        'current_password' => 'correct-pass',
        'new_password' => 'newpass99',
        'new_password_confirmation' => 'different',
    ])))->toThrow(\Illuminate\Validation\ValidationException::class);
});

/* ==================================================================
 |  POST /settings/hide  +  /settings/unhide
 | ================================================================== */

it('hide sets profile is_hidden=true', function () {
    $user = buildSettingsUser();

    $response = buildSettingsController()->hide(settingsRequest($user, 'POST'));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['is_hidden'])->toBeTrue();
    expect($user->profile->fresh()->is_hidden)->toBeTrue();
});

it('unhide sets profile is_hidden=false', function () {
    $user = buildSettingsUser();
    $user->profile->update(['is_hidden' => true]);

    $response = buildSettingsController()->unhide(settingsRequest($user, 'POST'));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['is_hidden'])->toBeFalse();
    expect($user->profile->fresh()->is_hidden)->toBeFalse();
});

/* ==================================================================
 |  POST /settings/delete
 | ================================================================== */

it('delete requires password confirmation', function () {
    $user = buildSettingsUser(password: 'correct-pass');

    $response = buildSettingsController()->delete(settingsRequest($user, 'POST', body: [
        'password' => 'wrong',
        'reason' => 'found_partner',
    ]));

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['fields'])->toHaveKey('password');
    expect($user->profile->fresh()->is_active)->toBeTrue();  // unchanged
    expect($user->profile->fresh()->trashed())->toBeFalse();
});

it('delete validates reason against canonical enum', function () {
    $user = buildSettingsUser(password: 'correct-pass');

    expect(fn () => buildSettingsController()->delete(settingsRequest($user, 'POST', body: [
        'password' => 'correct-pass',
        'reason' => 'fake_reason',
    ])))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('delete soft-deletes profile + revokes all tokens + records reason', function () {
    $user = buildSettingsUser(password: 'correct-pass');
    $auth = new FakeSettingsAuthService();
    $controller = buildSettingsController(auth: $auth);

    $response = $controller->delete(settingsRequest($user, 'POST', body: [
        'password' => 'correct-pass',
        'reason' => 'found_partner',
    ]));

    expect($response->getStatusCode())->toBe(200);
    $data = $response->getData(true)['data'];
    expect($data['deleted'])->toBeTrue();
    expect($data['logged_out'])->toBeTrue();

    // Profile state — refetch with trashed because SoftDeletes default scope hides it.
    $profile = Profile::withTrashed()->where('user_id', $user->id)->first();
    expect($profile->is_active)->toBeFalse();
    expect($profile->is_hidden)->toBeTrue();
    expect($profile->deletion_reason)->toBe('found_partner');
    expect($profile->trashed())->toBeTrue();  // SoftDeletes set deleted_at

    // AuthService::revokeAllTokens was called once.
    expect($auth->revokeAllCalledCount)->toBe(1);
});

it('delete with reason=other folds feedback into deletion_reason', function () {
    $user = buildSettingsUser(password: 'correct-pass');

    buildSettingsController()->delete(settingsRequest($user, 'POST', body: [
        'password' => 'correct-pass',
        'reason' => 'other',
        'feedback' => 'app was too slow',
    ]));

    $profile = Profile::withTrashed()->where('user_id', $user->id)->first();
    expect($profile->deletion_reason)->toBe('other: app was too slow');
});
