<?php

use App\Services\MemberCreationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Js;
use Tests\PrivacyBench\Bench;

/*
| Members' Advanced Search: Caste (Hindu, Jain) and Denomination (Christian) appear once
| the religion is picked. 6 Nov 2026: the page never listened to the Religion box (it
| announces its picks as "multiselect-change"), so members never saw Caste or
| Denomination, even with Hindu already chosen. Visitors' search was not affected.
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    $this->member = app(MemberCreationService::class)->create(['full_name' => 'Search Member', 'gender' => 'male', 'date_of_birth' => '1992-05-05',
        'email' => 'search-member@bench.test', 'phone' => '9812500001', 'religion' => 'Hindu', 'caste' => 'Billava'], ['temp_password' => 'search-pass-0001'])['profile'];
    $this->member->forceFill(['onboarding_completed' => true])->save();
});

it('listens to the Religion box, and starts with the religions already chosen', function () {
    $html = (string) $this->actingAs($this->member->user)->get('/search/advanced-search?religion[]=Hindu&caste[]=Billava')->assertOk()->getContent();

    expect($html)
        ->toContain('@multiselect-change.window="if ($event.detail.name === \'religion\')')
        ->toContain('religions: ' . Js::from(['Hindu'])->toHtml())
        ->toContain('x-show="hasReligion(\'Hindu\') || hasReligion(\'Jain\')"')
        ->toContain('Caste')
        ->toContain("\$dispatch('multiselect-clear', { name: 'caste' })");

});

it('starts a fresh Advanced or Quick Search with the member\'s own religion, and keeps a search\'s own choice', function () {
    $this->actingAs($this->member->user);

    // fresh forms: the member is Hindu (owner's choice, 6 Nov 2026, as on Partner Preferences)
    expect((string) $this->get('/search/advanced-search')->assertOk()->getContent())->toContain('religions: ' . Js::from(['Hindu'])->toHtml())
        ->and((string) $this->get('/search/quick-search')->assertOk()->getContent())->toContain('selectedReligion: ' . Js::from('Hindu')->toHtml());

    // Modify Search of a search that had no religion ("Any") stays on Any
    expect((string) $this->get('/search/advanced-search?age_from=25&age_to=30')->assertOk()->getContent())->toContain('religions: ' . Js::from([])->toHtml())
        ->and((string) $this->get('/search/quick-search?age_from=25')->assertOk()->getContent())->toContain('selectedReligion: ' . Js::from('')->toHtml());
});

it('leaves visitors\' Quick Search on any religion', function () {
    expect((string) $this->get('/search/quick-search')->assertOk()->getContent())->toContain('selectedReligion: ' . Js::from('')->toHtml());
});
