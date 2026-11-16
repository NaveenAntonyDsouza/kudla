<?php

use App\Models\SiteSetting;
use App\Services\MemberCreationService;
use App\Support\AdminModules;
use App\Support\PhotoPrompt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| "Add your photo" nudges (weekly review, 16 Nov 2026; the owner chose a close button
| that hides the bar for 3 days, and a pop-up once a day). Both switches start off,
| and off means the bar is exactly where it was: dashboard and My Matches, no ×.
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    SiteSetting::setValue(AdminModules::PHOTO_BAR_MORE, '0');
    SiteSetting::setValue(AdminModules::PHOTO_POPUP, '0');
    Cache::flush();
});

afterEach(function () {
    SiteSetting::setValue(AdminModules::PHOTO_BAR_MORE, '0');
    SiteSetting::setValue(AdminModules::PHOTO_POPUP, '0');
    $this->travelBack();
});

function photolessMember(string $tag, string $phone): \App\Models\Profile
{
    $profile = app(MemberCreationService::class)->create([
        'full_name' => 'Photo '.$tag, 'gender' => 'male', 'date_of_birth' => '1990-04-04',
        'email' => 'photo-'.$tag.'@bench.test', 'phone' => $phone,
        'religion' => 'Christian', 'denomination' => 'Roman Catholic',
    ], ['temp_password' => 'photo-pass-9'])['profile'];
    $profile->forceFill(['onboarding_completed' => true])->save();

    return $profile->fresh();
}

function pageFor(\App\Models\Profile $p, string $url): string
{
    return (string) test()->actingAs($p->user)->get($url)->assertOk()->getContent();
}

it('keeps the bar where it was while the switches are off', function () {
    $p = photolessMember('off', '9812600001');

    expect(pageFor($p, '/dashboard'))->toContain('data-add-photo-bar')->not->toContain('data-photo-bar-close')->not->toContain('data-photo-popup')
        ->and(pageFor($p, '/profile'))->not->toContain('data-add-photo-bar')
        ->and(pageFor($p, '/search/results?religion=Christian'))->not->toContain('data-add-photo-bar');

    // the close address does nothing while its switch is off
    test()->actingAs($p->user)->post('/photo-bar/close')->assertNoContent();
    expect(PhotoPrompt::barClosed($p->user))->toBeFalse();
});

it('shows the bar on the own profile and above search results, and a close hides it for 3 days', function () {
    SiteSetting::setValue(AdminModules::PHOTO_BAR_MORE, '1');
    $p = photolessMember('more', '9812600002');

    expect(pageFor($p, '/dashboard'))->toContain('data-add-photo-bar')->toContain('data-photo-bar-close')
        ->and(pageFor($p, '/profile'))->toContain('data-add-photo-bar')
        ->and(pageFor($p, '/search/results?religion=Christian'))->toContain('data-add-photo-bar');

    test()->actingAs($p->user)->post('/photo-bar/close')->assertNoContent();
    expect(pageFor($p, '/dashboard'))->not->toContain('data-add-photo-bar')
        ->and(pageFor($p, '/profile'))->not->toContain('data-add-photo-bar');

    $this->travel(3)->days();
    $this->travel(1)->minutes();
    expect(pageFor($p, '/dashboard'))->toContain('data-add-photo-bar');
});

it('never nudges a member whose photo is in, even one still waiting for approval', function () {
    SiteSetting::setValue(AdminModules::PHOTO_BAR_MORE, '1');
    SiteSetting::setValue(AdminModules::PHOTO_POPUP, '1');
    $p = photolessMember('done', '9812600003');
    DB::table('profile_photos')->insert([
        'profile_id' => $p->id, 'photo_type' => 'profile', 'photo_url' => 'photos/bench-done.jpg',
        'is_primary' => true, 'approval_status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
    ]);

    expect(pageFor($p, '/dashboard'))->not->toContain('data-add-photo-bar')->not->toContain('data-photo-popup')
        ->and(pageFor($p, '/profile'))->not->toContain('data-add-photo-bar');
});

it('shows the pop-up once a day, never on the photos page, and comes back the next day', function () {
    SiteSetting::setValue(AdminModules::PHOTO_POPUP, '1');
    $p = photolessMember('popup', '9812600004');

    // the photos page neither shows it nor uses up the day's showing
    expect(pageFor($p, '/manage-photos'))->not->toContain('data-photo-popup');

    expect(pageFor($p, '/dashboard'))->toContain('data-photo-popup')
        ->and(pageFor($p, '/matches'))->not->toContain('data-photo-popup')
        ->and(pageFor($p, '/dashboard'))->not->toContain('data-photo-popup');

    $this->travel(1)->days();
    expect(pageFor($p, '/matches'))->toContain('data-photo-popup');
});

it('does not show the pop-up, or use it up, while staff look through Login as member', function () {
    SiteSetting::setValue(AdminModules::PHOTO_POPUP, '1');
    $p = photolessMember('loginas', '9812600005');

    request()->attributes->set('login_as.active', true);
    expect(PhotoPrompt::popupDue($p->user))->toBeFalse();

    request()->attributes->set('login_as.active', false);
    expect(PhotoPrompt::popupDue($p->user))->toBeTrue()
        ->and(PhotoPrompt::popupDue($p->user))->toBeFalse();
});
