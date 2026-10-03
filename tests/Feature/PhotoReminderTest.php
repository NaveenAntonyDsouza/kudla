<?php

use App\Mail\PhotoReminderMail;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| members:remind-photo — "add a photo" reminder
|--------------------------------------------------------------------------
| Only finished registrations with no photo (approved or waiting for
| approval), in good standing, not unsubscribed — at most once a month.
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
        $t->boolean('onboarding_completed')->default(true);
        $t->softDeletes();
        $t->timestamps();
    });
    Schema::create('profile_photos', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('photo_type')->default('profile');
        $t->string('photo_url')->nullable();
        $t->boolean('is_primary')->default(false);
        $t->boolean('is_visible')->default(true);
        $t->string('approval_status')->default('approved');
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
    foreach (['users', 'profiles', 'profile_photos', 'site_settings', 'email_templates', 'admin_activity_log', 'theme_settings'] as $t) {
        Schema::dropIfExists($t);
    }
});

function prMember(string $matriId, ?string $photo = null, bool $done = true, string $joined = '-10 days', array $user = [], array $profile = []): User
{
    $u = User::create(array_merge([
        'name' => "{$matriId} Surname", 'email' => strtolower($matriId) . '@site.test', 'password' => 'x', 'role' => 'user', 'is_active' => true,
    ], $user));
    DB::table('users')->where('id', $u->id)->update(['created_at' => now()->modify($joined)]);
    $pid = DB::table('profiles')->insertGetId(array_merge([
        'user_id' => $u->id, 'matri_id' => $matriId, 'onboarding_completed' => $done, 'created_at' => now(), 'updated_at' => now(),
    ], $profile));
    if ($photo) {
        DB::table('profile_photos')->insert(['profile_id' => $pid, 'photo_url' => 'p.jpg', 'is_primary' => true, 'approval_status' => $photo]);
    }

    return $u->fresh();
}

it('reminds only finished members with no photo, in good standing — once a month at most', function () {
    prMember('KM1');                                         // ✓ no photo
    prMember('KM2', photo: 'rejected');                      // ✓ only a rejected photo
    prMember('KM3', photo: 'approved');                      // has a photo
    prMember('KM4', photo: 'pending');                       // photo waiting for approval
    prMember('KM5', done: false);                            // registration unfinished
    prMember('KM6', joined: '-3 hours');                     // joined today
    prMember('KM7', profile: ['suspension_status' => 'suspended']);
    prMember('KM8', user: ['notification_preferences' => ['email_reengagement' => false]]);

    $this->artisan('members:remind-photo', ['--delay' => 0])->assertSuccessful();

    Mail::assertSent(PhotoReminderMail::class, 2);
    Mail::assertSent(PhotoReminderMail::class, fn (Mailable $m) => $m->hasTo('km1@site.test'));
    Mail::assertSent(PhotoReminderMail::class, fn (Mailable $m) => $m->hasTo('km2@site.test'));

    $this->artisan('members:remind-photo', ['--delay' => 0])->assertSuccessful();
    Mail::assertSent(PhotoReminderMail::class, 2); // not again this month
});

it('honours --limit and --dry-run', function () {
    prMember('KM1');
    prMember('KM2');
    prMember('KM3');

    $this->artisan('members:remind-photo', ['--dry-run' => true])->assertSuccessful();
    Mail::assertNothingSent();

    $this->artisan('members:remind-photo', ['--delay' => 0, '--limit' => 2])->assertSuccessful();
    Mail::assertSent(PhotoReminderMail::class, 2);
});

it('writes the name, ID, photos link, privacy choices and help phone', function () {
    $html = (new PhotoReminderMail(prMember('KM10', user: ['name' => "Naveen D'Souza"])))->render();

    expect($html)->toContain('Hi Naveen,')
        ->and($html)->toContain('(KM10)')
        ->and($html)->toContain('/manage-photos')
        ->and($html)->toContain('Only after interest accepted')
        ->and($html)->toContain('7760020186');
});
