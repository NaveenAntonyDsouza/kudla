<?php

use App\Console\Commands\AlertSiteErrors;
use App\Models\LoginHistory;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminLoginTrail;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/*
| Audit phase 1 (29 Oct 2026): staff sign-ins and wrong staff passwords go
| to Login History; new log errors reach the owner by email once an hour
| (the same error at most daily); the uptime alarm also notices when a
| site's scheduled jobs stop running.
*/

beforeEach(function () {
    foreach (['site_settings', 'login_history', 'users'] as $t) {
        Schema::dropIfExists($t);
    }
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->timestamp('last_login_at')->nullable();
        $t->timestamps();
    });
    Schema::create('login_history', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('login_method', 20);
        $t->string('ip_address', 45)->nullable();
        $t->string('user_agent', 500)->nullable();
        $t->timestamp('logged_in_at')->useCurrent();
        $t->timestamps();
    });
    Cache::flush();
    config(['mail.default' => 'array']);
    DB::table('users')->insert([
        ['id' => 1, 'name' => 'Owner', 'email' => 'owner@example.test', 'role' => 'admin', 'staff_role_id' => 1],
        ['id' => 2, 'name' => 'Member', 'email' => 'member@example.test', 'role' => 'user', 'staff_role_id' => null],
    ]);
    SiteSetting::setValue('site_name', 'Kudla Matrimony');

    AlertSiteErrors::$logPath = tempnam(sys_get_temp_dir(), 'log');
});

afterEach(function () {
    @unlink(AlertSiteErrors::$logPath);
    AlertSiteErrors::$logPath = null;
    foreach (['site_settings', 'login_history', 'users'] as $t) {
        Schema::dropIfExists($t);
    }
});

function alertMails(): array
{
    return collect(app('mailer')->getSymfonyTransport()->messages())
        ->map(fn ($m) => $m->getOriginalMessage())
        ->map(fn ($m) => ['subject' => $m->getSubject(), 'body' => $m->getTextBody()])->values()->all();
}

function writeLog(array $lines): void
{
    file_put_contents(AlertSiteErrors::$logPath, implode("\n", $lines) . "\n", FILE_APPEND);
}

it('records staff sign-ins and wrong staff passwords, not members', function () {
    $owner = User::find(1);
    $member = User::find(2);

    AdminLoginTrail::login(new Login('web', $owner, false));
    AdminLoginTrail::login(new Login('web', $member, false));
    AdminLoginTrail::failed(new Failed('web', $owner, ['email' => 'owner@example.test']));
    AdminLoginTrail::failed(new Failed('web', null, ['email' => 'nobody@example.test']));

    expect(LoginHistory::orderBy('id')->pluck('login_method', 'id')->values()->all())->toBe(['admin', 'admin_failed'])
        ->and(LoginHistory::pluck('user_id')->unique()->all())->toBe([1])
        ->and(User::find(1)->last_login_at)->not->toBeNull()
        ->and(User::find(2)->last_login_at)->toBeNull();
});

it('emails new errors once, grouped, and stays quiet until something new happens', function () {
    SiteSetting::setValue('uptime_alert_email', 'owner@example.test');
    $t = now()->subMinutes(10)->format('Y-m-d H:i:s');
    writeLog([
        "[{$t}] production.ERROR: Expected response code \"250\" but got code \"421\" {\"exception\":\"[object] (Symfony...)\"}",
        '#0 /home/site/vendor/stack.php(12)',
        "[{$t}] production.ERROR: Expected response code \"250\" but got code \"421\" {\"exception\":\"[object] (Symfony...)\"}",
        "[{$t}] production.INFO: Weekly matches sent",
        "[{$t}] production.ERROR: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'document_number'",
    ]);

    $this->artisan('alerts:site-errors')->assertSuccessful();
    $mails = alertMails();
    expect($mails)->toHaveCount(1)
        ->and($mails[0]['subject'])->toBe('Kudla Matrimony: 2 new errors')
        ->and($mails[0]['body'])->toContain('2 times')
        ->and($mails[0]['body'])->toContain("Unknown column 'document_number'")
        ->and($mails[0]['body'])->not->toContain('Symfony...');

    // the same timeout again within 24 hours: nothing new to say
    $later = now()->addMinute()->format('Y-m-d H:i:s');
    $this->travel(2)->minutes();
    writeLog(["[{$later}] production.ERROR: Expected response code \"250\" but got code \"421\""]);
    $this->artisan('alerts:site-errors')->assertSuccessful();
    expect(alertMails())->toHaveCount(1);
});

it('sends nothing when the log has no new errors', function () {
    SiteSetting::setValue('uptime_alert_email', 'owner@example.test');
    $old = now()->subHours(3)->format('Y-m-d H:i:s');
    writeLog(["[{$old}] production.ERROR: an old error, before the last hour"]);

    $this->artisan('alerts:site-errors')->assertSuccessful();
    expect(alertMails())->toBe([]);
});

it('reports 5 wrong passwords for a staff account in an hour', function () {
    SiteSetting::setValue('error_alert_email', 'alerts@example.test');
    foreach (range(1, 5) as $i) {
        LoginHistory::create(['user_id' => 1, 'login_method' => 'admin_failed', 'logged_in_at' => now()->subMinutes($i)]);
    }

    $this->artisan('alerts:site-errors')->assertSuccessful();
    $mails = alertMails();
    expect($mails[0]['subject'])->toBe('Kudla Matrimony: wrong admin passwords')
        ->and($mails[0]['body'])->toContain('Owner (owner@example.test): 5 times');
});

it('tells the owner when a site\'s scheduled jobs stop, and when they run again', function () {
    SiteSetting::setValue('uptime_watch_urls', "https://amchi.example.test\nhttps://gcm.example.test");
    SiteSetting::setValue('uptime_alert_email', 'owner@example.test');

    $amchiLastRun = now()->subHours(5)->toIso8601String();
    Http::fake(function ($request) use (&$amchiLastRun) {
        if (str_ends_with($request->url(), '/health/jobs')) {
            // GCM has no cron at all: never ran, never alarms
            return Http::response(['last_run' => str_contains($request->url(), 'amchi') ? $amchiLastRun : null]);
        }

        return Http::response('<html><body>ok</body></html>', 200);
    });

    $this->artisan('uptime:watch', ['--retry-after' => 0])->assertSuccessful();
    Cache::lock('uptime-watch')->forceRelease();
    $this->artisan('uptime:watch', ['--retry-after' => 0])->assertSuccessful();
    expect(collect(alertMails())->pluck('subject')->all())->toBe(['amchi.example.test: scheduled jobs stopped']);

    $amchiLastRun = now()->toIso8601String();
    Cache::lock('uptime-watch')->forceRelease();
    $this->artisan('uptime:watch', ['--retry-after' => 0])->assertSuccessful();
    expect(collect(alertMails())->pluck('subject')->all())->toBe(['amchi.example.test: scheduled jobs stopped', 'amchi.example.test: scheduled jobs running again']);
});

it('says when the scheduled jobs last ran', function () {
    $this->getJson('/health/jobs')->assertOk()->assertExactJson(['last_run' => null]);

    Cache::forever('scheduler:last-run', '2026-10-29T10:00:00+05:30');
    $this->getJson('/health/jobs')->assertOk()->assertExactJson(['last_run' => '2026-10-29T10:00:00+05:30']);
});
