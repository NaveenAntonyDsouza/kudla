<?php

use App\Http\Controllers\DiscoverController;
use App\Support\DiscoverSeo;
use App\Support\NriWording;
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
    // a site like Kudla: 28 of 760 live profiles abroad, members of several religions
    Cache::forever('discover.counts.live', 760);
    Cache::forever('site-religions', ['christian' => true, 'other' => true]);
    Cache::forever('community_caste_list_all', ['Billava', 'Kulal', 'Bunts']);
    Cache::forever('site_setting.community_hidden_religions', '[]');
    Cache::forever('site_setting.seo_region', '');
    Cache::forever('site_setting.site_name', 'Kudla Matrimony');
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

it('keeps a row of two or more communities, and gives a site of several religions no dioceses', function () {
    Cache::forever('discover.counts.diocese-matrimony', ['mangalore' => 14, 'udupi' => 4]);   // Kudla's Catholics

    $rows = app(DiscoverController::class)->hub()->getData()['chipRows'];

    expect($rows->pluck('title')->all())->toBe(['Communities'])
        ->and($rows[0]['links']->pluck('label')->all())->toBe(['Billava', 'Kulal', 'Bunts']);
});

it('gives a Catholic site its dioceses as chips, and leaves out a lone community chip', function () {
    // Crasta: every member Christian, nearly all "Roman Catholic"
    Cache::forever('site-religions', ['christian' => true, 'other' => false]);
    Cache::forever('community_caste_list_all', []);
    Cache::forever('discover.counts.community-matrimony', ['roman-catholic' => 55]);
    Cache::forever('discover.counts.diocese-matrimony', ['mangalore' => 6, 'bangalore' => 3, 'mysore' => 3, 'shimoga' => 2]);

    $rows = app(DiscoverController::class)->hub()->getData()['chipRows'];

    expect($rows->pluck('title')->all())->toBe(['Dioceses'])
        ->and($rows[0]['links']->pluck('label')->all())->toBe(['Mangalore', 'Bangalore', 'Mysore'])   // 3+ profiles
        ->and($rows[0]['all'])->toBe('diocese-matrimony');
});

it('calls the NRI pages by country on a site whose members mostly live abroad', function () {
    // Kudla: 28 of 760 abroad, so "NRI"
    expect(DiscoverController::hubSections()['nri-matrimony']['hub_label'])->toBe('Living abroad (NRI)')
        ->and(DiscoverSeo::forResults('nri-matrimony', 'UAE Profiles')['heading'])->toBe('NRI Matrimony – UAE');

    // Global Catholic Match: 700 of 702 live in the USA, Canada, the UK…
    Cache::forever('discover.counts.nri-matrimony', ['usa' => 191, 'canada' => 105, 'united-kingdom' => 404]);
    Cache::forever('discover.counts.live', 702);
    $seo = DiscoverSeo::forResults('nri-matrimony', 'USA Profiles');

    expect(DiscoverController::hubSections()['nri-matrimony']['hub_label'])->toBe('Country')
        ->and(NriWording::config('nri-matrimony')['label'])->toBe('Matrimony by Country')
        ->and(DiscoverSeo::forCategory('nri-matrimony', NriWording::config('nri-matrimony'))['title'])->toBe('Brides & Grooms by Country')
        ->and($seo['heading'])->toBe('Brides & Grooms in USA')
        ->and($seo['title'])->toBe('Brides & Grooms in USA')
        ->and($seo['description'])->not->toContain('NRI');
});

it('shows the chip rows, then the groups as headed sections of cards', function () {
    $html = view('discover._hub-groups', [
        'chipRows' => collect([
            ['title' => 'Communities', 'all' => 'community-matrimony', 'all_label' => 'All communities', 'links' => collect([
                ['label' => 'Billava', 'url' => 'https://example.test/discover/community-matrimony/billava', 'count' => 165],
                ['label' => 'Roman Catholic', 'url' => 'https://example.test/discover/catholic-matrimony/roman-catholic', 'count' => 27],
            ])],
            ['title' => 'Dioceses', 'all' => 'diocese-matrimony', 'all_label' => 'All dioceses', 'links' => collect([
                ['label' => 'Mangalore', 'url' => 'https://example.test/discover/diocese-matrimony/mangalore', 'count' => 6],
            ])],
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
        ->toContain('>Dioceses</h2>')
        ->toContain('Mangalore <span class="dh-chip-n">6</span>')
        ->toContain('/discover/diocese-matrimony"')
        ->toContain('All dioceses')
        ->toContain('479 profiles')
        ->toContain('Dakshina Kannada · Udupi · Chikkamagaluru')
        ->toContain('1 profile<')
        ->not->toContain('Community &amp; religion');
    expect(strpos($html, 'Communities'))->toBeLessThan(strpos($html, 'Dioceses'))
        ->and(strpos($html, 'Dioceses'))->toBeLessThan(strpos($html, 'Karnataka'));
});
