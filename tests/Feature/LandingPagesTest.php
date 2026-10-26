<?php

use App\Models\StaticPage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/*
| Region / tradition pages (Oct 2026, catholicmatrimony.in SEO): a static page
| whose slug ends in "-matrimony" (e.g. mangalorean-catholic-matrimony) is
| served at the site root, listed in the sitemap and linked from the footer.
| Nothing changes on a site that has none.
*/

beforeEach(function () {
    Schema::create('static_pages', function (Blueprint $t) {
        $t->id();
        $t->string('slug')->unique();
        $t->string('title');
        $t->longText('content')->nullable();
        $t->string('meta_title')->nullable();
        $t->string('meta_description')->nullable();
        $t->boolean('is_active')->default(true);
        $t->boolean('is_system')->default(false);
        $t->boolean('show_in_footer')->default(false);
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

it('knows which pages are region pages, in their own order', function () {
    StaticPage::create(['slug' => 'goan-catholic-matrimony', 'title' => 'Goan Catholic Matrimony', 'content' => 'x', 'sort_order' => 2]);
    StaticPage::create(['slug' => 'mangalorean-catholic-matrimony', 'title' => 'Mangalorean Catholic Matrimony', 'content' => 'x', 'sort_order' => 1]);
    StaticPage::create(['slug' => 'tamil-catholic-matrimony', 'title' => 'Tamil', 'content' => 'x', 'is_active' => false]);
    StaticPage::create(['slug' => 'privacy-policy', 'title' => 'Privacy', 'content' => 'x']);
    StaticPage::create(['slug' => 'matrimony-tips', 'title' => 'Tips', 'content' => 'x']);

    expect(array_column(StaticPage::getLandingPages(), 'slug'))->toBe(['mangalorean-catholic-matrimony', 'goan-catholic-matrimony'])
        ->and(StaticPage::isLanding('privacy-policy'))->toBeFalse()
        ->and(StaticPage::isLanding('Goan-catholic-matrimony'))->toBeFalse();
});

it('serves them at the site root only, and sends the old /page/ address there', function () {
    $route = fn (string $path) => Route::getRoutes()->match(Illuminate\Http\Request::create($path))->getName();

    expect($route('/mangalorean-catholic-matrimony'))->toBe('landing')
        ->and($route('/about-us'))->not->toBe('landing')
        ->and($route('/search/results'))->not->toBe('landing');

    $this->get('/page/goan-catholic-matrimony')->assertStatus(301)->assertRedirect('/goan-catholic-matrimony');
    $this->get('/nowhere-matrimony')->assertNotFound();
});

it('lists them in the sitemap and links them from the footer', function () {
    StaticPage::create(['slug' => 'goan-catholic-matrimony', 'title' => 'Goan Catholic Matrimony', 'content' => 'x']);

    expect($this->get('/sitemap.xml')->assertOk()->getContent())->toContain('<loc>' . url('/goan-catholic-matrimony') . '</loc>');

    $layout = file_get_contents(resource_path('views/components/layouts/app.blade.php'));
    expect($layout)->toContain('@if($fLanding = \App\Models\StaticPage::getLandingPages())')
        ->and(file_get_contents(resource_path('views/pages/landing.blade.php')))->toContain(':canonicalUrl="$url"')->toContain("'@type' => 'WebPage'");
});

it('keeps the footer working on a site without the table', function () {
    Schema::dropIfExists('static_pages');
    Cache::flush();

    expect(StaticPage::getLandingPages())->toBe([]);
});
