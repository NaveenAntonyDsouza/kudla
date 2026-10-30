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

it('puts every Discover category in one of the hub groups', function () {
    foreach (config('discover') as $slug => $cat) {
        expect($cat['hub_group'] ?? null)->toBeIn(array_keys(DiscoverController::HUB_GROUPS), $slug)
            ->and($cat['hub_label'] ?? null)->not->toBeNull();
    }
});

it('shows the groups as headed sections of cards with counts and top entries', function () {
    $html = view('discover._hub-groups', [
        'groups' => collect([
            'community' => ['title' => 'Community & religion', 'sections' => collect([
                ['slug' => 'community-matrimony', 'hub_label' => 'Community', 'count' => 612, 'top' => ['Billava', 'Kulal', 'Bunts']],
            ])],
            'life' => ['title' => 'Life situation', 'sections' => collect([
                ['slug' => 'second-marriage', 'hub_label' => 'Second marriage', 'count' => 1, 'top' => []],
            ])],
        ]),
    ])->render();

    expect($html)->toContain('Community &amp; religion')
        ->toContain('612 profiles')
        ->toContain('Billava · Kulal · Bunts')
        ->toContain('1 profile<')
        ->toContain(route('discover.category', 'second-marriage'));
});
