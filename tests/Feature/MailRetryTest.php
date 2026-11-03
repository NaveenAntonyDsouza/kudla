<?php

use App\Mail\DatabaseMailable;
use App\Models\EmailLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| A send that fails on a passing fault is tried again
|--------------------------------------------------------------------------
| 26 emails were dropped on kudla in the week of 27 Oct 2026 — the mailbox
| answered "421 timeout exceeded" and nothing picked them up. A timeout is
| now retried; a refused mailbox and a quota refusal are not, because trying
| again cannot change either answer.
*/

beforeEach(function () {
    Schema::dropIfExists('site_settings');
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });

    Schema::dropIfExists('email_logs');
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

    Schema::dropIfExists('users');
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('email')->nullable();
        $t->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('email_logs');
    Schema::dropIfExists('site_settings');
    Schema::dropIfExists('users');
});

/** A mailable that only has to carry a slug and a recipient. */
function retryMailable(): DatabaseMailable
{
    $mailable = new class extends DatabaseMailable
    {
        protected string $templateSlug = 'interest-received';

        protected function templateVariables(): array
        {
            return [];
        }

        /** The retry loop, without the whole render-and-send path around it. */
        public function attempt(callable $send)
        {
            return $this->sendWithRetries($send);
        }
    };

    $mailable->to = [['address' => 'member@site.test', 'name' => null]];

    return $mailable;
}

function timeout(): Exception
{
    return new RuntimeException('Expected response code "250" but got code "421", with message "421 4.4.2 smtp.hostinger.com Error: timeout exceeded".');
}

/** Counts tries from inside a closure that an arrow function would copy by value. */
function retryCounter(): ArrayObject
{
    return new ArrayObject(['tries' => 0]);
}

it('tries again after a timeout and sends the email', function () {
    $tries = 0;

    $sent = retryMailable()->attempt(function () use (&$tries) {
        if (++$tries < 3) {
            throw timeout();
        }

        return 'sent';
    });

    expect($sent)->toBe('sent')
        ->and($tries)->toBe(3)                       // first try plus the two retries
        ->and(EmailLog::count())->toBe(0);           // nothing failed in the end
});

it('gives up after the set number of tries and records one failure', function () {
    $count = retryCounter();

    expect(fn () => retryMailable()->attempt(function () use ($count) {
        $count['tries']++;
        throw timeout();
    }))->toThrow(RuntimeException::class);

    expect($count['tries'])->toBe(3);

    $log = EmailLog::first();
    expect(EmailLog::count())->toBe(1)
        ->and($log->event)->toBe('failed')
        ->and($log->email_type)->toBe('interest-received')
        ->and($log->recipient)->toBe('member@site.test')
        ->and($log->error)->toContain('after 3 tries');
});

it('honours mail_retry_attempts', function () {
    DB::table('site_settings')->insert(['key' => 'mail_retry_attempts', 'value' => '0']);
    $count = retryCounter();

    expect(fn () => retryMailable()->attempt(function () use ($count) {
        $count['tries']++;
        throw timeout();
    }))->toThrow(RuntimeException::class);

    expect($count['tries'])->toBe(1)
        ->and(EmailLog::first()->error)->not->toContain('tries');
});

it('does not try again when the mailbox refuses the address', function () {
    $count = retryCounter();

    expect(fn () => retryMailable()->attempt(function () use ($count) {
        $count['tries']++;
        throw new RuntimeException('Expected response code "250" but got code "550", with message "550 5.1.1 No such user here".');
    }))->toThrow(RuntimeException::class);

    expect($count['tries'])->toBe(1);
});

it('tries again when the connection never opened, whatever port the host names', function () {
    $count = retryCounter();

    $sent = retryMailable()->attempt(function () use ($count) {
        if (++$count['tries'] < 2) {
            // the port must not be read as a 5xx refusal
            throw new Symfony\Component\Mailer\Exception\TransportException(
                'Connection could not be established with host "smtp.hostinger.com:587": stream_socket_client(): Connection timed out'
            );
        }

        return 'sent';
    });

    expect($sent)->toBe('sent')
        ->and($count['tries'])->toBe(2)
        ->and(EmailLog::count())->toBe(0);
});

it('does not try again when the mailbox is at its limit', function () {
    $count = retryCounter();

    expect(fn () => retryMailable()->attempt(function () use ($count) {
        $count['tries']++;
        throw new RuntimeException('451 4.7.1 Ratelimit "hostinger_out_ratelimit" exceeded');
    }))->toThrow(RuntimeException::class);

    expect($count['tries'])->toBe(1);
});

it('stops waiting on retries once several emails in a row have failed', function () {
    // three emails fail on every try: the mail server is down, not having a moment
    foreach (range(1, 3) as $i) {
        expect(fn () => retryMailable()->attempt(fn () => throw timeout()))->toThrow(RuntimeException::class);
    }

    // so the next one is tried once, not three times (a bulk run is not stretched ten seconds an email)
    $count = retryCounter();
    expect(fn () => retryMailable()->attempt(function () use ($count) {
        $count['tries']++;
        throw timeout();
    }))->toThrow(RuntimeException::class);
    expect($count['tries'])->toBe(1);

    // one that gets through brings the retries back
    expect(retryMailable()->attempt(fn () => 'sent'))->toBe('sent');
    $count = retryCounter();
    $sent = retryMailable()->attempt(function () use ($count) {
        if (++$count['tries'] < 2) {
            throw timeout();
        }

        return 'sent';
    });
    expect($sent)->toBe('sent')->and($count['tries'])->toBe(2);
});

it('does not count a refused address towards the server being down', function () {
    foreach (range(1, 5) as $i) {
        expect(fn () => retryMailable()->attempt(fn () => throw new RuntimeException('Expected response code "250" but got code "550", with message "550 5.1.1 No such user here".')))
            ->toThrow(RuntimeException::class);
    }

    // a timeout right after five bad addresses is still retried
    $count = retryCounter();
    $sent = retryMailable()->attempt(function () use ($count) {
        if (++$count['tries'] < 2) {
            throw timeout();
        }

        return 'sent';
    });
    expect($sent)->toBe('sent')->and($count['tries'])->toBe(2);
});
