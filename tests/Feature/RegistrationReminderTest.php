<?php

use App\Mail\RegistrationReminderMail;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| members:remind-incomplete — "finish your registration" reminder
|--------------------------------------------------------------------------
| Only members who started but didn't finish, joined over a day ago, in good
| standing, not unsubscribed — and never twice within a week.
*/

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('password')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->boolean('is_active')->default(true);
        $t->json('notification_preferences')->nullable();
        $t->timestamp('last_registration_reminder_at')->nullable();
        $t->timestamp('last_photo_reminder_at')->nullable();
        $t->unsignedTinyInteger('registration_reminders_sent')->default(0);
        $t->timestamp('email_verified_at')->nullable();
        $t->timestamp('phone_verified_at')->nullable();
        $t->timestamp('email_undeliverable_at')->nullable();
        $t->string('email_undeliverable_address')->nullable();
        $t->timestamps();
    });
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->string('matri_id')->nullable();
        $t->boolean('is_active')->default(true);
        $t->string('suspension_status')->nullable();
        $t->boolean('onboarding_completed')->default(false);
        $t->integer('onboarding_step_completed')->default(0);
        $t->softDeletes();
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
        $t->string('slug');
        $t->string('name')->nullable();
        $t->string('subject')->nullable();
        $t->string('preheader')->nullable();
        $t->text('body_html')->nullable();
        $t->json('variables')->nullable();
        $t->boolean('is_active')->default(true);
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
    Schema::create('theme_settings', function (Blueprint $t) { // read by the email wrapper
        $t->id();
        $t->string('primary_color')->nullable();
        $t->string('primary_hover')->nullable();
        $t->string('primary_light')->nullable();
        $t->string('secondary_color')->nullable();
        $t->string('logo_url')->nullable();
        $t->timestamps();
    });
    SiteSetting::create(['key' => 'phone', 'value' => '7760020186']);
    (new Database\Seeders\EmailTemplateSeeder)->run();
    Mail::fake();
});

afterEach(function () {
    foreach (['users', 'profiles', 'site_settings', 'email_templates', 'admin_activity_log', 'theme_settings'] as $t) {
        Schema::dropIfExists($t);
    }
});

function rrMember(string $matriId, int $step, bool $done = false, string $joined = '-5 days', array $user = [], array $profile = []): User
{
    $u = User::create(array_merge([
        'name' => "{$matriId} Surname", 'email' => strtolower($matriId) . '@site.test', 'password' => 'x', 'role' => 'user',
        'is_active' => true,
    ], $user));
    DB::table('users')->where('id', $u->id)->update(['created_at' => now()->modify($joined)]);
    DB::table('profiles')->insert(array_merge([
        'user_id' => $u->id, 'matri_id' => $matriId, 'onboarding_step_completed' => $step,
        'onboarding_completed' => $done, 'created_at' => now(), 'updated_at' => now(),
    ], $profile));

    return $u->fresh();
}

it('reminds only unfinished, settled, reachable members — once a week at most', function () {
    rrMember('KM1', 3);                                                   // ✓ stuck at step 4
    rrMember('KM2', 5);                                                   // ✓ only email verification left
    rrMember('KM3', 5, done: true);                                       // finished
    rrMember('KM4', 2, joined: '-30 minutes');                            // may still be at it
    rrMember('KM5', 1, profile: ['is_active' => false]);                 // deactivated
    rrMember('KM6', 1, profile: ['deleted_at' => now()]);                // deleted
    rrMember('KM7', 2, user: ['notification_preferences' => ['email_reengagement' => false]]); // unsubscribed

    $this->artisan('members:remind-incomplete', ['--delay' => 0])->assertSuccessful();

    Mail::assertSent(RegistrationReminderMail::class, 2);
    Mail::assertSent(RegistrationReminderMail::class, fn (Mailable $m) => $m->hasTo('km1@site.test'));
    Mail::assertSent(RegistrationReminderMail::class, fn (Mailable $m) => $m->hasTo('km2@site.test'));

    // A second run the same week sends nothing new
    $this->artisan('members:remind-incomplete', ['--delay' => 0])->assertSuccessful();
    Mail::assertSent(RegistrationReminderMail::class, 2);
});

it('includes a named new member with --also, and sends nothing on --dry-run', function () {
    rrMember('KM100717', 3, joined: '-1 hour');

    $this->artisan('members:remind-incomplete', ['--dry-run' => true, '--also' => ['km100717']])->assertSuccessful();
    Mail::assertNothingSent();

    $this->artisan('members:remind-incomplete', ['--delay' => 0, '--also' => ['km100717']])->assertSuccessful();
    Mail::assertSent(RegistrationReminderMail::class, fn (Mailable $m) => $m->hasTo('km100717@site.test'));
});

it('writes the right status line, first name, ID and help phone', function () {
    $stuck = rrMember('KM10', 3, user: ['name' => "Naveen D'Souza"]);
    $verify = rrMember('KM11', 5);

    // Values are HTML-escaped in the body ("isn&#039;t"); compare the text a member sees
    $html = html_entity_decode((new RegistrationReminderMail($stuck))->render(), ENT_QUOTES);
    expect($html)->toContain("Hi Naveen,")
        ->and($html)->toContain("Your profile (KM10) isn't finished yet")
        ->and($html)->toContain('Matri ID (<strong>KM10</strong>)')
        ->and($html)->toContain('7760020186')
        ->and($html)->toContain('/unsubscribe/');

    expect((new RegistrationReminderMail($verify))->render())->toContain('you just need to verify your email address');
    expect((new RegistrationReminderMail($stuck))->envelope()->subject)->toContain('Naveen, your');
});

it('sends at most 3 reminders: an hour after signing up, then 4 and 10 days later', function () {
    $member = rrMember('KM20', 2, joined: '-61 minutes');
    $run = fn () => $this->artisan('members:remind-incomplete', ['--delay' => 0])->assertSuccessful();
    $count = fn () => Mail::sent(RegistrationReminderMail::class)->count();

    $run();
    expect($count())->toBe(1); // 1st: an hour after signing up

    $this->travel(3)->days(); $run();
    expect($count())->toBe(1); // 2nd waits 4 days
    $this->travel(1)->days(); $run();
    expect($count())->toBe(2);

    $this->travel(9)->days(); $run();
    expect($count())->toBe(2); // 3rd waits 10 days
    $this->travel(1)->days(); $run();
    expect($count())->toBe(3);

    $this->travel(60)->days(); $run();
    expect($count())->toBe(3)
        ->and($member->fresh()->registration_reminders_sent)->toBe(3);
});

it('counts members reminded by hand before the schedule existed', function () {
    // As the 2 Oct hand runs left them (the migration counts those as 1)
    foreach (['KM21' => 2, 'KM22' => 5] as $matri => $daysAgo) {
        $u = rrMember($matri, 1, joined: '-10 days');
        DB::table('users')->where('id', $u->id)->update(['registration_reminders_sent' => 1, 'last_registration_reminder_at' => now()->subDays($daysAgo)]);
    }

    $this->artisan('members:remind-incomplete', ['--delay' => 0])->assertSuccessful();

    Mail::assertSent(RegistrationReminderMail::class, 1);
    Mail::assertSent(RegistrationReminderMail::class, fn (Mailable $m) => $m->hasTo('km22@site.test'));
});

it('runs daily only where switched on, and at most the daily cap', function () {
    foreach (['KM31', 'KM32', 'KM33'] as $matri) {
        rrMember($matri, 2);
    }

    $this->artisan('members:remind-incomplete', ['--scheduled' => true, '--delay' => 0])->expectsOutputToContain('are off')->assertSuccessful();
    Mail::assertNothingSent();

    SiteSetting::setValue('registration_reminders_enabled', '1');
    SiteSetting::setValue('registration_reminders_daily_cap', '2');
    $this->artisan('members:remind-incomplete', ['--scheduled' => true, '--delay' => 0])->assertSuccessful();
    Mail::assertSent(RegistrationReminderMail::class, 2);

    $this->travel(1)->hours(); // hourly: the cap counts the whole day
    $this->artisan('members:remind-incomplete', ['--scheduled' => true, '--delay' => 0])->assertSuccessful();
    Mail::assertSent(RegistrationReminderMail::class, 2);

    $this->travel(1)->days();
    $this->artisan('members:remind-incomplete', ['--scheduled' => true, '--delay' => 0])->assertSuccessful();
    Mail::assertSent(RegistrationReminderMail::class, 3); // the one left over goes the next day

    $event = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())
        ->first(fn ($e) => str_contains($e->command, 'members:remind-incomplete'));
    expect($event?->expression)->toBe('0 * * * *')
        ->and($event->filtersPass(app()))->toBeBool();
});

it('makes the first reminder a "thank you for joining" email, with a confirm-email button until confirmed', function () {
    $new = rrMember('KM40', 1, joined: '-2 hours');
    $old = rrMember('KM41', 1, joined: '-20 days');

    $first = new RegistrationReminderMail($new, first: true);
    $html = html_entity_decode($first->render(), ENT_QUOTES);
    expect($first->envelope()->subject)->toContain('Thank you for joining')
        ->and($html)->toContain('Your profile has been created')
        ->and($html)->toContain('KM40')
        ->and($html)->toContain('7760020186')
        ->and($html)->toContain('Confirm my email')
        ->and($html)->toContain('/confirm-email/'.$new->id)
        ->and($html)->toContain('typed your address by mistake');

    // Someone who signed up weeks ago gets the plain reminder, not "thanks for joining"
    expect((new RegistrationReminderMail($old, first: true))->envelope()->subject)->not->toContain('Thank you for joining');

    DB::table('users')->where('id', $new->id)->update(['email_verified_at' => now()]);
    expect((new RegistrationReminderMail($new->fresh(), first: true))->render())->not->toContain('Confirm my email');
});

it('sends the "thank you for joining" email as the first scheduled reminder', function () {
    rrMember('KM42', 2, joined: '-70 minutes');

    $this->artisan('members:remind-incomplete', ['--delay' => 0])->assertSuccessful();

    Mail::assertSent(RegistrationReminderMail::class, fn (RegistrationReminderMail $m) => str_contains($m->envelope()->subject, 'Thank you for joining'));
});

describe('the confirm-email link', function () {
    it('only asks on GET (mail scanners open links), and confirms on the button', function () {
        $user = rrMember('KM50', 2);
        $url = $user->confirmEmailUrl();

        $this->call('HEAD', $url)->assertOk();
        $this->get($url)->assertOk()->assertSee('Yes, this is my email')->assertSee($user->email);
        expect($user->fresh()->email_verified_at)->toBeNull();

        $this->post($url)->assertOk()->assertSee('your email is confirmed');
        expect($user->fresh()->email_verified_at)->not->toBeNull()
            ->and((bool) $user->profile->fresh()->onboarding_completed)->toBeFalse(); // steps still to do

        $this->get($url)->assertOk()->assertSee('already confirmed');
    });

    it('finishes the registration of a member who was only waiting on it', function () {
        $user = rrMember('KM51', 5);

        $this->post($user->confirmEmailUrl())->assertOk()->assertSee('Your profile is ready');

        expect((bool) $user->profile->fresh()->onboarding_completed)->toBeTrue();
    });

    it('stops working once the email is changed, and needs a valid signature', function () {
        $user = rrMember('KM52', 2);
        $url = $user->confirmEmailUrl();
        DB::table('users')->where('id', $user->id)->update(['email' => 'new@site.test']);

        $this->post($url)->assertOk()->assertSee('has been changed');
        expect($user->fresh()->email_verified_at)->toBeNull();

        $this->get(str_replace('signature=', 'signature=x', $url))->assertForbidden();
    });
});

it('adds the new email on existing sites and upgrades the old reminder, keeping an admin\'s own wording', function () {
    $migration = require database_path('migrations/2026_10_04_000008_add_registration_started_email.php');

    DB::table('email_templates')->where('slug', 'registration-started')->delete();
    DB::table('email_templates')->where('slug', 'registration-reminder')->update(['body_html' => App\Support\EmailTemplateBodies::REGISTRATION_REMINDER_V1]);
    $migration->up();

    expect(DB::table('email_templates')->where('slug', 'registration-started')->value('preheader'))->not->toBeEmpty()
        ->and(DB::table('email_templates')->where('slug', 'registration-reminder')->value('body_html'))->toBe(App\Support\EmailTemplateBodies::REGISTRATION_REMINDER);

    DB::table('email_templates')->where('slug', 'registration-reminder')->update(['body_html' => '<p>Our own words</p>']);
    $migration->up();
    expect(DB::table('email_templates')->where('slug', 'registration-reminder')->value('body_html'))->toBe('<p>Our own words</p>')
        ->and(DB::table('email_templates')->where('slug', 'registration-started')->count())->toBe(1);
});
