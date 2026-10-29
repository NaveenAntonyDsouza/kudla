<?php

use App\Filament\Auth\AppAuthentication;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PragmaRX\Google2FA\Google2FA;

/*
| Two-step login (29 Oct 2026). Filament's set-up checks the same code twice
| (Next, then Enable) while a code may be used only once — so set-up always
| failed. Set-up now skips the used-once rule; signing in keeps it.
*/

beforeEach(function () {
    Schema::dropIfExists('users');
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->text('app_authentication_secret')->nullable();
        $t->text('app_authentication_recovery_codes')->nullable();
        $t->timestamps();
    });
    DB::table('users')->insert(['id' => 1, 'name' => 'Owner', 'email' => 'owner@example.test', 'role' => 'admin']);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

afterEach(fn () => Schema::dropIfExists('users'));

it('accepts the set-up code on both checks of the wizard', function () {
    $this->actingAs(User::find(1), 'admin');
    $auth = AppAuthentication::make();
    $secret = app(Google2FA::class)->generateSecretKey(16);
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    expect($auth->verifyCode($code, $secret))->toBeTrue()   // "Next"
        ->and($auth->verifyCode($code, $secret))->toBeTrue(); // "Enable authenticator app"
});

it('still refuses a code used twice once the secret is saved', function () {
    $user = User::find(1);
    $secret = app(Google2FA::class)->generateSecretKey(16);
    $user->saveAppAuthenticationSecret($secret);
    $this->actingAs($user->fresh(), 'admin');
    $auth = AppAuthentication::make();
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    expect($auth->verifyCode($code))->toBeTrue()
        ->and($auth->verifyCode($code))->toBeFalse()
        ->and($auth->verifyCode($code, $secret))->toBeFalse();
});

it('keeps the secret and recovery codes encrypted and out of JSON', function () {
    $user = User::find(1);
    $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
    $user->saveAppAuthenticationRecoveryCodes(['aaa-bbb', 'ccc-ddd']);

    $raw = DB::table('users')->where('id', 1)->first();
    expect($raw->app_authentication_secret)->not->toContain('JBSWY3DPEHPK3PXP')
        ->and($user->fresh()->getAppAuthenticationRecoveryCodes())->toBe(['aaa-bbb', 'ccc-ddd'])
        ->and($user->fresh()->toArray())->not->toHaveKey('app_authentication_secret');
});

it('signs staff in to the admin panel separately from the member site', function () {
    expect(Filament::getPanel('admin')->getAuthGuard())->toBe('admin');

    // staff signed in to the panel only: the member site sees a visitor,
    // permission checks (e.g. a blog draft preview) still know them
    $this->actingAs(User::find(1), 'admin');
    expect(auth('web')->check())->toBeFalse()
        ->and(\App\Support\Permissions::can('manage_content'))->toBeTrue();
});

it('ends an old member-site sign-in of a staff account, keeping the admin panel one', function () {
    Schema::dropIfExists('profiles');
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->softDeletes();
        $t->timestamps();
    });
    DB::table('users')->insert(['id' => 2, 'name' => 'Member', 'email' => 'member@example.test', 'role' => 'user']);
    DB::table('profiles')->insert(['user_id' => 2]);

    // staff: signed in on both sides, the member side from before 29 Oct
    $this->actingAs(User::find(1), 'web')->actingAs(User::find(1), 'admin');
    $this->get('/health/jobs')->assertOk();
    expect(auth('web')->check())->toBeFalse()
        ->and(auth('admin')->id())->toBe(1);

    // a member stays signed in
    auth('admin')->logout();
    $this->actingAs(User::find(2), 'web');
    $this->get('/health/jobs')->assertOk();
    expect(auth('web')->id())->toBe(2);

    Schema::dropIfExists('profiles');
});
