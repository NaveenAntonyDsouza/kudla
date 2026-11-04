<?php

use App\Http\Controllers\DiscoverController;
use App\Support\DiscoverSeo;
use Illuminate\Support\Facades\Cache;

/*
| Brides / Grooms pages (Nov 2026, after comparing Chavara Matrimony): families
| search "Billava brides" or "Udupi grooms", so a community / place / job page
| with 10+ brides or grooms gets its own page for them, listed in the sitemap.
| Fewer than 10, and the address goes to the plain page (no thin pages).
*/

beforeEach(function () {
    Cache::flush();
    foreach (array_keys(config('discover')) as $slug) {
        Cache::forever("discover.counts.{$slug}", []);
        Cache::forever("discover.side-counts.{$slug}", []);
    }
    Cache::forever('discover.counts.karnataka-matrimony', ['udupi' => 105, 'kasaragod' => 12]);
    Cache::forever('discover.side-counts.karnataka-matrimony', [
        'udupi' => ['female' => 30, 'male' => 75],
        'kasaragod' => ['female' => 3, 'male' => 9],
    ]);
    Cache::forever('site_setting.seo_region', 'Mangaluru, Udupi and Tulu Nadu');
    Cache::forever('site_setting.site_name', 'Kudla Matrimony');
});

it('names the side in the heading, intro and title', function () {
    $community = DiscoverSeo::forSide('community-matrimony', 'Billava Profiles', 'brides');
    expect($community['heading'])->toBe('Billava Brides')
        ->and($community['intro'])->toBe('Find Billava brides from Mangaluru, Udupi and Tulu Nadu.')
        ->and($community['title'])->toBe('Billava Brides – Billava Matrimony')
        ->and($community['description'])->toStartWith('Find Billava brides from Mangaluru, Udupi and Tulu Nadu on Kudla Matrimony.');

    expect(DiscoverSeo::forSide('mother-tongue-matrimony', 'Tulu Profiles', 'grooms')['heading'])->toBe('Tulu-Speaking Grooms')
        ->and(DiscoverSeo::forSide('mother-tongue-matrimony', 'Tulu Profiles', 'grooms')['title'])->toBe('Tulu-Speaking Grooms')
        ->and(DiscoverSeo::forSide('working-in', 'Bangalore Profiles', 'grooms')['heading'])->toBe('Grooms Working in Bangalore')
        ->and(DiscoverSeo::forSide('karnataka-matrimony', 'Udupi Profiles', 'brides')['intro'])->toBe('Find brides from Udupi district.')
        ->and(DiscoverSeo::forSide('nri-matrimony', 'UAE Profiles', 'brides')['heading'])->toBeIn(['NRI Brides in UAE', 'Brides in UAE'])
        ->and(DiscoverSeo::forSide('second-marriage', 'Widow Profiles', 'grooms')['heading'])->toBe('Second Marriage – Widow – Grooms');
});

it('links a side only when it has 10 or more profiles', function () {
    $links = DiscoverController::sideLinks('karnataka-matrimony', 'udupi');
    expect(collect($links)->pluck('count', 'label')->all())->toBe(['Brides' => 30, 'Grooms' => 75])
        ->and($links[0]['url'])->toBe(route('discover.side', ['karnataka-matrimony', 'udupi', 'brides']))
        ->and(DiscoverController::sideLinks('karnataka-matrimony', 'kasaragod'))->toBe([]);
});

it('lists the Brides and Grooms pages with 10+ profiles in the sitemap', function () {
    $urls = DiscoverController::populatedPageUrls();

    expect($urls)->toContain(route('discover.results', ['karnataka-matrimony', 'udupi']) . '/brides')
        ->toContain(route('discover.results', ['karnataka-matrimony', 'udupi']) . '/grooms')
        ->not->toContain(route('discover.results', ['karnataka-matrimony', 'kasaragod']) . '/grooms');
});

it('sends a side with fewer than 10 profiles to the plain page', function () {
    $this->get('/discover/karnataka-matrimony/kasaragod/grooms')
        ->assertRedirect(route('discover.results', ['karnataka-matrimony', 'kasaragod']));
});

it('only knows brides and grooms', function () {
    $this->get('/discover/karnataka-matrimony/udupi/couples')->assertNotFound();
});
