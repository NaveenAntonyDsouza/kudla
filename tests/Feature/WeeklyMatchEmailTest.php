<?php

use App\Mail\WeeklyMatchSuggestionsMail;
use App\Models\PhotoPrivacySetting;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\MatchingService;
use App\Services\WeeklyMatchSuggestionsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Sleep;

/*
|--------------------------------------------------------------------------
| Weekly match email
|--------------------------------------------------------------------------
| Found in the pre-flight check before kudla's first weekly run (4 Oct 2026):
| 1. Match cards used the photo URL directly, so 142 of 192 emails would
|    have shown photos of 22 members who set them to Hidden / After interest
|    accepted. Cards must follow PhotoVisibility, like the website.
| 2. "View Profile" linked to /profiles/{matri_id}, which is a 404 on the
|    website (that path exists only in the API).
| 3. Sends are paced like the other bulk emails (mailbox sending limits).
*/

beforeEach(function () {
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('password')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->boolean('is_active')->default(true);
        $t->timestamp('last_login_at')->nullable();
        $t->timestamp('last_weekly_match_sent_at')->nullable();
        $t->json('notification_preferences')->nullable();
        $t->timestamps();
    });
    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('matri_id', 20)->unique();
        $t->string('full_name')->nullable();
        $t->boolean('is_active')->default(true);
        $t->boolean('is_approved')->default(true);
        $t->string('suspension_status')->nullable();
        $t->boolean('onboarding_completed')->default(true);
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->timestamp('deleted_at')->nullable();
        $t->timestamps();
    });
    Schema::create('partner_preferences', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->timestamps();
    });
    Schema::create('profile_photos', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('photo_type')->default('profile');
        $t->string('photo_url')->nullable();
        $t->string('thumbnail_url')->nullable();
        $t->string('storage_driver')->default('public');
        $t->boolean('is_primary')->default(false);
        $t->integer('display_order')->default(0);
        $t->boolean('is_visible')->default(true);
        $t->string('approval_status')->default('approved');
        $t->timestamps();
    });
    Schema::create('photo_privacy_settings', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id')->unique();
        $t->string('privacy_level')->nullable();
        $t->string('profile_photo_privacy')->nullable();
        $t->string('album_photos_privacy')->nullable();
        $t->string('family_photos_privacy')->nullable();
        $t->timestamps();
    });
    Schema::create('photo_requests', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('requester_profile_id');
        $t->unsignedBigInteger('target_profile_id');
        $t->string('status')->default('pending');
        $t->timestamps();
    });
    Schema::create('interests', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('sender_profile_id');
        $t->unsignedBigInteger('receiver_profile_id');
        $t->string('status')->default('pending');
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
        $t->boolean('is_active')->default(true);
        $t->timestamps();
    });

    Mail::fake();
});

afterEach(function () {
    foreach (['users', 'profiles', 'partner_preferences', 'profile_photos', 'photo_privacy_settings', 'photo_requests', 'interests', 'site_settings', 'email_templates'] as $table) {
        Schema::dropIfExists($table);
    }
});

function wmMember(?string $photoPrivacy = 'visible_to_all'): Profile
{
    static $n = 0;
    $n++;
    $u = User::create(['name' => "W{$n}", 'email' => "w{$n}@example.test", 'password' => 'x', 'is_active' => true]);
    $p = Profile::create(['user_id' => $u->id, 'matri_id' => 'KM' . (300000 + $n), 'full_name' => "W{$n}"]);
    ProfilePhoto::create(['profile_id' => $p->id, 'photo_type' => 'profile', 'photo_url' => "photos/w{$n}.jpg", 'is_primary' => true]);
    if ($photoPrivacy) {
        PhotoPrivacySetting::create(['profile_id' => $p->id, 'profile_photo_privacy' => $photoPrivacy]);
    }

    return $p->fresh();
}

function wmCards(User $recipient, array $matches): string
{
    foreach ($matches as $m) {
        $m->setRelation('locationInfo', null)->setRelation('educationDetail', null);
    }
    $mail = new WeeklyMatchSuggestionsMail($recipient, collect($matches));
    $render = new ReflectionMethod($mail, 'renderMatchCards');
    $render->setAccessible(true);

    return $render->invoke($mail);
}

it('shows only the photos the recipient may see', function () {
    $recipient = wmMember()->user;
    $open = wmMember('visible_to_all');
    $hidden = wmMember('hidden');
    $afterInterest = wmMember('interest_accepted');

    $html = wmCards($recipient, [$open, $hidden, $afterInterest]);

    expect($html)->toContain('photos/' . basename($open->primaryPhoto->photo_url))
        ->and($html)->not->toContain(basename($hidden->primaryPhoto->photo_url))
        ->and($html)->not->toContain(basename($afterInterest->primaryPhoto->photo_url))
        ->and(substr_count($html, '👤'))->toBe(2); // placeholders instead
});

it('links each card to the website profile page', function () {
    $recipient = wmMember()->user;
    $match = wmMember();

    $html = wmCards($recipient, [$match]);

    expect($html)->toContain('href="' . route('profile.view', $match) . '"')
        ->and($html)->not->toContain('/profiles/');
});

it('pauses between sends, not before the first', function () {
    Sleep::fake();
    SiteSetting::setValue('weekly_matches_send_delay', '3');

    $matches = collect([wmMember(), wmMember()])->each(fn ($m) => $m->match_score = 80);
    foreach ([wmMember(), wmMember(), wmMember()] as $member) {
        \Illuminate\Support\Facades\DB::table('partner_preferences')->insert(['profile_id' => $member->id]);
    }
    $matcher = Mockery::mock(MatchingService::class);
    $matcher->shouldReceive('getRecommendations')->andReturn(new \Illuminate\Database\Eloquent\Collection($matches->all()));

    $result = (new WeeklyMatchSuggestionsService($matcher))->run();

    expect($result['sent'])->toBe(3);
    Mail::assertSent(WeeklyMatchSuggestionsMail::class, 3);
    Sleep::assertSleptTimes(2);
    Sleep::assertSequence([Sleep::for(3)->seconds(), Sleep::for(3)->seconds()]);
});
