<?php

use App\Http\Controllers\Api\V1\StaticPageController;
use App\Models\StaticPage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| StaticPageController — GET /api/v1/static-pages/{slug}
|--------------------------------------------------------------------------
| Inline static_pages + site_settings tables; cache cleared between tests
| since StaticPage::getBySlug caches for 1h.
*/

beforeEach(function () {
    if (! Schema::hasTable('static_pages')) {
        Schema::create('static_pages', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 100)->unique();
            $t->string('title', 200);
            $t->longText('content');
            $t->string('meta_title', 200)->nullable();
            $t->string('meta_description', 500)->nullable();
            $t->boolean('is_active')->default(true);
            $t->boolean('is_system')->default(false);
            $t->integer('sort_order')->default(0);
            $t->boolean('show_in_footer')->default(false);
            $t->timestamps();
        });
    }
    if (! Schema::hasTable('site_settings')) {
        Schema::create('site_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });
    }
    // Clear any cached pages between tests.
    Cache::flush();
});

afterEach(function () {
    Schema::dropIfExists('site_settings');
    Schema::dropIfExists('static_pages');
});

/* ==================================================================
 |  show
 | ================================================================== */

it('returns 404 when slug does not exist', function () {
    $response = app(StaticPageController::class)->show('nonexistent');

    expect($response->getStatusCode())->toBe(404);
    expect($response->getData(true)['error']['code'])->toBe('NOT_FOUND');
});

it('returns 404 when page exists but is inactive', function () {
    StaticPage::create([
        'slug' => 'about',
        'title' => 'About',
        'content' => 'hello',
        'is_active' => false,
    ]);

    $response = app(StaticPageController::class)->show('about');

    expect($response->getStatusCode())->toBe(404);
});

it('returns title + rendered content + meta + updated_at', function () {
    StaticPage::create([
        'slug' => 'about',
        'title' => 'About Us',
        'content' => '<p>About</p>',
        'meta_title' => 'About Us — Matrimony',
        'meta_description' => 'About this site',
        'is_active' => true,
    ]);

    $response = app(StaticPageController::class)->show('about');
    $data = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(200);
    expect($data)->toMatchArray([
        'slug' => 'about',
        'title' => 'About Us',
        'content_html' => '<p>About</p>',
        'meta_title' => 'About Us — Matrimony',
        'meta_description' => 'About this site',
    ]);
    expect($data['updated_at'])->toBeString();
});

it('substitutes {{ app_name }} and {{ current_year }} into rendered content', function () {
    StaticPage::create([
        'slug' => 'terms',
        'title' => 'Terms',
        'content' => 'Welcome to {{ app_name }}. © {{ current_year }}.',
        'is_active' => true,
    ]);

    $response = app(StaticPageController::class)->show('terms');
    $html = $response->getData(true)['data']['content_html'];

    expect($html)->not->toContain('{{ app_name }}');
    expect($html)->not->toContain('{{ current_year }}');
    expect($html)->toContain((string) date('Y'));
    // app_name from config('app.name') — at minimum it gets inlined.
    expect($html)->toMatch('/^Welcome to .+\. © \d{4}\.$/');
});
