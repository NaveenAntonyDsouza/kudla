<?php

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Bounces\BounceParser;
use App\Support\Bounces\BounceSource;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| email:process-bounces — read bounce notices, stop emailing dead addresses
|--------------------------------------------------------------------------
| Formats below are the ones in kudla's mailbox (Oct 2026): MailChannels /
| Postfix delivery reports for Gmail, Yahoo, qmail and bad domains.
*/

function dsn(string $rcpt, string $status, string $diagnostic): string
{
    return "From: Mail Delivery System <MAILER-DAEMON@mailchannels.net>\r\nSubject: Undelivered Mail Returned to Sender\r\n"
        ."Content-Type: multipart/report; report-type=delivery-status; boundary=\"B\"\r\n\r\n--B\r\nContent-Type: text/plain\r\n\r\n"
        ."This is the mail system at host relay.mailchannels.net.\r\n\r\n<{$rcpt}>: host x said: {$diagnostic}\r\n\r\n"
        ."--B\r\nContent-Type: message/delivery-status\r\n\r\nReporting-MTA: dns; relay.mailchannels.net\r\n\r\n"
        ."Final-Recipient: rfc822; {$rcpt}\r\nOriginal-Recipient: rfc822;{$rcpt}\r\nAction: failed\r\nStatus: {$status}\r\n"
        ."Diagnostic-Code: smtp; {$diagnostic}\r\n    (in reply to RCPT TO command)\r\n\r\n--B--\r\n";
}

it('tells dead addresses, full inboxes and spam blocks apart', function (string $status, string $diagnostic, string $kind) {
    $result = BounceParser::parse(dsn('a@b.com', $status, $diagnostic));

    expect($result)->toHaveCount(1)
        ->and($result[0])->recipient->toBe('a@b.com')->kind->toBe($kind);
})->with([
    'gmail no such account' => ['5.1.1', '550-5.1.1 The email account that you tried to reach does not exist.', BounceParser::DEAD],
    'gmail inbox full' => ['4.2.2', "452-4.2.2 The recipient's inbox is out of storage space.", BounceParser::FULL],
    'yahoo not found' => ['5.0.0', '552 1 Requested mail action aborted, mailbox not found', BounceParser::DEAD],
    'yahoo disabled' => ['5.0.0', '554 30 Sorry, your message cannot be delivered. This mailbox is disabled (554.30)', BounceParser::DEAD],
    'qmail typo domain' => ['5.0.0', '511 sorry, no mailbox here by that name (#5.1.1 - chkuser)', BounceParser::DEAD],
    'bad domain' => ['5.4.4', 'X-Postfix; Host or domain name not found. Name service error for name=gmail.coms', BounceParser::DEAD],
    'spam block' => ['5.7.1', '550-5.7.1 Our system has detected an unusual rate of unsolicited mail', BounceParser::OTHER],
    'server busy' => ['4.4.1', '421 Service not available, try again later', BounceParser::OTHER],
]);

it('reads the plain-text Postfix notice when there is no delivery report', function () {
    $raw = "Subject: Undelivered Mail Returned to Sender\r\n\r\n<dead@gmail.com>: host gmail-smtp-in.l.google.com said:\r\n    550-5.1.1 The email account that you tried to reach does not exist.\r\n";

    expect(BounceParser::parse($raw))->toBe([[
        'recipient' => 'dead@gmail.com', 'status' => '5.1.1',
        'diagnostic' => 'host gmail-smtp-in.l.google.com said: 550-5.1.1 The email account that you tried to reach does not exist.',
        'kind' => BounceParser::DEAD,
    ]]);
});

describe('the nightly command', function () {
    beforeEach(function () {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->timestamp('email_undeliverable_at')->nullable();
            $t->string('email_undeliverable_address')->nullable();
            $t->string('email_undeliverable_reason', 190)->nullable();
            $t->timestamps();
        });
        Schema::create('admin_activity_log', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('admin_user_id')->nullable();
            $t->string('action');
            $t->string('model_type')->nullable();
            $t->unsignedBigInteger('model_id')->nullable();
            $t->json('changes')->nullable();
            $t->string('ip_address')->nullable();
            $t->timestamps();
        });
        Schema::create('site_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });
        SiteSetting::create(['key' => 'bounce_processing_enabled', 'value' => '1']);

        foreach (['dead@gmail.com', 'full@gmail.com', 'briefly-full@gmail.com', 'blocked@gmail.com', 'fine@gmail.com'] as $email) {
            DB::table('users')->insert(['email' => $email, 'created_at' => now(), 'updated_at' => now()]);
        }

        $notices = [
            ['received' => now()->subDays(2)->toRfc2822String(), 'raw' => dsn('Dead@gmail.com', '5.1.1', '550-5.1.1 The email account that you tried to reach does not exist.')],
            ['received' => now()->subDays(1)->toRfc2822String(), 'raw' => dsn('blocked@gmail.com', '5.7.1', '550 5.7.1 unsolicited mail')],
            ['received' => now()->subDays(1)->toRfc2822String(), 'raw' => dsn('stranger@gmail.com', '5.1.1', '550 5.1.1 does not exist')],
        ];
        foreach ([40, 30, 20, 10, 1] as $daysAgo) { // full for 39 days, 5 bounces
            $notices[] = ['received' => now()->subDays($daysAgo)->toRfc2822String(), 'raw' => dsn('full@gmail.com', '4.2.2', '452-4.2.2 inbox is out of storage space')];
        }
        foreach ([3, 2, 1] as $daysAgo) { // full only lately
            $notices[] = ['received' => now()->subDays($daysAgo)->toRfc2822String(), 'raw' => dsn('briefly-full@gmail.com', '4.2.2', '452-4.2.2 inbox is out of storage space')];
        }

        app()->instance(BounceSource::class, new class($notices) implements BounceSource {
            public function __construct(private array $notices) {}

            public function bounces(\DateTimeInterface $since): iterable
            {
                foreach ($this->notices as $i => $n) {
                    yield ['uid' => $i + 1] + $n;
                }
            }
        });
    });

    afterEach(function () {
        foreach (['site_settings', 'admin_activity_log', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
    });

    function stopped(): array
    {
        return User::whereNotNull('email_undeliverable_at')->orderBy('email')->pluck('email_undeliverable_reason', 'email')->all();
    }

    it('stops dead addresses and inboxes full for weeks — nothing else', function () {
        $this->artisan('email:process-bounces')->expectsOutputToContain('2 stopped')->assertSuccessful();

        expect(array_keys(stopped()))->toBe(['dead@gmail.com', 'full@gmail.com'])
            ->and(stopped()['full@gmail.com'])->toStartWith('Inbox full for 39 days (5 bounces');
    });

    it('changes nothing on a dry run, or when switched off', function () {
        $this->artisan('email:process-bounces', ['--dry-run' => true])->expectsOutputToContain('2 would be stopped')->assertSuccessful();
        expect(stopped())->toBe([]);

        SiteSetting::setValue('bounce_processing_enabled', '0');
        $this->artisan('email:process-bounces')->expectsOutputToContain('is off')->assertSuccessful();
        expect(stopped())->toBe([]);
    });

    it('does not re-stop a member an admin cleared, from bounces older than the clearing', function () {
        $this->artisan('email:process-bounces')->assertSuccessful();
        App\Support\EmailSuppression::clear('dead@gmail.com');

        $this->artisan('email:process-bounces')->assertSuccessful();

        expect(array_keys(stopped()))->toBe(['full@gmail.com']);
    });

    it('runs once a night', function () {
        $event = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())
            ->first(fn ($e) => str_contains($e->command, 'email:process-bounces'));

        expect($event?->expression)->toBe('45 2 * * *');
    });
});
