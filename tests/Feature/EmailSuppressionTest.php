<?php

use App\Models\EmailLog;
use App\Models\User;
use App\Services\EmailActivityReport;
use App\Support\EmailSuppression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Addresses that bounced permanently are not emailed again
|--------------------------------------------------------------------------
| kudla's info@ mailbox held 201 bounce notices (June–Oct 2026); some dead
| addresses had bounced 15+ times. A mark on the member stops every kind of
| email to that address (MessageSending listener), and lapses by itself once
| the member's email is changed.
*/

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
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('matri_id')->nullable();
        $t->softDeletes();
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
    Schema::create('email_templates', function (Blueprint $t) {
        $t->id();
        $t->string('slug')->unique();
        $t->string('name')->nullable();
        $t->timestamps();
    });

    config(['mail.default' => 'array']);
});

afterEach(function () {
    foreach (['email_templates', 'site_settings', 'admin_activity_log', 'email_logs', 'profiles', 'users'] as $table) {
        Schema::dropIfExists($table);
    }
});

function esMember(string $email, string $matri): User
{
    $id = DB::table('users')->insertGetId(['name' => $matri, 'email' => $email, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('profiles')->insert(['user_id' => $id, 'matri_id' => $matri]);

    return User::find($id);
}

function esSentTo(): array
{
    return collect(app('mailer')->getSymfonyTransport()->messages())
        ->map(fn ($m) => $m->getEnvelope()->getRecipients()[0]->getAddress())->all();
}

it('stops every email to a marked address, and logs that it was skipped', function () {
    esMember('Dead.Address@gmail.com', 'KM1');
    esMember('alive@gmail.com', 'KM2');

    expect(EmailSuppression::mark('dead.address@gmail.com', '550 5.1.1 does not exist'))->toBeTrue();

    Mail::raw('Your OTP is 1234', fn ($m) => $m->to('dead.address@gmail.com')->subject('Your verification code'));
    Mail::raw('Hello', fn ($m) => $m->to('alive@gmail.com')->subject('Hello'));

    expect(esSentTo())->toBe(['alive@gmail.com'])
        ->and(EmailLog::where('event', 'suppressed')->first())
        ->recipient->toBe('Dead.Address@gmail.com')
        ->email_type->toBe('verification-code')
        ->and(DB::table('admin_activity_log')->where('action', 'email_marked_undeliverable')->count())->toBe(1);
});

it('emails again once the member\'s address is corrected, even by a direct DB update', function () {
    $user = esMember('typo@gamil.com', 'KM1');
    EmailSuppression::mark('typo@gamil.com', '511 no mailbox here by that name');

    DB::table('users')->where('id', $user->id)->update(['email' => 'right@gmail.com']);
    Mail::raw('Hello', fn ($m) => $m->to('right@gmail.com')->subject('Hello'));

    expect(esSentTo())->toBe(['right@gmail.com']);
});

it('can be cleared, and ignores addresses no member has', function () {
    esMember('x@gmail.com', 'KM1');
    EmailSuppression::mark('x@gmail.com', 'disabled');

    expect(EmailSuppression::mark('nobody@gmail.com', 'x'))->toBeFalse()
        ->and(EmailSuppression::clear('x@gmail.com'))->toBeTrue();

    Mail::raw('Hello', fn ($m) => $m->to('x@gmail.com')->subject('Hello'));
    expect(esSentTo())->toBe(['x@gmail.com']);
});

it('lists the members we stopped emailing on the Email Activity report', function () {
    esMember('dead@gmail.com', 'KM7');
    esMember('fixed@gmail.com', 'KM8');
    EmailSuppression::mark('dead@gmail.com', '550 5.1.1 does not exist');
    EmailSuppression::mark('fixed@gmail.com', '550 5.1.1 does not exist');
    DB::table('users')->where('email', 'fixed@gmail.com')->update(['email' => 'new@gmail.com']);

    Mail::raw('Hello', fn ($m) => $m->to('dead@gmail.com')->subject('Hello'));
    $report = (new EmailActivityReport)->build(7);

    expect($report['undeliverable'])->toHaveCount(1)
        ->and($report['undeliverable'][0])->matri_id->toBe('KM7')->reason->toBe('550 5.1.1 does not exist')
        ->and($report['suppressed'])->toBe(1);
});

it('marks and lists from the command line', function () {
    esMember('a@gmail.com', 'KM1');

    $this->artisan('email:undeliverable', ['action' => 'mark', 'emails' => ['a@gmail.com', 'zz@gmail.com'], '--reason' => '550 5.1.1'])
        ->expectsOutputToContain('ok   a@gmail.com')
        ->expectsOutputToContain('skip zz@gmail.com')
        ->assertSuccessful();

    $this->artisan('email:undeliverable', ['action' => 'list'])->expectsOutputToContain('KM1')->assertSuccessful();
});
