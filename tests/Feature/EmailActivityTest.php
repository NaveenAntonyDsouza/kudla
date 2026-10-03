<?php

use App\Mail\PasswordChangedMail;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\EmailActivityReport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

/*
|--------------------------------------------------------------------------
| Email Activity report (admin → Reports → Email Activity)
|--------------------------------------------------------------------------
| Every email that leaves the site is logged with its type (template slug
| from our Feedback-ID header, or a guess for plain code/password mails),
| failed send attempts are logged with the reason, and unsubscribes are
| logged per email setting. Sends for real through the array mailer.
*/

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('password')->nullable();
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->json('notification_preferences')->nullable();
        $t->timestamps();
    });
    Schema::create('email_templates', function (Blueprint $t) {
        $t->id();
        $t->string('slug')->unique();
        $t->string('name')->nullable();
        $t->string('subject')->nullable();
        $t->string('preheader')->nullable();
        $t->text('body_html')->nullable();
        $t->json('variables')->nullable();
        $t->boolean('is_active')->default(true);
        $t->timestamps();
    });
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Schema::create('theme_settings', function (Blueprint $t) {
        $t->id();
        $t->string('primary_color')->nullable();
        $t->string('logo_url')->nullable();
        $t->timestamps();
    });
    Schema::create('email_logs', function (Blueprint $t) {
        $t->id();
        $t->string('event', 16);
        $t->string('email_type', 64)->nullable();
        $t->string('subject', 255)->nullable();
        $t->string('recipient', 191)->nullable();
        $t->unsignedBigInteger('user_id')->nullable();
        $t->text('error')->nullable();
        $t->timestamp('created_at')->useCurrent();
    });

    EmailTemplate::create(['slug' => 'password-changed', 'name' => 'Password Changed (security alert)', 'subject' => 'Your password was changed', 'body_html' => '<p>Hi {{USER_NAME}}</p>']);
});

afterEach(function () {
    foreach (['email_logs', 'theme_settings', 'site_settings', 'email_templates', 'users'] as $table) {
        Schema::dropIfExists($table);
    }
});

function eaMember(): User
{
    return User::create(['name' => 'Asha Shetty', 'email' => 'asha@example.test', 'password' => 'x'])->fresh();
}

it('logs every sent email with its type', function () {
    $user = eaMember();

    Mail::to($user->email)->send(new PasswordChangedMail($user, 'now'));
    Mail::raw('Your code is 123456', fn ($m) => $m->to('asha@example.test')->subject('Email Verification OTP - Kudla Matrimony'));

    expect(EmailLog::where('event', 'sent')->orderBy('id')->pluck('email_type')->all())->toBe(['password-changed', 'verification-code'])
        ->and(EmailLog::first()->recipient)->toBe('asha@example.test');
});

it('logs a failed send attempt with the reason, and still raises the error', function () {
    Mail::extend('broken', fn () => new class extends AbstractTransport {
        protected function doSend(SentMessage $message): void
        {
            throw new RuntimeException('451 4.7.1 Ratelimit hostinger_out_ratelimit');
        }

        public function __toString(): string
        {
            return 'broken://';
        }
    });
    config(['mail.mailers.broken' => ['transport' => 'broken']]);
    $user = eaMember();

    expect(fn () => Mail::mailer('broken')->to($user->email)->send(new PasswordChangedMail($user, 'now')))->toThrow(RuntimeException::class);

    $log = EmailLog::where('event', 'failed')->first();
    expect($log->email_type)->toBe('password-changed')
        ->and($log->recipient)->toBe('asha@example.test')
        ->and($log->error)->toContain('Ratelimit');
});

it('logs unsubscribes per email setting', function () {
    $user = eaMember();

    $this->post($user->unsubscribeUrl('email_weekly_matches'))->assertOk();

    expect(EmailLog::where('event', 'unsubscribed')->first())
        ->email_type->toBe('email_weekly_matches')
        ->user_id->toBe($user->id);
});

it('summarises the period: totals, by email, unsubscribes and the mailbox limit', function () {
    SiteSetting::setValue('mail_daily_quota', '1000');
    foreach (range(1, 3) as $i) {
        EmailLog::create(['event' => 'sent', 'email_type' => 'password-changed']);
    }
    EmailLog::create(['event' => 'failed', 'email_type' => 'password-changed', 'error' => 'refused']);
    EmailLog::create(['event' => 'sent', 'email_type' => 'verification-code']);
    EmailLog::create(['event' => 'unsubscribed', 'email_type' => 'email_weekly_matches']);
    EmailLog::forceCreate(['event' => 'sent', 'email_type' => 'password-changed', 'created_at' => now()->subDays(10)]);

    $report = app(EmailActivityReport::class)->build(7);

    expect($report['totals'])->toBe(['sent' => 4, 'failed' => 1, 'unsubscribed' => 1])
        ->and($report['byType'][0])->toBe(['type' => 'Password Changed (security alert)', 'sent' => 3, 'failed' => 1])
        ->and($report['byType'][1]['type'])->toBe('Verification / login codes')
        ->and($report['unsubscribes'])->toBe(['Weekly match suggestions' => 1])
        ->and($report['last24h'])->toBe(4)
        ->and($report['quota'])->toBe(1000)
        ->and($report['daily'])->toHaveCount(7)
        ->and($report['failures'][0]['error'])->toBe('refused');
});
