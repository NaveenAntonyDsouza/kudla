<?php

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| "Your email is not reaching you"
|--------------------------------------------------------------------------
| 46 members had an address that bounced (weekly review, 2 Nov 2026) and
| were waiting for a staff phone call — the oldest 29 days — while the site
| could send them nothing. They can now give a new address themselves. The
| code goes to the NEW address, so nobody can move an account to a mailbox
| they cannot open.
*/

beforeEach(function () {
    Schema::dropIfExists('users');
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('password')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->boolean('is_active')->default(true);
        $t->timestamp('last_login_at')->nullable();
        $t->integer('reengagement_level')->default(0);
        $t->timestamp('email_verified_at')->nullable();
        $t->timestamp('email_undeliverable_at')->nullable();
        $t->string('email_undeliverable_address')->nullable();
        $t->string('email_undeliverable_reason')->nullable();
        $t->rememberToken();
        $t->timestamps();
    });
    Schema::dropIfExists('profiles');
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('matri_id')->nullable();
        $t->string('full_name')->nullable();
        $t->boolean('is_active')->default(true);
        $t->string('suspension_status')->nullable();
        $t->boolean('onboarding_completed')->default(true);
        $t->integer('onboarding_step_completed')->default(5);
        $t->softDeletes();
        $t->timestamps();
    });
    Schema::dropIfExists('site_settings');
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('site_settings');
    Schema::dropIfExists('profiles');
    Schema::dropIfExists('users');
});

/** A member whose address bounced. */
function bouncedMember(): User
{
    $user = fixEmailMember('old@dead.test');
    $user->forceFill([
        'email_undeliverable_at' => now()->subDays(29),
        'email_undeliverable_address' => 'old@dead.test',
        'email_undeliverable_reason' => '550 no such user',
    ])->save();

    return $user->fresh();
}

/** A member with a finished profile, so the member routes let them through. */
function fixEmailMember(string $email): User
{
    $user = User::create(['name' => 'M', 'email' => $email, 'password' => 'x', 'role' => 'user']);
    \Illuminate\Support\Facades\DB::table('profiles')->insert([
        'user_id' => $user->id, 'matri_id' => 'KM' . $user->id, 'full_name' => 'A Member',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    return $user->fresh();
}

/** The last message the array mailer took, as [recipient, body]. */
function lastSentMail(): array
{
    // ArrayTransport keeps an ArrayObject, so go through a collection
    $last = collect(app('mailer')->getSymfonyTransport()->messages())->last();
    expect($last)->not->toBeNull();
    $email = $last->getOriginalMessage();

    return [$email->getTo()[0]->getAddress(), (string) $email->getTextBody()];
}

it('shows the banner only to a member whose email bounced, and not on the fixing page itself', function () {
    $banner = file_get_contents(resource_path('views/components/partials/email-bounced-banner.blade.php'));

    expect($banner)->toContain('auth()->user()->email_undeliverable_at')
        ->toContain('auth()->user()->staff_role_id === null')      // staff are phoned, not nagged
        ->toContain("! request()->routeIs('email.fix')")
        ->toContain('Your email is not reaching you.')
        ->toContain("route('email.fix')");

    // and it is in the member layout, above the page content
    $layout = file_get_contents(resource_path('views/components/layouts/app.blade.php'));
    expect($layout)->toContain("@include('components.partials.email-bounced-banner')")
        ->and(strpos($layout, "@include('components.partials.email-bounced-banner')"))
        ->toBeLessThan(strpos($layout, '{{ $slot }}'));
});

it('sends the code to the new address, never to the one that bounced', function () {
    $this->actingAs(bouncedMember())
        ->post('/email-not-reaching-you/send-code', ['email' => 'new@live.test'])
        ->assertRedirect()
        ->assertSessionHas('fix_email_sent');

    [$to, $body] = lastSentMail();

    expect($to)->toBe('new@live.test')
        ->and($body)->toMatch('/\b\d{6}\b/');
});

it('updates the address and lifts the block once the member types the code', function () {
    $user = bouncedMember();

    $this->actingAs($user)
        ->post('/email-not-reaching-you/send-code', ['email' => 'new@live.test'])
        ->assertRedirect();

    [, $body] = lastSentMail();
    preg_match('/\b(\d{6})\b/', $body, $m);
    $code = $m[1];

    // a wrong code changes nothing
    $this->actingAs($user)->from('/email-not-reaching-you')
        ->post('/email-not-reaching-you', ['code' => $code === '000000' ? '111111' : '000000'])
        ->assertRedirect('/email-not-reaching-you')
        ->assertSessionHasErrors(['code']);
    expect(User::find($user->id)->email)->toBe('old@dead.test');

    // the real code moves the account and lifts the block
    $this->actingAs($user)->post('/email-not-reaching-you', ['code' => $code])->assertRedirect('/dashboard');

    $fresh = User::find($user->id);
    expect($fresh->email)->toBe('new@live.test')
        ->and($fresh->email_verified_at)->not->toBeNull()
        ->and($fresh->email_undeliverable_at)->toBeNull()
        ->and($fresh->email_undeliverable_address)->toBeNull();
});

it('refuses an address another member already uses', function () {
    fixEmailMember('taken@live.test');

    $this->actingAs(bouncedMember())->from('/email-not-reaching-you')
        ->post('/email-not-reaching-you/send-code', ['email' => 'taken@live.test'])
        ->assertRedirect('/email-not-reaching-you')
        ->assertSessionHasErrors(['email']);

    expect(session('fix_email_address'))->toBeNull();
});
