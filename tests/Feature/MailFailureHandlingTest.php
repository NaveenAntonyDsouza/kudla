<?php

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| A refused email shows a message, not an error page
|--------------------------------------------------------------------------
| kudla's mailbox hit Hostinger's 100-a-day limit; every code / reset email
| then threw and members got a 500 page (registration's last step, email
| login code, forgot password). Now they get "please try again later".
*/

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('phone')->nullable();
        $t->string('password')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->boolean('is_active')->default(true);
        $t->timestamp('last_login_at')->nullable();
        $t->integer('reengagement_level')->default(0);
        $t->rememberToken();
        $t->timestamps();
    });
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('users');
    Schema::dropIfExists('site_settings');
});

function refusingMailbox(): void
{
    Mail::shouldReceive('raw')->andThrow(new RuntimeException('451 4.7.1 Ratelimit "hostinger_out_ratelimit" exceeded'));
}

it('registration: a refused verification email asks to try again later', function () {
    $user = User::create(['name' => 'M', 'email' => 'm@site.test', 'password' => 'x', 'role' => 'user']);
    refusingMailbox();

    $this->actingAs($user)->from('/register/verify-email')
        ->post('/register/verify-email/send-otp')
        ->assertRedirect('/register/verify-email')
        ->assertSessionHasErrors(['email_otp_send'])
        ->assertSessionMissing('email_otp');
});

it('login by email code: a refused email asks to try again or use the password', function () {
    SiteSetting::setValue('email_otp_login_enabled', '1');
    User::create(['name' => 'M', 'email' => 'm@site.test', 'password' => 'x', 'role' => 'user']);
    refusingMailbox();

    $this->from('/login')->post('/login/send-email-otp', ['email' => 'm@site.test'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['login_email_otp'])
        ->assertSessionMissing('login_email_otp_expires');
    expect(session('errors')->first('login_email_otp'))->toContain('try again');
});

it('forgot password: a refused email asks to try again later', function () {
    Password::shouldReceive('sendResetLink')->andThrow(new RuntimeException('451 ratelimit'));

    $this->from('/forgot-password')->post('/forgot-password', ['email' => 'm@site.test'])
        ->assertRedirect('/forgot-password')
        ->assertSessionHasErrors(['email']);
    expect(session('errors')->first('email'))->toContain("couldn't send");
});

it('app forgot password: a refused email still gets the same neutral answer', function () {
    Password::shouldReceive('sendResetLink')->andThrow(new RuntimeException('451 ratelimit'));

    $this->postJson('/api/v1/auth/password/forgot', ['email' => 'm@site.test'])
        ->assertOk()
        ->assertJsonPath('data.sent', true);
});
