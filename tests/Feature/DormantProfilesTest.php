<?php

use App\Http\Middleware\TrackMemberActivity;
use App\Mail\StillLookingMail;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\DormantProfiles;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Dormant profiles: "Are you still looking?" at 90 days, hidden at 180
|--------------------------------------------------------------------------
| Profiles of people who married or left never answer interests (kudla Oct
| 2026: 26 of 259 interests accepted). Asked once, hidden if they stay
| silent, and back the moment they visit. "Last seen" counts any activity,
| not just last_login_at (which before 2 Oct 2026 missed remembered logins).
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
        $t->timestamp('last_login_at')->nullable();
        $t->unsignedTinyInteger('reengagement_level')->default(0);
        $t->timestamp('dormant_checkin_at')->nullable();
        $t->timestamp('email_undeliverable_at')->nullable();
        $t->string('email_undeliverable_address')->nullable();
        $t->timestamps();
    });
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('matri_id')->nullable();
        $t->boolean('is_active')->default(true);
        $t->boolean('is_hidden')->default(false);
        $t->string('hidden_reason', 20)->nullable();
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
    Schema::create('interests', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('sender_profile_id');
        $t->unsignedBigInteger('receiver_profile_id');
        $t->string('status')->default('pending');
        $t->timestamps();
    });
    Schema::create('login_history', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->timestamp('logged_in_at')->nullable();
    });
    foreach (['site_settings', 'admin_activity_log', 'email_templates', 'theme_settings'] as $table) {
        Schema::create($table, function (Blueprint $t) use ($table) {
            $t->id();
            match ($table) {
                'site_settings' => [$t->string('key')->unique(), $t->text('value')->nullable()],
                'admin_activity_log' => [$t->unsignedBigInteger('admin_user_id')->nullable(), $t->string('action'), $t->string('model_type')->nullable(), $t->unsignedBigInteger('model_id')->nullable(), $t->json('changes')->nullable(), $t->string('ip_address')->nullable()],
                'email_templates' => [$t->string('slug')->unique(), $t->string('name')->nullable(), $t->string('subject')->nullable(), $t->string('preheader')->nullable(), $t->text('body_html')->nullable(), $t->json('variables')->nullable(), $t->boolean('is_active')->default(true)],
                'theme_settings' => [$t->string('primary_color')->nullable(), $t->string('logo_url')->nullable()],
            };
            $t->timestamps();
        });
    }
    (new Database\Seeders\EmailTemplateSeeder)->run();
    Mail::fake();
});

afterEach(function () {
    foreach (['users', 'profiles', 'profile_views', 'interests', 'login_history', 'site_settings', 'admin_activity_log', 'email_templates', 'theme_settings'] as $table) {
        Schema::dropIfExists($table);
    }
});

function dmMember(string $matri, int $joinedDaysAgo, ?int $lastLoginDaysAgo, array $profile = []): User
{
    $id = DB::table('users')->insertGetId([
        'name' => "{$matri} Surname", 'email' => strtolower($matri).'@site.test',
        'last_login_at' => $lastLoginDaysAgo === null ? null : now()->subDays($lastLoginDaysAgo),
        'created_at' => now()->subDays($joinedDaysAgo), 'updated_at' => now(),
    ]);
    DB::table('profiles')->insert(array_merge(['user_id' => $id, 'matri_id' => $matri, 'created_at' => now(), 'updated_at' => now()], $profile));

    return User::find($id);
}

function dmProfileId(User $u): int
{
    return (int) DB::table('profiles')->where('user_id', $u->id)->value('id');
}

it('asks members quiet for 90+ days, once — counting any activity, not just logins', function () {
    $quiet = dmMember('KM1', 200, 120);
    dmMember('KM2', 200, 30);                                   // active lately
    $browsing = dmMember('KM3', 200, null);                     // never "logged in", but browses
    DB::table('profile_views')->insert(['viewer_profile_id' => dmProfileId($browsing), 'viewed_profile_id' => 1, 'viewed_at' => now()->subDays(10)]);
    $answering = dmMember('KM4', 200, 150);                     // answered an interest lately
    DB::table('interests')->insert(['sender_profile_id' => 1, 'receiver_profile_id' => dmProfileId($answering), 'status' => 'accepted', 'created_at' => now()->subDays(100), 'updated_at' => now()->subDays(5)]);
    dmMember('KM5', 60, null);                                  // joined 60 days ago

    $this->artisan('members:dormant', ['--delay' => 0])->assertSuccessful();

    Mail::assertSent(StillLookingMail::class, 1);
    Mail::assertSent(StillLookingMail::class, fn (Mailable $m) => $m->hasTo('km1@site.test'));
    expect($quiet->fresh()->dormant_checkin_at)->not->toBeNull();

    $this->artisan('members:dormant', ['--delay' => 0])->assertSuccessful();
    Mail::assertSent(StillLookingMail::class, 1); // not again
});

it('hides members quiet for 180+ days who did not answer within two weeks', function () {
    $silent = dmMember('KM10', 300, 200);
    DB::table('users')->where('id', $silent->id)->update(['dormant_checkin_at' => now()->subDays(20)]);
    $justAsked = dmMember('KM11', 300, 200);
    DB::table('users')->where('id', $justAsked->id)->update(['dormant_checkin_at' => now()->subDays(5)]);
    dmMember('KM12', 300, 200);                                 // never asked → asked now, not hidden
    $ownChoice = dmMember('KM13', 300, 200, ['is_hidden' => true]);

    $this->artisan('members:dormant', ['--delay' => 0])->assertSuccessful();

    $hidden = DB::table('profiles')->where('is_hidden', true)->pluck('hidden_reason', 'matri_id')->all();
    expect($hidden)->toBe(['KM10' => 'dormant', 'KM13' => null]);
    Mail::assertSent(StillLookingMail::class, fn (Mailable $m) => $m->hasTo('km12@site.test'));
});

it('brings a profile hidden for inactivity back on the next visit — never one the member hid', function () {
    $dormant = dmMember('KM20', 300, 200, ['is_hidden' => true, 'hidden_reason' => 'dormant']);
    $own = dmMember('KM21', 300, 200, ['is_hidden' => true]);

    foreach ([$dormant, $own] as $user) {
        Auth::setUser($user);
        $request = Request::create('/dashboard');
        $request->setLaravelSession(app('session.store'));
        app('session.store')->flush();
        (new TrackMemberActivity)->handle($request, fn () => response('ok'));
    }

    expect(DB::table('profiles')->pluck('is_hidden', 'matri_id')->map(fn ($v) => (bool) $v)->all())->toBe(['KM20' => false, 'KM21' => true]);
});

describe('the answers in the email', function () {
    it('only asks on GET; the button applies the answer', function () {
        $user = dmMember('KM30', 300, 200, ['is_hidden' => true, 'hidden_reason' => 'dormant']);
        $url = StillLookingMail::answerUrl($user, 'looking');

        $this->call('HEAD', $url)->assertOk();
        $this->get($url)->assertOk()->assertSee('still looking');
        expect((bool) DB::table('profiles')->where('user_id', $user->id)->value('is_hidden'))->toBeTrue();

        $this->post($url)->assertOk()->assertSee('stays visible');
        expect((bool) DB::table('profiles')->where('user_id', $user->id)->value('is_hidden'))->toBeFalse()
            ->and($user->fresh()->last_login_at->isToday())->toBeTrue();
    });

    it('hides the profile when they found their match, and invites their story', function () {
        $user = dmMember('KM31', 300, 200);

        $this->post(StillLookingMail::answerUrl($user, 'found'))->assertOk()->assertSee('Congratulations')->assertSee('Share our story');

        expect(DB::table('profiles')->where('user_id', $user->id)->first(['is_hidden', 'hidden_reason']))
            ->is_hidden->toEqual(1)->hidden_reason->toBe('found_match');
    });

    it('needs a valid signature and a known answer', function () {
        $user = dmMember('KM32', 300, 200);
        $url = StillLookingMail::answerUrl($user, 'hide');

        $this->post(str_replace('signature=', 'signature=x', $url))->assertForbidden();
        $this->get(str_replace('answer=hide', 'answer=delete', $url))->assertForbidden(); // tampering breaks the signature too
    });
});

it('runs daily only where switched on', function () {
    dmMember('KM40', 200, 120);

    $this->artisan('members:dormant', ['--scheduled' => true, '--delay' => 0])->expectsOutputToContain('is off')->assertSuccessful();
    Mail::assertNothingSent();

    SiteSetting::setValue('dormant_profiles_enabled', '1');
    $this->artisan('members:dormant', ['--scheduled' => true, '--delay' => 0])->assertSuccessful();
    Mail::assertSent(StillLookingMail::class, 1);

    $event = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())->first(fn ($e) => str_contains($e->command, 'members:dormant'));
    expect($event?->expression)->toBe('30 10 * * *');
});
