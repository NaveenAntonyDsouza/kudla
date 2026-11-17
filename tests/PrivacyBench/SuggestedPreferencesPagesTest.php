<?php

use App\Models\SiteSetting;
use App\Services\MemberCreationService;
use App\Support\AdminModules;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| My Matches, Mutual Matches and the dashboard for a member who never saved partner
| preferences (owner, 17 Nov 2026): with the switch on they get matches from their own
| profile and a note asking them to set their own; off, the page is as before.
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    SiteSetting::setValue(AdminModules::SUGGESTED_PREFS, '0');
});

afterEach(fn () => SiteSetting::setValue(AdminModules::SUGGESTED_PREFS, '0'));

function noPrefsMember(string $tag, string $phone, string $gender, string $dob): \App\Models\Profile
{
    $profile = app(MemberCreationService::class)->create([
        'full_name' => 'Pref '.$tag, 'gender' => $gender, 'date_of_birth' => $dob,
        'email' => 'pref-'.$tag.'@bench.test', 'phone' => $phone,
        'religion' => 'Christian', 'denomination' => 'Roman Catholic',
    ], ['temp_password' => 'pref-pass-9'])['profile'];
    $profile->forceFill(['onboarding_completed' => true, 'height' => $gender === 'male' ? '172 cm - 5 ft 08 inch' : '158 cm - 5 ft 02 inch'])->save();
    $profile->partnerPreference()->delete();

    return $profile->fresh();
}

it('shows an empty page while off, and matches from the member\'s own profile while on', function () {
    $man = noPrefsMember('man', '9812800001', 'male', '1994-03-03');
    $woman = noPrefsMember('woman', '9812800002', 'female', '1997-06-06');
    expect($man->partnerPreference)->toBeNull();

    $page = fn (string $url) => (string) test()->actingAs($man->user)->get($url)->assertOk()->getContent();

    expect($page('/matches'))->toContain('Set your Partner Preferences first')->not->toContain('data-suggested-preferences');

    SiteSetting::setValue(AdminModules::SUGGESTED_PREFS, '1');
    $matches = $page('/matches');
    expect($matches)->toContain('data-suggested-preferences')
        ->not->toContain('Set your Partner Preferences first')
        ->toContain($woman->matri_id);

    // both have no saved preferences and fit each other's suggestions: each is the other's mutual match
    expect($page('/matches/mutual'))->toContain($woman->matri_id);
    expect((string) test()->actingAs($woman->user)->get('/matches/mutual')->assertOk()->getContent())->toContain($man->matri_id);

    // the dashboard's Recommended carries the same note
    expect($page('/dashboard'))->toContain('data-suggested-preferences');

    // nothing was saved for them
    expect($man->fresh()->partnerPreference)->toBeNull();
});

it('never shows the note to a member who saved preferences', function () {
    SiteSetting::setValue(AdminModules::SUGGESTED_PREFS, '1');
    $man = noPrefsMember('saved', '9812800003', 'male', '1990-01-01');
    $man->partnerPreference()->create(['age_from' => 22, 'age_to' => 35]);

    $html = (string) $this->actingAs($man->user->fresh())->get('/matches')->assertOk()->getContent();
    expect($html)->not->toContain('data-suggested-preferences')->not->toContain('Set your Partner Preferences first');
});
