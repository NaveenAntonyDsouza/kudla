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
    rrMember('KM4', 2, joined: '-2 hours');                               // joined today
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

    $html = (new RegistrationReminderMail($stuck))->render();
    expect($html)->toContain("Hi Naveen,")
        ->and($html)->toContain("Your profile (KM10) isn't finished yet")
        ->and($html)->toContain('Matri ID (<strong>KM10</strong>)')
        ->and($html)->toContain('7760020186')
        ->and($html)->toContain('/unsubscribe/');

    expect((new RegistrationReminderMail($verify))->render())->toContain('you just need to verify your email address');
    expect((new RegistrationReminderMail($stuck))->envelope()->subject)->toContain('Naveen, your');
});
