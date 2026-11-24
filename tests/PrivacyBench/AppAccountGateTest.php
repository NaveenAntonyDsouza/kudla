<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| The app follows the website's account gate (24 Nov 2026): a member who deleted their account, or whom
| staff suspended, banned or deactivated, cannot sign in through the app, and a token from before stops
| working at once. Signing out still works. Sign-in already refused these accounts (the same answer as
| for a wrong password); a token issued before kept working until now.
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    Cache::flush();
});

it('refuses sign-in after Delete Account and ends the old token', function () {
    $b = Bench::build();
    $user = $b->m['V']->user;
    $login = fn () => $this->postJson('/api/v1/auth/login/password', ['login' => $user->email, 'password' => 'bench-pass-123']);

    $token = $login()->assertOk()->json('data.token');
    $this->withToken($token)->postJson('/api/v1/settings/delete', ['password' => 'bench-pass-123', 'reason' => 'found_partner'])
        ->assertOk()->assertJsonPath('data.deleted', true);

    $this->app['auth']->forgetGuards();
    $login()->assertStatus(401)->assertJsonPath('error.code', 'UNAUTHENTICATED');   // as for a wrong password
    $this->app['auth']->forgetGuards();
    $this->withToken($token)->getJson('/api/v1/dashboard')->assertStatus(401);           // Delete Account revoked it
});

it('stops a suspended or banned member at once, also with a token from before; signing out still works', function (string $status) {
    $b = Bench::build();
    $profile = $b->m['V'];
    $token = $profile->user->createToken('test')->plainTextToken;
    $this->withToken($token)->getJson('/api/v1/dashboard')->assertOk();

    $profile->forceFill(['suspension_status' => $status])->save();
    $this->app['auth']->forgetGuards();
    $this->withToken($token)->getJson('/api/v1/dashboard')
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'PROFILE_SUSPENDED')
        ->assertJsonPath('error.message', User::blockedStatusMessage($status));
    $this->app['auth']->forgetGuards();
    $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(403);
    $this->postJson('/api/v1/auth/login/password', ['login' => $profile->user->email, 'password' => 'bench-pass-123'])
        ->assertStatus(401)->assertJsonPath('error.code', 'UNAUTHENTICATED');

    $this->app['auth']->forgetGuards();
    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
})->with(['suspended', 'banned']);

it('leaves active members and members in the middle of signing up alone', function () {
    $b = Bench::build();
    $this->withToken($b->m['T']->user->createToken('test')->plainTextToken)->getJson('/api/v1/dashboard')->assertOk();

    $this->app['auth']->forgetGuards();
    $token = $this->postJson('/api/v1/auth/register/step-1', [
        'full_name' => 'Gate Tester', 'gender' => 'female', 'date_of_birth' => '1995-02-02', 'phone' => '9'.random_int(100000000, 999999999),
        'email' => 'gate-'.uniqid().'@bench.test', 'password' => 'gate-pass-9', 'accept_terms' => true,
    ])->assertSuccessful()->json('data.token');
    $this->withToken($token)->postJson('/api/v1/auth/register/step-2', [])
        ->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_FAILED');   // the form, not the gate
});
