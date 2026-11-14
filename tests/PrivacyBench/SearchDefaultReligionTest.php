<?php

use App\Http\Controllers\SearchController;
use App\Models\SiteSetting;
use App\Services\MemberCreationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| The religion the public search starts on (owner, 14 Nov 2026). Most of Kudla's members
| are Hindu and all of Global Catholic Match's are Christian, so the starting religion is
| a per-site setting rather than one answer for all seven. A signed-in member always
| starts on their own religion, whatever the site says; an empty setting keeps "Any".
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    SiteSetting::setValue('search_default_religion', '');
});

afterEach(fn () => SiteSetting::setValue('search_default_religion', ''));

it('starts a visitor on the religion the site chose, and on Any when it chose none', function () {
    expect(SearchController::defaultReligion())->toBe('');
    $blank = (string) $this->get('/search/quick-search')->assertOk()->getContent();
    expect($blank)->toContain("selectedReligion: ''");

    SiteSetting::setValue('search_default_religion', 'Hindu');
    expect(SearchController::defaultReligion())->toBe('Hindu');

    // both tabs start there, and the box still offers every religion
    $html = (string) $this->get('/search/quick-search')->assertOk()->getContent();
    expect($html)->toContain("selectedReligion: 'Hindu'")
        ->toContain("advReligion: 'Hindu'")
        ->toContain('<option value="">Any Religion</option>')
        ->toContain('<option value="Christian">Christian</option>');

    // a search already in the address keeps its own choice
    expect((string) $this->get('/search/quick-search?religion=Christian')->assertOk()->getContent())
        ->toContain("selectedReligion: 'Christian'")
        ->toContain("advReligion: 'Christian'");

    // a setting that is not a real religion is ignored rather than put in the box
    SiteSetting::setValue('search_default_religion', 'Pastafarian');
    expect(SearchController::defaultReligion())->toBe('')
        ->and((string) $this->get('/search/quick-search')->assertOk()->getContent())->toContain("selectedReligion: ''");
});

it('still starts a signed-in member on their own religion, whatever the site chose', function () {
    SiteSetting::setValue('search_default_religion', 'Hindu');
    $christian = app(MemberCreationService::class)->create([
        'full_name' => 'Search Christian', 'gender' => 'male', 'date_of_birth' => '1991-03-03',
        'email' => 'search-christian@bench.test', 'phone' => '9812500007',
        'religion' => 'Christian', 'denomination' => 'Roman Catholic',
    ], ['temp_password' => 'search-pass-9'])['profile'];
    $christian->forceFill(['onboarding_completed' => true])->save();

    $html = (string) $this->actingAs($christian->user)->get('/search/quick-search')->assertOk()->getContent();
    expect($html)->toContain("selectedReligion: 'Christian'")->toContain("advReligion: 'Christian'");

    // a member with no religion on record falls back to the site's
    $christian->religiousInfo->update(['religion' => null]);
    expect((string) $this->actingAs($christian->user->fresh())->get('/search/quick-search')->assertOk()->getContent())
        ->toContain("selectedReligion: 'Hindu'");
});
