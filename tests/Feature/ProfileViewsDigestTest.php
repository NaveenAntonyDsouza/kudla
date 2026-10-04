<?php

use App\Mail\ProfileViewsDigestMail;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Weekly "N members viewed your profile" email
|--------------------------------------------------------------------------
| Counts distinct, visible viewers from the last 7 days; blocked members
| never appear; members who can see who viewed them (paid or free-membership
| mode) get cards, others the count and an upgrade link. Once a week.
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
        $t->timestamp('last_views_digest_at')->nullable();
        $t->timestamp('email_undeliverable_at')->nullable();
        $t->string('email_undeliverable_address')->nullable();
        $t->timestamps();
    });
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('matri_id')->nullable();
        $t->string('full_name')->nullable();
        $t->string('gender')->nullable();
        $t->date('date_of_birth')->nullable();
        $t->boolean('is_active')->default(true);
        $t->boolean('is_hidden')->default(false);
        $t->string('suspension_status')->nullable();
        $t->boolean('onboarding_completed')->default(true);
        $t->softDeletes();
        $t->timestamps();
    });
    Schema::create('profile_views', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('viewer_profile_id');
        $t->unsignedBigInteger('viewed_profile_id');
        $t->timestamp('viewed_at')->nullable();
    });
    Schema::create('blocked_profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->unsignedBigInteger('blocked_profile_id');
        $t->timestamps();
    });
    foreach (['location_info', 'education_details'] as $table) {
        Schema::create($table, function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('profile_id');
            $t->string('native_district')->nullable();
            $t->string('occupation')->nullable();
            $t->timestamps();
        });
    }
    foreach (['site_settings', 'admin_activity_log', 'email_templates', 'theme_settings', 'user_memberships', 'membership_plans', 'profile_photos', 'photo_privacy_settings'] as $table) {
        Schema::create($table, function (Blueprint $t) use ($table) {
            $t->id();
            match ($table) {
                'site_settings' => [$t->string('key')->unique(), $t->text('value')->nullable()],
                'admin_activity_log' => [$t->unsignedBigInteger('admin_user_id')->nullable(), $t->string('action'), $t->string('model_type')->nullable(), $t->unsignedBigInteger('model_id')->nullable(), $t->json('changes')->nullable(), $t->string('ip_address')->nullable()],
                'email_templates' => [$t->string('slug')->unique(), $t->string('name')->nullable(), $t->string('subject')->nullable(), $t->string('preheader')->nullable(), $t->text('body_html')->nullable(), $t->json('variables')->nullable(), $t->boolean('is_active')->default(true)],
                'theme_settings' => [$t->string('primary_color')->nullable(), $t->string('logo_url')->nullable()],
                'user_memberships' => [$t->unsignedBigInteger('user_id'), $t->unsignedBigInteger('plan_id')->nullable(), $t->boolean('is_active')->default(true), $t->timestamp('starts_at')->nullable(), $t->timestamp('ends_at')->nullable()],
                'membership_plans' => [$t->boolean('can_view_contact')->default(true)],
                'profile_photos' => [$t->unsignedBigInteger('profile_id'), $t->boolean('is_primary')->default(false), $t->boolean('is_visible')->default(true), $t->string('approval_status')->default('approved'), $t->string('photo_url')->nullable()],
                'photo_privacy_settings' => [$t->unsignedBigInteger('profile_id'), $t->string('privacy_level')->nullable()],
            };
            $t->timestamps();
        });
    }
    (new Database\Seeders\EmailTemplateSeeder)->run();
    SiteSetting::setValue('free_membership_enabled', '1'); // kudla today: everyone can see who viewed them
    Mail::fake();
});

afterEach(function () {
    foreach (['users', 'profiles', 'profile_views', 'blocked_profiles', 'location_info', 'education_details', 'site_settings', 'admin_activity_log', 'email_templates', 'theme_settings', 'user_memberships', 'membership_plans', 'profile_photos', 'photo_privacy_settings'] as $table) {
        Schema::dropIfExists($table);
    }
});

function pvdMember(string $matri, array $profile = []): User
{
    $id = DB::table('users')->insertGetId(['name' => "{$matri} Rao", 'email' => strtolower($matri).'@site.test', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('profiles')->insert(array_merge(['user_id' => $id, 'matri_id' => $matri, 'full_name' => "{$matri} Rao", 'created_at' => now(), 'updated_at' => now()], $profile));

    return User::find($id);
}

function pvdView(User $viewer, User $viewed, int $daysAgo = 1): void
{
    DB::table('profile_views')->insert([
        'viewer_profile_id' => $viewer->profile->id, 'viewed_profile_id' => $viewed->profile->id, 'viewed_at' => now()->subDays($daysAgo),
    ]);
}

it('counts distinct recent viewers, skips blocked and hidden ones, once a week', function () {
    $me = pvdMember('KM1');
    $asha = pvdMember('KM2');
    $ravi = pvdMember('KM3');
    $blocked = pvdMember('KM5');
    $hidden = pvdMember('KM6', ['is_hidden' => true]);
    pvdView($asha, $me);
    pvdView($asha, $me, 2);      // same viewer twice → once
    pvdView($ravi, $me);
    pvdView($ravi, $me, 10);     // older than a week, but Ravi also viewed yesterday
    pvdView($blocked, $me);
    pvdView($hidden, $me);
    DB::table('blocked_profiles')->insert(['profile_id' => $me->profile->id, 'blocked_profile_id' => $blocked->profile->id]);

    $this->artisan('engagement:send-profile-views-digest', ['--delay' => 0])->assertSuccessful();

    Mail::assertSent(ProfileViewsDigestMail::class, 1);
    Mail::assertSent(ProfileViewsDigestMail::class, function (ProfileViewsDigestMail $m) {
        $html = html_entity_decode($m->render(), ENT_QUOTES);

        return $m->hasTo('km1@site.test')
            && $m->viewCount === 3   // Asha, Ravi, the blocked one counts as a view but isn't shown
            && str_contains($html, 'KM2') && str_contains($html, 'KM3')
            && ! str_contains($html, 'KM5') && ! str_contains($html, 'KM6');
    });

    $this->artisan('engagement:send-profile-views-digest', ['--delay' => 0])->assertSuccessful();
    Mail::assertSent(ProfileViewsDigestMail::class, 1); // not again this week
});

it('shows the count and an upgrade link to members who cannot see who viewed them', function () {
    SiteSetting::setValue('free_membership_enabled', '0');
    $me = pvdMember('KM1');
    pvdView(pvdMember('KM2'), $me);

    $html = html_entity_decode((new ProfileViewsDigestMail($me, 1, collect([pvdMember('KM9')->profile])))->render(), ENT_QUOTES);

    expect($html)->toContain('1 member viewed your profile this week')
        ->toContain('See membership plans')
        ->not->toContain('KM9');
});

it('respects the unsubscribe setting and the site switch', function () {
    $me = pvdMember('KM1');
    DB::table('users')->where('id', $me->id)->update(['notification_preferences' => json_encode(['email_views' => false])]);
    pvdView(pvdMember('KM2'), $me);

    $this->artisan('engagement:send-profile-views-digest', ['--delay' => 0])->assertSuccessful();
    Mail::assertNothingSent();

    $this->artisan('engagement:send-profile-views-digest', ['--scheduled' => true])->expectsOutputToContain('is off')->assertSuccessful();

    $event = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())->first(fn ($e) => str_contains($e->command, 'profile-views-digest'));
    expect($event?->expression)->toBe('0 19 * * 3');
});
