<?php

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\OtpService;
use App\Support\PhoneCodeLogin;
use App\Support\WhatsAppCloud;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/*
| Sign in with a code on WhatsApp (Nov 2026). Before this, the "Mobile OTP"
| tab showed on sites with no SMS key (codes never arrived) and said "No
| account found with this phone number" to anyone who typed a number.
*/

const PCL_TABLES = ['users', 'profiles', 'contact_info', 'site_settings', 'otp_verifications', 'whatsapp_messages', 'login_history'];

beforeEach(function () {
    foreach (PCL_TABLES as $t) {
        Schema::dropIfExists($t);
    }
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
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('matri_id')->nullable();
        $t->boolean('is_active')->default(true);
        $t->string('suspension_status')->nullable();
        $t->boolean('onboarding_completed')->default(true);
        $t->timestamps();
        $t->softDeletes();
    });
    Schema::create('contact_info', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('whatsapp_number')->nullable();
        $t->string('primary_phone')->nullable();
        $t->timestamps();
    });
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
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
    Schema::create('login_history', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('login_method')->nullable();
        $t->string('ip_address')->nullable();
        $t->string('user_agent', 500)->nullable();
        $t->timestamp('logged_in_at')->nullable();
        $t->timestamps();
    });
    (require base_path('database/migrations/2026_10_16_000001_create_whatsapp_messages_table.php'))->up();
    Cache::flush();
    config(['services.fast2sms.api_key' => null]);

    SiteSetting::setValue('mobile_otp_login_enabled', '1');
    SiteSetting::setValue('wa_cloud_enabled', '1');
    SiteSetting::setValue('wa_cloud_phone_number_id', '1112223334');
    WhatsAppCloud::storeSecret('wa_cloud_access_token', 'EAA-test-token');

    DB::table('users')->insert([
        ['id' => 1, 'name' => 'Asha', 'email' => 'asha@site.test', 'phone' => '9845012345', 'role' => 'user', 'staff_role_id' => null, 'is_active' => true],
        ['id' => 2, 'name' => 'Staff', 'email' => 'staff@site.test', 'phone' => '9900011111', 'role' => 'user', 'staff_role_id' => 5, 'is_active' => true],
        ['id' => 3, 'name' => 'Banned', 'email' => 'ban@site.test', 'phone' => '9900022222', 'role' => 'user', 'staff_role_id' => null, 'is_active' => true],
    ]);
    DB::table('profiles')->insert([
        ['id' => 1, 'user_id' => 1, 'matri_id' => 'KM100001', 'suspension_status' => null],
        ['id' => 3, 'user_id' => 3, 'matri_id' => 'KM100003', 'suspension_status' => 'banned'],
    ]);

    Http::fake(['graph.facebook.com/v25.0/1112223334/messages' => fn () => Http::response(['messages' => [['id' => 'wamid.CODE'.uniqid()]]])]);
});

afterEach(function () {
    foreach (PCL_TABLES as $t) {
        Schema::dropIfExists($t);
    }
});

function codeSends(): \Illuminate\Support\Collection
{
    return collect(Http::recorded())->map(fn ($pair) => $pair[0])->filter(fn ($r) => str_contains($r->url(), '/messages'));
}

it('hides phone-code sign-in when nothing can send a code', function () {
    // WhatsApp codes switched off and no SMS key: the old tab showed and no code ever came.
    expect(PhoneCodeLogin::available())->toBeFalse();

    $this->post('/login/send-otp', ['phone' => '9845012345'])->assertSessionHasErrors('phone');
    expect(DB::table('otp_verifications')->count())->toBe(0);
    Http::assertNothingSent();
});

it('sends a member the approved WhatsApp code, never into the inbox', function () {
    SiteSetting::setValue('wa_cloud_login_codes_enabled', '1');
    expect(PhoneCodeLogin::available())->toBeTrue()
        ->and(PhoneCodeLogin::label())->toBe('WhatsApp Code');

    $this->post('/login/send-otp', ['phone' => '9845012345'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('otp_sent', true);

    $send = codeSends()->sole();
    expect($send['to'])->toBe('919845012345')
        ->and($send['template']['name'])->toBe('login_code')
        ->and($send['template']['components'][0]['parameters'][0]['text'])->toMatch('/^\d{6}$/')
        ->and($send['template']['components'][1]['parameters'][0]['text'])->toBe($send['template']['components'][0]['parameters'][0]['text'])
        ->and(DB::table('otp_verifications')->where('destination', '9845012345')->count())->toBe(1)
        ->and(DB::table('whatsapp_messages')->count())->toBe(0)
        ->and(OtpService::sentToday())->toBe(1);
});

it('sends the code to the WhatsApp number on the profile when it differs from the login mobile', function () {
    SiteSetting::setValue('wa_cloud_login_codes_enabled', '1');
    DB::table('contact_info')->insert(['profile_id' => 1, 'whatsapp_number' => '+91 98450 99999', 'primary_phone' => '9845012345']);

    $this->post('/login/send-otp', ['phone' => '9845012345'])->assertSessionHasNoErrors();

    expect(codeSends()->sole()['to'])->toBe('919845099999')
        ->and(DB::table('otp_verifications')->where('destination', '9845012345')->count())->toBe(1)
        ->and(PhoneCodeLogin::sentMessage('9845012345'))->toContain('WhatsApp number on that account');
});

it('answers the same for unknown, staff and banned numbers, and sends them nothing', function () {
    SiteSetting::setValue('wa_cloud_login_codes_enabled', '1');

    foreach (['9811122233', '9900011111', '9900022222'] as $phone) {
        $this->post('/login/send-otp', ['phone' => $phone])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('otp_sent', true)
            ->assertSessionHas('login_phone', $phone);
    }
    expect(codeSends())->toHaveCount(0)
        ->and(DB::table('otp_verifications')->count())->toBe(0)
        ->and(PhoneCodeLogin::sentMessage('9811122233'))->toContain('If +91 9811122233 belongs to an account');
});

it('allows one code a minute and five an hour per number, counted for unknown numbers too', function () {
    SiteSetting::setValue('wa_cloud_login_codes_enabled', '1');

    $this->post('/login/send-otp', ['phone' => '9811122233'])->assertSessionHasNoErrors();
    $this->post('/login/send-otp', ['phone' => '9811122233'])
        ->assertSessionHasErrors(['phone' => PhoneCodeLogin::limitMessage(OtpService::LIMIT_WAIT)]);

    $this->post('/login/send-otp', ['phone' => '9845012345'])->assertSessionHasNoErrors();
    $this->travel(61)->seconds();
    $this->post('/login/send-otp', ['phone' => '9845012345'])->assertSessionHasNoErrors();
    expect(codeSends())->toHaveCount(2);
});

it('stops at the daily limit for the whole site', function () {
    SiteSetting::setValue('wa_cloud_login_codes_enabled', '1');
    SiteSetting::setValue('otp_phone_daily_cap', '1');

    $this->post('/login/send-otp', ['phone' => '9845012345'])->assertSessionHasNoErrors();
    expect(app(OtpService::class)->phoneLimit('9899988877'))->toBe(OtpService::LIMIT_DAY);
    $this->post('/login/send-otp', ['phone' => '9899988877'])->assertSessionHasErrors('phone');
    expect(codeSends())->toHaveCount(1);
});

it('signs the member in with the right code, and staff never', function () {
    SiteSetting::setValue('wa_cloud_login_codes_enabled', '1');
    $row = fn (string $phone) => DB::table('otp_verifications')->insert(['channel' => 'phone', 'destination' => $phone, 'phone' => $phone,
        'otp_code' => Hash::make('482913'), 'expires_at' => now()->addMinutes(10), 'created_at' => now(), 'updated_at' => now()]);

    $row('9900011111'); // a staff number, even with a valid code
    $this->post('/login/verify-otp', ['phone' => '9900011111', 'otp' => '482913'])
        ->assertSessionHasErrors(['otp' => 'Invalid or expired code.']);
    $this->assertGuest();

    $row('9845012345');
    $this->post('/login/verify-otp', ['phone' => '9845012345', 'otp' => '111111'])->assertSessionHasErrors('otp');
    $this->post('/login/verify-otp', ['phone' => '9845012345', 'otp' => '482913'])->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs(User::find(1));
    expect(DB::table('login_history')->where('user_id', 1)->value('login_method'))->toBe('mobile_otp');
});

it('email code: the same answer whether or not the email has an account', function () {
    Mail::fake();
    SiteSetting::setValue('email_otp_login_enabled', '1');

    $this->post('/login/send-email-otp', ['email' => 'nobody@site.test'])
        ->assertSessionHasNoErrors()->assertSessionHas('email_otp_sent', true);
    $this->post('/login/verify-email-otp', ['email' => 'nobody@site.test', 'otp' => '123456'])
        ->assertSessionHasErrors('otp');
    $this->post('/login/send-email-otp', ['email' => 'staff@site.test'])
        ->assertSessionHasNoErrors()->assertSessionHas('email_otp_sent', true);
    $this->assertGuest();
});

it('app API: no code when nothing can send one, members only otherwise', function () {
    $this->postJson('/api/v1/auth/otp/phone/send', ['phone' => '9845012345', 'purpose' => 'login'])->assertStatus(403);

    SiteSetting::setValue('wa_cloud_login_codes_enabled', '1');
    $this->postJson('/api/v1/auth/otp/phone/send', ['phone' => '9900011111', 'purpose' => 'login'])->assertOk();
    expect(codeSends())->toHaveCount(0);

    $this->postJson('/api/v1/auth/otp/phone/send', ['phone' => '9845012345', 'purpose' => 'login'])->assertOk();
    expect(codeSends())->toHaveCount(1);
});
