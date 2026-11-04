<?php

use App\Services\OtpService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/*
| Privacy check, 5 Nov 2026:
| - a one-time code had no limit of its own on wrong guesses (only 10 a minute per
|   address); now 5 wrong codes cancel it and a new one must be sent;
| - "forgot password" said "We can't find a user with that email address", telling
|   anyone whether someone has a profile; now it gives one answer either way.
*/

beforeEach(function () {
    Schema::create('otp_verifications', function (Blueprint $t) {
        $t->id();
        $t->string('phone')->nullable();
        $t->string('channel')->nullable();
        $t->string('destination')->nullable();
        $t->string('otp_code');
        $t->timestamp('expires_at')->nullable();
        $t->timestamp('verified_at')->nullable();
        $t->timestamps();
    });
    Cache::flush();
});

afterEach(fn () => Schema::dropIfExists('otp_verifications'));

function otpRow(string $code): void
{
    DB::table('otp_verifications')->insert(['channel' => 'phone', 'destination' => '9876543210', 'phone' => '9876543210',
        'otp_code' => Hash::make($code), 'expires_at' => now()->addMinutes(10), 'created_at' => now(), 'updated_at' => now()]);
}

it('cancels a one-time code after 5 wrong guesses, even when the right one comes next', function () {
    otpRow('482913');
    $otp = app(OtpService::class);

    foreach (range(1, OtpService::MAX_WRONG_GUESSES) as $i) {
        expect($otp->verify('9876543210', 'phone', '00000' . $i))->toBeFalse();
    }
    expect($otp->verify('9876543210', 'phone', '482913'))->toBeFalse();
});

it('still accepts the right code after a few wrong ones', function () {
    otpRow('482913');
    $otp = app(OtpService::class);

    expect($otp->verify('9876543210', 'phone', '111111'))->toBeFalse()
        ->and($otp->verify('9876543210', 'phone', '482913'))->toBeTrue();
});

it('gives the same forgot-password answer whether or not the email is registered', function () {
    $code = file_get_contents(app_path('Http/Controllers/Auth/ForgotPasswordController.php'));
    $method = substr($code, strpos($code, 'public function sendResetLink'), 1400);

    expect($method)->toContain("return back()->with('success', 'If an account uses this email")
        ->not->toContain('__($status)');
    expect(file_get_contents(base_path('routes/web.php')))
        ->toContain("->name('password.email')->middleware('throttle:5,1')");
});
