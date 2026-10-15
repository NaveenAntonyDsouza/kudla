<?php

use App\Http\Controllers\DiscoverController;
use App\Models\SiteSetting;
use App\Support\DiscoverSeo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/*
| SEO audit, 15 Oct 2026: 254 of Kudla's 257 pages had the same description,
| browse pages were titled "Billava Profiles", some titles repeated ("Marathi
| Profiles" x3), and the SEO Settings for About / Contact / Membership never
| applied because the layout looked them up under the wrong route names.
*/

beforeEach(function () {
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Cache::flush();
    SiteSetting::setValue('site_name', 'Kudla Matrimony');
});

afterEach(function () {
    Schema::dropIfExists('site_settings');
});

it('names the community and the region in a community page title and description', function () {
    SiteSetting::setValue('seo_region', 'Mangaluru, Udupi and Tulu Nadu');

    $seo = DiscoverSeo::forResults('community-matrimony', 'Billava Profiles');

    expect($seo['title'])->toBe('Billava Matrimony – Billava Brides & Grooms')
        ->and($seo['heading'])->toBe('Billava Matrimony')
        ->and($seo['description'])->toStartWith('Find Billava brides and grooms from Mangaluru, Udupi and Tulu Nadu on Kudla Matrimony.')
        ->and(mb_strlen($seo['description']))->toBeLessThanOrEqual(160);
});

it('leaves the place out on a site that has not set one', function () {
    $seo = DiscoverSeo::forResults('community-matrimony', 'Billava Profiles');

    expect($seo['description'])->toStartWith('Find Billava brides and grooms on Kudla Matrimony.')
        ->and($seo['intro'])->not->toContain(' from ');
});

it('gives pages that used to share a title different titles', function () {
    $titles = [
        DiscoverSeo::forResults('community-matrimony', 'Marathi Profiles')['title'],
        DiscoverSeo::forResults('mother-tongue-matrimony', 'Marathi Profiles')['title'],
        DiscoverSeo::forResults('karnataka-matrimony', 'Udupi Profiles')['title'],
        DiscoverSeo::forResults('diocese-matrimony', 'Udupi Profiles')['title'],
        DiscoverSeo::forResults('karnataka-matrimony', 'Belgaum Profiles')['title'],
        DiscoverSeo::forResults('diocese-matrimony', 'Belgaum Profiles')['title'],
    ];

    expect(array_unique($titles))->toHaveCount(count($titles));
});

it('keeps every title short enough for Google and every description under 160', function () {
    SiteSetting::setValue('seo_region', 'Mangaluru, Udupi and Tulu Nadu');
    $long = 'GSB - Goud Saraswat Brahmin Profiles';

    foreach (array_keys(config('discover')) as $category) {
        $seo = DiscoverSeo::forResults($category, $long);
        expect(mb_strlen($seo['description']))->toBeLessThanOrEqual(160);

        $seo = DiscoverSeo::forCategory($category, config("discover.{$category}"));
        expect(mb_strlen($seo['title']))->toBeLessThanOrEqual(48)
            ->and(mb_strlen($seo['description']))->toBeLessThanOrEqual(160);
    }

    expect(DiscoverSeo::forResults('community-matrimony', $long)['title'])->toBe('GSB - Goud Saraswat Brahmin Matrimony');
});

it('does not double the word in names that already carry it', function () {
    expect(DiscoverSeo::forResults('christian-matrimony', 'CSI Christian Profiles')['heading'])->toBe('CSI Christian Matrimony')
        ->and(DiscoverSeo::forResults('christian-matrimony', 'Jacobite Profiles')['heading'])->toBe('Jacobite Christian Matrimony')
        ->and(DiscoverSeo::forResults('catholic-matrimony', 'Roman Catholic Profiles')['heading'])->toBe('Roman Catholic Matrimony')
        ->and(DiscoverSeo::forResults('occupation-matrimony', 'Corporate communication Profiles')['heading'])->toBe('Corporate Communication Matrimony');
});

it('points Hindu caste pages at the matching community page and keeps them out of the sitemap', function () {
    Cache::put('community_caste_list_all', ['Billava', 'Bunts'], 60);

    expect(DiscoverController::canonicalUrl('hindu-matrimony', 'billava'))->toBe(route('discover.results', ['community-matrimony', 'billava']))
        ->and(DiscoverController::canonicalUrl('community-matrimony', 'billava'))->toBe(route('discover.results', ['community-matrimony', 'billava']))
        ->and(DiscoverController::canonicalUrl('karnataka-matrimony', 'udupi'))->toBe(route('discover.results', ['karnataka-matrimony', 'udupi']))
        ->and(file_get_contents(app_path('Http/Controllers/DiscoverController.php')))
        ->toContain("if (\$category === self::DUPLICATE_OF_COMMUNITY) {\n                continue;");
});

it('passes Discover titles as data, so "&" is not shown as "&amp;amp;"', function () {
    foreach (['results', 'category', 'hub'] as $view) {
        expect(file_get_contents(resource_path("views/discover/{$view}.blade.php")))
            ->toContain(':title="$seo[\'title\']" :metaDescription="$seo[\'description\']"');
    }
});

it('looks up per-page SEO under route names that exist', function () {
    preg_match('/\$pageSlugMap = \[(.*?)\];/s', file_get_contents(resource_path('views/components/layouts/app.blade.php')), $m);
    preg_match_all("/'([^']+)' => '([^']+)'/", $m[1], $pairs, PREG_SET_ORDER);

    expect($pairs)->not->toBeEmpty();
    foreach ($pairs as [, $route, $page]) {
        expect(Route::has($route))->toBeTrue("route {$route} does not exist")
            ->and(array_key_exists($page, \App\Models\PageSeoSetting::defaultPages()))->toBeTrue("{$page} is not an SEO Settings page");
    }
});

it('keeps the empty search forms out of Google', function () {
    foreach (['public', 'public-results'] as $view) {
        expect(file_get_contents(resource_path("views/search/{$view}.blade.php")))->toContain('robots="noindex, follow"');
    }

    expect(file_get_contents(app_path('Http/Controllers/SitemapController.php')))
        ->not->toContain("route('search.quick')")
        ->toContain("route('discover.hub')");
});

it('gives the register page a description and canonical', function () {
    expect(file_get_contents(resource_path('views/auth/register-step1.blade.php')))
        ->toContain("@include('components.partials.seo-basic', ['seoPage' => 'register'");
});
