<?php

use App\Mail\DatabaseMailable;
use App\Support\AdminModules;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| An address the mail server refuses outright is marked at once (weekly review,
| 16 Nov 2026). One member's address had no proper domain after the @: the server
| answered "504 5.5.2 ... need fully-qualified address" on every weekly run, and a
| refusal at sending time leaves no bounce notice for the nightly bounce reader.
| Only answers about the address itself count — never spam, policy, relay or
| sign-in refusals, which say something about us, not about the member.
*/

beforeEach(function () {
    foreach (['site_settings', 'email_logs', 'users'] as $t) {
        Schema::dropIfExists($t);
    }
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
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
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->timestamp('email_undeliverable_at')->nullable();
        $t->string('email_undeliverable_address')->nullable();
        $t->string('email_undeliverable_reason', 190)->nullable();
        $t->timestamps();
    });
    DB::table('users')->insert(['id' => 1, 'name' => 'Member', 'email' => 'member@gmail']);
    DB::table('users')->insert(['id' => 2, 'name' => 'Other', 'email' => 'other@site.test']);
    DB::table('site_settings')->insert(['key' => 'mail_retry_attempts', 'value' => '0']);
    DB::table('site_settings')->insert(['key' => AdminModules::REFUSED_ADDRESSES, 'value' => '1']);
    Cache::flush();
    DatabaseMailable::forgetFailures();
});

afterEach(function () {
    foreach (['site_settings', 'email_logs', 'users'] as $t) {
        Schema::dropIfExists($t);
    }
});

function refusedMailable(string $to = 'member@gmail'): DatabaseMailable
{
    $mailable = new class extends DatabaseMailable
    {
        protected string $templateSlug = 'weekly-matches';

        protected function templateVariables(): array
        {
            return [];
        }

        public function attempt(callable $send)
        {
            return $this->sendWithRetries($send);
        }
    };
    $mailable->to = [['address' => $to, 'name' => null]];

    return $mailable;
}

/** Send once, failing with the mailbox's own words; the error must still reach the caller. */
function refuseWith(string $answer, string $to = 'member@gmail'): void
{
    $code = substr($answer, 0, 3);
    expect(fn () => refusedMailable($to)->attempt(function () use ($answer, $code) {
        throw new RuntimeException('Expected response code "250/251/252" but got code "'.$code.'", with message "'.$answer.'".');
    }))->toThrow(RuntimeException::class);
}

function markedAddress(int $id = 1): ?string
{
    return DB::table('users')->where('id', $id)->value('email_undeliverable_address');
}

it('marks an address the server says is not a full address (the live case)', function () {
    refuseWith('504 5.5.2 <member@gmail>: Recipient address rejected: need fully-qualified address');

    expect(markedAddress())->toBe('member@gmail')
        ->and(DB::table('users')->where('id', 1)->value('email_undeliverable_reason'))->toContain('need fully-qualified');
});

it('marks a mailbox that does not exist', function () {
    DB::table('users')->where('id', 1)->update(['email' => 'gone@site.test']);
    refuseWith('550 5.1.1 <gone@site.test>: Recipient address rejected: User unknown in virtual mailbox table', 'gone@site.test');

    expect(markedAddress())->toBe('gone@site.test');
});

it('leaves addresses alone when the refusal is about us, not about the address', function () {
    DB::table('users')->where('id', 1)->update(['email' => 'fine@site.test']);
    refuseWith('550 5.7.1 <fine@site.test>: Relay access denied', 'fine@site.test');
    refuseWith('554 5.7.1 <fine@site.test>: Message rejected as spam', 'fine@site.test');
    refuseWith('553 5.1.8 <info@kudla.test>: Sender address rejected: Domain not found', 'fine@site.test');
    refuseWith('535 5.7.8 Error: authentication failed', 'fine@site.test');

    expect(markedAddress())->toBeNull();
});

it('only marks an address this email was sent to, and only when the server named it', function () {
    // the answer names someone else: not ours to mark
    refuseWith('550 5.1.1 <other@site.test>: Recipient address rejected: User unknown');
    // a 5.1.x answer that names no address at all
    refuseWith('550 5.1.1 Recipient address rejected: User unknown');

    expect(markedAddress(1))->toBeNull()->and(markedAddress(2))->toBeNull();
});

it('never marks on a passing fault, and does nothing while the switch is off', function () {
    refuseWith('421 4.4.2 <member@gmail>: timeout exceeded');
    expect(markedAddress())->toBeNull();

    DB::table('site_settings')->where('key', AdminModules::REFUSED_ADDRESSES)->update(['value' => '0']);
    Cache::flush();
    refuseWith('504 5.5.2 <member@gmail>: Recipient address rejected: need fully-qualified address');
    expect(markedAddress())->toBeNull();
});
