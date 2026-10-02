<?php

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\AuthService;
use App\Support\LoginIdentifier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Password login with email, mobile number or Matri ID
|--------------------------------------------------------------------------
| Members who mistyped their email at registration couldn't log in, so the
| login box also takes the mobile number and the Matri ID. Mobile numbers
| match on the last 10 digits (old data has +91 / 0 / 91 prefixes).
*/

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('phone', 20)->nullable();
        $t->string('password')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->boolean('is_active')->default(true);
        $t->timestamp('last_login_at')->nullable();
        $t->integer('reengagement_level')->default(0);
        $t->rememberToken();
        $t->timestamps();
    });
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->string('matri_id')->nullable();
        $t->boolean('is_active')->default(true);
        $t->string('suspension_status')->nullable();
        $t->boolean('onboarding_completed')->default(true);
        $t->integer('onboarding_step_completed')->default(5);
        $t->softDeletes();
        $t->timestamps();
    });
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Schema::create('login_history', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('login_method')->nullable();
        $t->string('ip_address')->nullable();
        $t->text('user_agent')->nullable();
        $t->timestamp('logged_in_at')->nullable();
        $t->timestamps();
    });
    Cache::flush();
    RateLimiter::clear('login:km100001|127.0.0.1');
});

afterEach(function () {
    foreach (['users', 'profiles', 'site_settings', 'login_history'] as $table) {
        Schema::dropIfExists($table);
    }
});

function loginMember(string $matriId, ?string $email, ?string $phone, string $password = 'secret123', array $profile = []): User
{
    $user = User::create([
        'name' => $matriId, 'email' => $email, 'phone' => $phone,
        'password' => Hash::make($password), 'role' => 'user', 'is_active' => true,
    ]);
    DB::table('profiles')->insert(array_merge([
        'user_id' => $user->id, 'matri_id' => $matriId, 'is_active' => true,
        'onboarding_completed' => true, 'created_at' => now(), 'updated_at' => now(),
    ], $profile));

    return $user;
}

it('logs in with the Matri ID, in any case or spacing', function (string $typed) {
    $user = loginMember('KM100001', 'a@site.test', '9845012345');

    $this->post('/login', ['login' => $typed, 'password' => 'secret123'])->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
})->with(['KM100001', 'km100001', ' KM 100001 ', 'KM-100001']);

it('logs in with the mobile number however it is typed or stored', function (string $stored, string $typed) {
    $user = loginMember('KM100002', 'b@site.test', $stored);

    $this->post('/login', ['login' => $typed, 'password' => 'secret123'])->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
})->with([
    ['9845012345', '9845012345'],
    ['9845012345', '+91 98450 12345'],
    ['9845012345', '098450-12345'],
    ['919845012345', '9845012345'],   // old data stored with country code
    ['+91 98450 12345', '9845012345'],
]);

it('still logs in with the email, under the new and the old field name', function () {
    $user = loginMember('KM100003', 'c@site.test', '9000000003');

    $this->post('/login', ['login' => 'c@site.test', 'password' => 'secret123'])->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);

    auth()->logout();
    $this->post('/login', ['email' => 'c@site.test', 'password' => 'secret123'])->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

it('rejects a wrong password or unknown identifier with one generic message', function (string $typed, string $password) {
    loginMember('KM100004', 'd@site.test', '9000000004');

    $this->post('/login', ['login' => $typed, 'password' => $password])
        ->assertSessionHasErrors(['login' => 'These credentials do not match our records.']);
    $this->assertGuest();
})->with([
    ['KM100004', 'wrong'],
    ['9000000004', 'wrong'],
    ['KM999999', 'secret123'],
    ['12345', 'secret123'],          // too short for a mobile number
    ['not an id', 'secret123'],
]);

it('lets the password pick the account when two share a mobile number', function () {
    $mother = loginMember('MT100010', 'mother@site.test', '9811111111', 'mother-pass');
    loginMember('MT100011', 'son@site.test', '919811111111', 'son-pass');

    $this->post('/login', ['login' => '9811111111', 'password' => 'mother-pass'])->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($mother);
});

it('asks for email or Matri ID when shared-number accounts also share the password', function () {
    loginMember('MT100012', 'one@site.test', '9822222222', 'same-pass');
    loginMember('MT100013', 'two@site.test', '9822222222', 'same-pass');

    $this->post('/login', ['login' => '9822222222', 'password' => 'same-pass'])
        ->assertSessionHasErrors(['login' => 'This mobile number belongs to more than one account. Please log in with your email or Matri ID.']);
    $this->assertGuest();
});

it('blocks a deleted member who logs in by Matri ID, like by email', function () {
    loginMember('KM100005', 'e@site.test', '9000000005', 'secret123', ['deleted_at' => now()]);

    $this->post('/login', ['login' => 'KM100005', 'password' => 'secret123'])
        ->assertSessionHasErrors(['login' => User::blockedStatusMessage('deleted')]);
    $this->assertGuest();
});

it('allows 5 attempts a minute per identifier, then locks even the right password out', function () {
    loginMember('KM100001', 'a@site.test', '9845012345');

    foreach (range(1, 5) as $i) {
        $this->post('/login', ['login' => 'KM100001', 'password' => "wrong-$i"]);
    }
    $this->post('/login', ['login' => 'KM100001', 'password' => 'secret123'])
        ->assertSessionHasErrors('login');
    $this->assertGuest();
    expect(session('errors')->first('login'))->toContain('Too many login attempts');

    $this->travel(61)->seconds();
    $this->post('/login', ['login' => 'KM100001', 'password' => 'secret123'])->assertRedirect('/dashboard');
});

// (The full login page needs the theme tables; the live page is checked after deploy.)
it('labels the login box with the site\'s name for the member ID', function () {
    expect(LoginIdentifier::fieldLabel())->toBe('Email, Mobile Number or Matri ID');

    SiteSetting::setValue('member_id_label', 'Member ID');
    expect(LoginIdentifier::fieldLabel())->toBe('Email, Mobile Number or Member ID');

    SiteSetting::setValue('member_id_label', '');
    expect(LoginIdentifier::memberIdLabel())->toBe('Matri ID');
});

it('gives the app the same three ways in (AuthService)', function () {
    $user = loginMember('KM100006', 'f@site.test', '9000000006');
    $auth = app(AuthService::class);

    expect($auth->authenticatePassword('KM100006', 'secret123')?->id)->toBe($user->id)
        ->and($auth->authenticatePassword('+91 9000000006', 'secret123')?->id)->toBe($user->id)
        ->and($auth->authenticatePassword('f@site.test', 'secret123')?->id)->toBe($user->id)
        ->and($auth->authenticatePassword('KM100006', 'wrong'))->toBeNull();
});

it('app login asks for the login field when nothing is sent', function () {
    $this->postJson('/api/v1/auth/login/password', ['password' => 'x'])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonStructure(['error' => ['fields' => ['login']]]);
});
