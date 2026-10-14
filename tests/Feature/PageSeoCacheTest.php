<?php

use App\Models\PageSeoSetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
| 14 Oct 2026 outage: a cached PageSeoSetting model came back from the cache as
| __PHP_Incomplete_Class (cache.serializable_classes = false), so every page
| using the layout returned 500. The cache must hold plain data only.
*/

beforeEach(function () {
    Schema::dropIfExists('page_seo_settings');
    Schema::create('page_seo_settings', function (Blueprint $t) {
        $t->id();
        $t->string('page_slug')->unique();
        $t->string('page_label')->nullable();
        $t->string('meta_title')->nullable();
        $t->text('meta_description')->nullable();
        $t->string('og_image_url')->nullable();
        $t->timestamps();
    });
    Cache::flush();
});

it('caches page SEO as plain attributes and rebuilds the model on every read', function () {
    PageSeoSetting::create(['page_slug' => 'home', 'meta_title' => 'Kudla Matrimony – Mangalore', 'meta_description' => 'Tulu Nadu']);

    $first = PageSeoSetting::getForPage('home');
    $second = PageSeoSetting::getForPage('home');

    expect(Cache::get('page_seo_v2.home'))->toBeArray()
        ->and($first)->toBeInstanceOf(PageSeoSetting::class)
        ->and($second->meta_title)->toBe('Kudla Matrimony – Mangalore');
});

it('returns null for a page without custom SEO, also from the cache', function () {
    expect(PageSeoSetting::getForPage('faq'))->toBeNull()
        ->and(PageSeoSetting::getForPage('faq'))->toBeNull();
});
