<?php

use App\Http\Controllers\DiscoverController;
use Illuminate\Support\Facades\Cache;

/*
| Discover hub (Oct 2026): cards grouped under headings with counts; sections
| that repeat another (Hindu = Community's castes, Kannadiga = Mother tongue →
| Kannada) and sections with fewer than 3 profiles get no button.
*/

beforeEach(function () {
    Cache::flush();
    // profile counts per category, as DiscoverController::profileCounts caches them
    $counts = [
        'community-matrimony' => ['billava' => 165, 'kulal' => 72, 'bunts' => 60],
        'hindu-matrimony' => ['billava' => 165, 'kulal' => 72],
        'mother-tongue-matrimony' => ['tulu' => 440, 'kannada' => 67],
        'kannadiga-matrimony' => ['' => 67],
        'karnataka-matrimony' => ['dakshina-kannada' => 305],
        'nri-matrimony' => ['uae' => 23, 'qatar' => 5],
        'christian-matrimony' => ['csi-christian' => 1],
        'second-marriage' => ['widow' => 10],
    ];
    foreach (array_keys(config('discover')) as $slug) {
        Cache::forever("discover.counts.{$slug}", $counts[$slug] ?? []);
    }
});

it('gives a button only to sections that are not repeats and have 3+ profiles, biggest first', function () {
    $sections = DiscoverController::hubSections();

    expect($sections->keys()->all())->toBe(['mother-tongue-matrimony', 'karnataka-matrimony', 'community-matrimony', 'nri-matrimony', 'second-marriage'])
        ->and($sections['community-matrimony'])->toMatchArray(['hub_label' => 'Community', 'group' => 'community', 'count' => 297])
        ->and($sections['nri-matrimony'])->toMatchArray(['hub_label' => 'Living abroad (NRI)', 'group' => 'place', 'count' => 28]);
    // Hindu and Kannadiga repeat other sections; Christian has 1 profile
    expect($sections->has('hindu-matrimony'))->toBeFalse()
        ->and($sections->has('kannadiga-matrimony'))->toBeFalse()
        ->and($sections->has('christian-matrimony'))->toBeFalse();
});

it('puts every Discover category in a hub group, Profession before Job title', function () {
    $groups = array_merge(['community', 'religion'], array_keys(DiscoverController::HUB_GROUPS));
    foreach (config('discover') as $slug => $cat) {
        expect($cat['hub_group'] ?? null)->toBeIn($groups, $slug)
            ->and($cat['hub_label'] ?? null)->not->toBeNull();
    }
    // religion cards are not hub sections: the communities list holds them, as on the homepage
    expect(DiscoverController::HUB_GROUPS)->not->toHaveKey('religion')->not->toHaveKey('community')
        ->and(config('discover.profession-matrimony.hub_order'))->toBeLessThan(config('discover.occupation-matrimony.hub_order'));
});

it('shows the communities as chips, then the groups as headed sections of cards', function () {
    $html = view('discover._hub-groups', [
        'communities' => collect([
            ['label' => 'Billava', 'url' => 'https://example.test/discover/community-matrimony/billava', 'count' => 165],
            ['label' => 'Roman Catholic', 'url' => 'https://example.test/discover/catholic-matrimony/roman-catholic', 'count' => 27],
        ]),
        'groups' => collect([
            'place' => ['title' => 'Place', 'sections' => collect([
                ['slug' => 'karnataka-matrimony', 'hub_label' => 'Karnataka', 'count' => 479, 'top' => ['Dakshina Kannada', 'Udupi', 'Chikkamagaluru']],
            ])],
            'life' => ['title' => 'Life situation', 'sections' => collect([
                ['slug' => 'second-marriage', 'hub_label' => 'Second marriage', 'count' => 1, 'top' => []],
            ])],
        ]),
    ])->render();

    expect($html)->toContain('>Communities</h2>')
        ->toContain('Billava <span class="dh-chip-n">165</span>')
        ->toContain('discover/catholic-matrimony/roman-catholic')
        ->toContain('All communities')
        ->toContain('479 profiles')
        ->toContain('Dakshina Kannada · Udupi · Chikkamagaluru')
        ->toContain('1 profile<')
        ->not->toContain('Community &amp; religion');
    expect(strpos($html, 'Communities'))->toBeLessThan(strpos($html, 'Karnataka'));
});
