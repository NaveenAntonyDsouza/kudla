<?php

use App\Mail\Reengagement7DayMail;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\ReengagementService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Bulk email safety — re-engagement
|--------------------------------------------------------------------------
| 1. Only members get "we miss you" emails — never the site's own admin
|    account (role 'admin', which has no staff_role_id and slipped through).
| 2. A daily cap: the first time a site's scheduler runs (GCM had ~700
|    inactive members) the backlog goes out over several days instead of in
|    one burst that would use up the mailbox's daily sending allowance and
|    block OTP / password-reset emails. Deferred members stay eligible.
| 3. Most recently active members go first.
|
| Uses an inline site_settings table (the service reads several settings).
*/

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('password')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->boolean('is_active')->default(true);
        $t->timestamp('last_login_at')->nullable();
        $t->timestamp('last_reengagement_sent_at')->nullable();
        $t->integer('reengagement_level')->default(0);
        $t->json('notification_preferences')->nullable();
        $t->timestamp('last_weekly_match_sent_at')->nullable();
        $t->timestamp('last_nudge_sent_at')->nullable();
        $t->integer('nudges_sent_count')->default(0);
        $t->timestamps();
    });

    // Member standing (deactivated / suspended / banned / deleted) lives on the profile
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->boolean('is_active')->default(true);
        $t->string('suspension_status')->nullable();
        $t->boolean('onboarding_completed')->default(true);
        $t->softDeletes();
        $t->timestamps();
    });

    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    SiteSetting::create(['key' => 'reengagement_enabled', 'value' => '1']);
    // Empty: checking a recipient renders the envelope, which looks up the
    // template and falls back to the mailable's default subject.
    Schema::create('email_templates', function (Blueprint $t) {
        $t->id();
        $t->string('slug');
        $t->boolean('is_active')->default(true);
        $t->timestamps();
    });
    Mail::fake();
});

afterEach(function () {
    Schema::dropIfExists('users');
    Schema::dropIfExists('profiles');
    Schema::dropIfExists('site_settings');
    Schema::dropIfExists('email_templates');
});

function inactiveUser(string $email, int $daysInactive, string $role = 'user'): User
{
    return User::create([
        'name' => $email, 'email' => $email, 'password' => 'x', 'role' => $role,
        'is_active' => true, 'last_login_at' => now()->subDays($daysInactive),
    ]);
}

it('never sends re-engagement to the site admin account', function () {
    inactiveUser('admin@site.test', 30, role: 'admin');
    inactiveUser('member@site.test', 30);

    app(ReengagementService::class)->run();

    Mail::assertNotSent(fn (Mailable $m) => $m->hasTo('admin@site.test'));
    Mail::assertSent(fn (Mailable $m) => $m->hasTo('member@site.test'));
});

it('sends at most the daily cap and defers the rest to the next run', function () {
    SiteSetting::setValue('reengagement_daily_cap', '3');
    foreach (range(1, 5) as $i) {
        inactiveUser("m{$i}@site.test", 8 + $i);
    }

    $result = app(ReengagementService::class)->run();

    expect(array_sum($result['sent_by_level']))->toBe(3)
        ->and($result['deferred'])->toBe(2);
    Mail::assertSentCount(3);

    // Next day's run picks up the two that were deferred.
    $next = app(ReengagementService::class)->run();
    expect(array_sum($next['sent_by_level']))->toBe(2);
});

it('sends to the most recently active members first when capped', function () {
    SiteSetting::setValue('reengagement_daily_cap', '1');
    inactiveUser('long-gone@site.test', 90);
    inactiveUser('recent@site.test', 8);

    app(ReengagementService::class)->run();

    Mail::assertSent(Reengagement7DayMail::class, fn (Mailable $m) => $m->hasTo('recent@site.test'));
    Mail::assertSentCount(1);
});

it('sends nothing before the re-engagement start date, then starts on that day', function () {
    inactiveUser('member@site.test', 30);
    SiteSetting::setValue('reengagement_start_date', now()->addDays(7)->toDateString());

    $service = app(ReengagementService::class);
    expect($service->isEnabled())->toBeFalse()
        ->and($service->run()['disabled'] ?? false)->toBeTrue();
    Mail::assertNothingSent();

    $this->travel(7)->days();
    expect($service->isEnabled())->toBeTrue();
    $service->run();
    Mail::assertSent(fn (Mailable $m) => $m->hasTo('member@site.test'));
});

it('treats a blank or unreadable start date as no start date', function () {
    $service = app(ReengagementService::class);

    SiteSetting::setValue('reengagement_start_date', '');
    expect($service->isEnabled())->toBeTrue();

    SiteSetting::setValue('reengagement_start_date', 'not a date');
    expect($service->isEnabled())->toBeTrue();
});

function memberWithProfile(string $email, array $profile): User
{
    $user = inactiveUser($email, 30);
    DB::table('profiles')->insert(array_merge([
        'user_id' => $user->id, 'is_active' => true, 'suspension_status' => null,
        'onboarding_completed' => true, 'created_at' => now(), 'updated_at' => now(),
    ], $profile));

    return $user;
}

it('sends no engagement email to deactivated, suspended, banned or deleted members', function () {
    memberWithProfile('ok@site.test', []);
    inactiveUser('new-registrant@site.test', 30); // no profile yet — still a member
    memberWithProfile('deactivated@site.test', ['is_active' => false]);
    memberWithProfile('suspended@site.test', ['suspension_status' => 'suspended']);
    memberWithProfile('banned@site.test', ['suspension_status' => 'banned']);
    memberWithProfile('deleted@site.test', ['deleted_at' => now()->subMonth()]);

    $result = app(ReengagementService::class)->run();

    expect(array_sum($result['sent_by_level']))->toBe(2);
    Mail::assertSent(fn (Mailable $m) => $m->hasTo('ok@site.test'));
    Mail::assertSent(fn (Mailable $m) => $m->hasTo('new-registrant@site.test'));
    foreach (['deactivated', 'suspended', 'banned', 'deleted'] as $who) {
        Mail::assertNotSent(fn (Mailable $m) => $m->hasTo("$who@site.test"));
    }
});

it('applies the same standing rule to weekly matches and nudges', function () {
    $ok = memberWithProfile('ok@site.test', []);
    $off = memberWithProfile('deactivated@site.test', ['is_active' => false]);
    $ok->update(['last_login_at' => now()->subDays(2)]);
    $off->update(['last_login_at' => now()->subDays(2)]);

    expect($ok->fresh()->canReceiveWeeklyMatches())->toBeTrue()
        ->and($ok->fresh()->canReceiveNudge())->toBeTrue()
        ->and($off->fresh()->canReceiveWeeklyMatches())->toBeFalse()
        ->and($off->fresh()->canReceiveNudge())->toBeFalse();
});
