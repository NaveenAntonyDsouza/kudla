<?php

use App\Models\SiteSetting;
use App\Models\StaticPage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| sitemap.xml + robots.txt
|--------------------------------------------------------------------------
| Both are what Search Console reads. The sitemap listed two URLs that have
| been 404s for months (/search/advanced-search, /search/search-by-id) and
| robots.txt pointed at a RELATIVE sitemap, which crawlers ignore.
*/

beforeEach(function () {
    Schema::create('static_pages', function (Blueprint $t) {
        $t->id();
        $t->string('slug')->unique();
        $t->string('title');
        $t->longText('content')->nullable();
        $t->boolean('is_active')->default(true);
        $t->integer('sort_order')->default(0);
        $t->timestamps();
    });
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Cache::flush();
});

afterEach(function () {
    Schema::dropIfExists('static_pages');
    Schema::dropIfExists('site_settings');
});

it('lists no URL that the site would 404 on', function () {
    foreach (['privacy-policy', 'terms-condition', 'refund-policy', 'child-safety', 'report-misuse'] as $i => $slug) {
        StaticPage::create(['slug' => $slug, 'title' => $slug, 'is_active' => true, 'sort_order' => $i]);
    }

    preg_match_all('/<loc>([^<]+)<\/loc>/', $this->get('/sitemap.xml')->assertOk()->getContent(), $m);

    expect($m[1])->not->toBeEmpty();
    $dead = array_values(array_filter($m[1], fn ($url) => ! Route::has('sitemap') // guard: routes are loaded
        || ! collect(Route::getRoutes()->getRoutesByMethod()['GET'] ?? [])->contains(
            fn ($r) => $r->matches(Illuminate\Http\Request::create($url), false)
        )));

    expect($dead)->toBe([]);
});

it('only advertises legal pages this site actually published', function () {
    StaticPage::create(['slug' => 'privacy-policy', 'title' => 'Privacy', 'is_active' => true]);
    StaticPage::create(['slug' => 'refund-policy', 'title' => 'Refund', 'is_active' => false]);

    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($xml)->toContain('/privacy-policy')
        ->and($xml)->not->toContain('/refund-policy')   // switched off
        ->and($xml)->not->toContain('/report-misuse');  // never installed
});

it('serves robots.txt with an absolute sitemap URL for this host', function () {
    $res = $this->get('/robots.txt')->assertOk();

    expect($res->headers->get('content-type'))->toStartWith('text/plain')
        ->and($res->getContent())->toContain('Disallow: /admin')
        ->and($res->getContent())->toContain('Sitemap: ' . url('/sitemap.xml'));
});

it('lets an admin edit robots.txt but always fixes the Sitemap line', function () {
    SiteSetting::setValue('robots_txt', "User-agent: *\nDisallow: /secret\n\nSitemap: /sitemap.xml");

    $body = $this->get('/robots.txt')->assertOk()->getContent();

    expect($body)->toContain('Disallow: /secret')
        ->and($body)->not->toContain("Sitemap: /sitemap.xml\n")
        ->and(substr_count($body, 'Sitemap:'))->toBe(1)
        ->and($body)->toContain('Sitemap: ' . url('/sitemap.xml'));
});

it('drops the sitemap line, and the sitemap itself, when sitemaps are off', function () {
    SiteSetting::setValue('sitemap_enabled', '0');

    $this->get('/sitemap.xml')->assertNotFound();
    expect($this->get('/robots.txt')->assertOk()->getContent())->not->toContain('Sitemap:');
});
