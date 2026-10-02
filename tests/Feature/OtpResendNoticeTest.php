<?php

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\OtpService;
use App\Support\OtpResendNotice;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| "Resend OTP" says a new code went out
|--------------------------------------------------------------------------
| The page used to just reload; members couldn't tell a new code was sent.
| Only a resend (resend=1) gets the note — the first send's page already
| says "enter the OTP you received".
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
    Mail::fake();
});

afterEach(function () {
    Schema::dropIfExists('users');
    Schema::dropIfExists('site_settings');
});

it('registration email: a resend says where the new code went; the first send does not', function () {
    $user = User::create(['name' => 'M', 'email' => 'm@site.test', 'password' => 'x', 'role' => 'user']);

    $this->actingAs($user)->post('/register/verify-email/send-otp')
        ->assertSessionHas('email_otp_sent')
        ->assertSessionMissing('otp_resent');

    $this->actingAs($user)->post('/register/verify-email/send-otp', ['resend' => 1])
        ->assertSessionHas('otp_resent', OtpResendNotice::email('m@site.test'));
});

it('registration mobile: a resend names the last 4 digits only', function () {
    $user = User::create(['name' => 'M', 'email' => 'm@site.test', 'phone' => '9845012345', 'password' => 'x', 'role' => 'user']);
    $this->mock(OtpService::class)->shouldReceive('sendOtp')->twice();

    $this->actingAs($user)->post('/register/verify/send-otp')->assertSessionMissing('otp_resent');
    $this->actingAs($user)->post('/register/verify/send-otp', ['resend' => 1])
        ->assertSessionHas('otp_resent', fn ($msg) => str_contains($msg, 'ending 2345') && ! str_contains($msg, '98450'));
});

it('login by email code: a resend says a new code was sent', function () {
    SiteSetting::setValue('email_otp_login_enabled', '1');
    User::create(['name' => 'M', 'email' => 'm@site.test', 'password' => 'x', 'role' => 'user']);

    $this->post('/login/send-email-otp', ['email' => 'm@site.test', 'resend' => 1])
        ->assertSessionHas('email_otp_sent')
        ->assertSessionHas('otp_resent', OtpResendNotice::email('m@site.test'));
});
