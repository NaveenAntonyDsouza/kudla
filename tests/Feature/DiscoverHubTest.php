<?php

use App\Http\Controllers\DiscoverController;
use App\Services\DiscoverConfigService;
use App\Support\DiscoverSeo;
use App\Support\NriWording;
use Illuminate\Support\Facades\Cache;

/*
| Discover hub (Oct 2026): every section a row of chips — communities first, as on
| the homepage, then where members are from, where they work, abroad, language,
| work and second marriage. Sections that repeat another (Hindu = Community's
| castes, Kannadiga = Mother tongue → Kannada) and entries with fewer than 3
| profiles get no button.
*/

beforeEach(function () {
    Cache::flush();
    // profile counts per category, as DiscoverController::profileCounts caches them
    $counts = [
        'community-matrimony' => ['billava' => 165, 'kulal' => 72, 'bunts' => 60],
        'hindu-matrimony' => ['billava' => 165, 'kulal' => 72],
        'mother-tongue-matrimony' => ['tulu' => 440, 'kannada' => 67],
        'kannadiga-matrimony' => ['' => 67],
        'karnataka-matrimony' => ['dakshina-kannada' => 305, 'udupi' => 105, 'kasaragod' => 33, 'mysore' => 2],
        'working-in' => ['dakshina-kannada' => 208, 'bangalore' => 89, 'udupi' => 41],
        'nri-matrimony' => ['uae' => 39, 'qatar' => 7],
        'christian-matrimony' => ['csi-christian' => 1],
        'second-marriage' => ['widow' => 10],
    ];
    foreach (array_keys(config('discover')) as $slug) {
        Cache::forever("discover.counts.{$slug}", $counts[$slug] ?? []);
    }
    // a site like Kudla: 90 of 760 live profiles live or work abroad, members of several religions
    Cache::forever('discover.counts.live-abroad', ['live' => 760, 'abroad' => 90]);
    Cache::forever('site-religions', ['christian' => true, 'other' => true]);
    Cache::forever('community_caste_list_all', ['Billava', 'Kulal', 'Bunts']);
    Cache::forever('site_setting.community_hidden_religions', '[]');
    Cache::forever('site_setting.seo_region', '');
    Cache::forever('site_setting.site_name', 'Kudla Matrimony');
});

$rows = fn () => app(DiscoverController::class)->hub()->getData()['chipRows'];

it('gives a button only to sections that are not repeats and have 3+ profiles, biggest first', function () {
    $sections = DiscoverController::hubSections();

    expect($sections->keys()->all())->toBe(['mother-tongue-matrimony', 'karnataka-matrimony', 'working-in', 'community-matrimony', 'nri-matrimony', 'second-marriage'])
        ->and($sections['community-matrimony'])->toMatchArray(['hub_label' => 'Community', 'count' => 297])
        ->and($sections['nri-matrimony'])->toMatchArray(['hub_label' => 'NRI', 'count' => 46]);
    // Hindu and Kannadiga repeat other sections; Christian has 1 profile
    expect($sections->has('hindu-matrimony'))->toBeFalse()
        ->and($sections->has('kannadiga-matrimony'))->toBeFalse()
        ->and($sections->has('christian-matrimony'))->toBeFalse();
});

it('shows every section as a row of chips: communities, then where members are from, work and live', function () use ($rows) {
    $byTitle = $rows()->keyBy('title');

    expect($byTitle->keys()->all())->toBe(['Communities', 'Home district', 'Working in', 'NRI', 'Mother tongue', 'Second marriage'])
        ->and($byTitle['Home district']['links']->pluck('label')->all())->toBe(['Dakshina Kannada', 'Udupi', 'Kasaragod'])   // Mysore has 2
        ->and($byTitle['Working in']['links']->pluck('label')->all())->toBe(['Dakshina Kannada', 'Bangalore', 'Udupi'])
        ->and($byTitle['NRI']['links']->pluck('label')->all())->toBe(['UAE', 'Qatar'])
        ->and($byTitle['Mother tongue']['links']->pluck('label')->all())->toBe(['Tulu', 'Kannada'])
        ->and($byTitle->map(fn ($row) => $row['all_label'])->all())->toMatchArray(['Communities' => 'All communities', 'Home district' => 'All districts', 'NRI' => 'All countries']);
});

it('shows a long row\'s 12 biggest, but every community, as on the homepage', function () use ($rows) {
    $groups = collect(app(DiscoverConfigService::class)->professionGroups())->pluck('slug');
    Cache::forever('discover.counts.profession-matrimony', $groups->mapWithKeys(fn ($slug, $i) => [$slug => 50 - $i])->all());
    $castes = collect(range(1, 14))->map(fn ($i) => "Caste {$i}");
    Cache::forever('community_caste_list_all', $castes->all());
    Cache::forever('discover.counts.community-matrimony', $castes->mapWithKeys(fn ($c, $i) => [\Illuminate\Support\Str::slug($c) => 40 - $i])->all());

    $byTitle = $rows()->keyBy('title');

    expect($groups->count())->toBeGreaterThan(DiscoverController::HUB_ROW_LIMIT)
        ->and($byTitle['Profession']['links'])->toHaveCount(DiscoverController::HUB_ROW_LIMIT)
        ->and($byTitle['Communities']['links'])->toHaveCount(14);
});

it('gives a Catholic site its dioceses as chips, and leaves out a lone community chip', function () use ($rows) {
    // Crasta: every member Christian, nearly all "Roman Catholic"
    Cache::forever('site-religions', ['christian' => true, 'other' => false]);
    Cache::forever('community_caste_list_all', []);
    Cache::forever('discover.counts.community-matrimony', ['roman-catholic' => 55]);
    Cache::forever('discover.counts.diocese-matrimony', ['mangalore' => 6, 'bangalore' => 3, 'mysore' => 3, 'shimoga' => 2]);

    $first = $rows()->first();

    expect($rows()->pluck('title')->all())->not->toContain('Communities')
        ->and($first['title'])->toBe('Dioceses')
        ->and($first['links']->pluck('label')->all())->toBe(['Mangalore', 'Bangalore', 'Mysore'])   // 3+ profiles
        ->and($first['all'])->toBe('diocese-matrimony');
});

it('gives a site of several religions no dioceses row', function () use ($rows) {
    Cache::forever('discover.counts.diocese-matrimony', ['mangalore' => 14, 'udupi' => 4]);   // Kudla's Catholics

    expect($rows()->pluck('title')->all())->not->toContain('Dioceses');
});

it('counts as NRI whoever lives or works in that country', function () {
    $sql = DiscoverController::applyFilters(\App\Models\Profile::query(), ['abroad_country' => 'UAE'])->toSql();
    $uae = collect(app(DiscoverConfigService::class)->nriCountries())->firstWhere('slug', 'uae');

    expect($uae['filter'])->toBe(['abroad_country' => 'UAE'])
        ->and($sql)->toContain('residing_country')->toContain('working_country')->toContain(' or exists');
});

it('adds Kasaragod to the home districts and takes Bangalore Urban and Rural as one work place', function () {
    $service = app(DiscoverConfigService::class);
    $bangalore = collect($service->workPlaces())->firstWhere('slug', 'bangalore');

    expect(collect($service->karnatakaDistricts())->firstWhere('slug', 'kasaragod')['filter'])->toBe(['native_state' => 'Kerala', 'native_district' => 'Kasaragod'])
        ->and($bangalore['filter'])->toBe(['working_state' => 'Karnataka', 'working_district' => ['Bangalore Urban', 'Bangalore Rural']])
        ->and(collect($service->workPlaces())->pluck('slug')->all())->not->toContain('bangalore-urban')
        ->and(DiscoverSeo::forResults('working-in', 'Bangalore Profiles')['title'])->toBe('Brides & Grooms Working in Bangalore')
        ->and(DiscoverSeo::forResults('karnataka-matrimony', 'Kasaragod Profiles')['intro'])->toBe('Find brides and grooms from Kasaragod district.');
});

it('calls the NRI pages by country on a site whose members mostly live abroad', function () {
    // Kudla: 90 of 760 abroad, so "NRI"
    expect(DiscoverController::hubSections()['nri-matrimony']['hub_label'])->toBe('NRI')
        ->and(DiscoverSeo::forResults('nri-matrimony', 'UAE Profiles')['heading'])->toBe('NRI Matrimony – UAE');

    // Global Catholic Match: 700 of 702 live in the USA, Canada, the UK…
    Cache::forever('discover.counts.nri-matrimony', ['usa' => 191, 'canada' => 105, 'united-kingdom' => 404]);
    Cache::forever('discover.counts.live-abroad', ['live' => 702, 'abroad' => 700]);
    $seo = DiscoverSeo::forResults('nri-matrimony', 'USA Profiles');

    expect(DiscoverController::hubSections()['nri-matrimony']['hub_label'])->toBe('Country')
        ->and(NriWording::config('nri-matrimony')['label'])->toBe('Matrimony by Country')
        ->and(DiscoverSeo::forCategory('nri-matrimony', NriWording::config('nri-matrimony'))['title'])->toBe('Brides & Grooms by Country')
        ->and($seo['heading'])->toBe('Brides & Grooms in USA')
        ->and($seo['title'])->toBe('Brides & Grooms in USA')
        ->and($seo['description'])->not->toContain('NRI');
});

it('lists India among the countries only on a site that calls them Country', function () {
    $hasIndia = fn () => collect(app(DiscoverConfigService::class)->nriCountries())->contains('slug', 'india');

    expect($hasIndia())->toBeFalse();   // Kudla: members in India browse by district

    Cache::forever('discover.counts.live-abroad', ['live' => 702, 'abroad' => 700]);   // Global Catholic Match
    expect($hasIndia())->toBeTrue();
});

it('shows each row with its heading, chips and "All …" link, in order', function () {
    $html = view('discover._hub-groups', [
        'chipRows' => collect([
            ['title' => 'Communities', 'all' => 'community-matrimony', 'all_label' => 'All communities', 'links' => collect([
                ['label' => 'Billava', 'url' => 'https://example.test/discover/community-matrimony/billava', 'count' => 165],
                ['label' => 'Roman Catholic', 'url' => 'https://example.test/discover/catholic-matrimony/roman-catholic', 'count' => 27],
            ])],
            ['title' => 'Working in', 'all' => 'working-in', 'all_label' => 'All work places', 'links' => collect([
                ['label' => 'Bangalore', 'url' => 'https://example.test/discover/working-in/bangalore', 'count' => 89],
            ])],
        ]),
    ])->render();

    expect($html)->toContain('>Communities</h2>')
        ->toContain('Billava <span class="dh-chip-n">165</span>')
        ->toContain('discover/catholic-matrimony/roman-catholic')
        ->toContain('All communities →')
        ->toContain('>Working in</h2>')
        ->toContain('Bangalore <span class="dh-chip-n">89</span>')
        ->toContain('/discover/working-in"')
        ->toContain('All work places →');
    expect(strpos($html, 'Communities'))->toBeLessThan(strpos($html, 'Working in'));
});
