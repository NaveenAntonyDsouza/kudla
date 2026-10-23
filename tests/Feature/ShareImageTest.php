<?php

use App\Models\PageSeoSetting;
use App\Models\SiteSetting;
use App\Models\ThemeSetting;
use App\Support\ShareImage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
| 23 Oct 2026: kudlamatrimony.com printed og:image="/storage/branding/…" (the
| header logo's relative path). Facebook and WhatsApp need an absolute URL, so
| shared links showed no picture; and the logo is a transparent PNG with black
| lettering, so sites can now set a share image that is used before it.
*/

beforeEach(function () {
    config(['app.url' => 'https://kudlamatrimony.com']);

    Schema::dropIfExists('site_settings');
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Schema::dropIfExists('theme_settings');
    Schema::create('theme_settings', function (Blueprint $t) {
        $t->id();
        $t->string('primary_color')->nullable();
        $t->string('logo_url')->nullable();
        $t->string('favicon_url')->nullable();
        $t->timestamps();
    });
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
    ThemeSetting::create(['logo_url' => '/storage/branding/kudla-logo-km-wordmark.png']);
    Cache::flush();
});

afterEach(function () {
    foreach (['site_settings', 'theme_settings', 'page_seo_settings', 'static_pages', 'advertisements', 'blog_posts'] as $table) {
        Schema::dropIfExists($table);
    }
});

it('turns a site path into an absolute URL and leaves full URLs alone', function () {
    expect(ShareImage::absolute('/storage/branding/logo.png'))->toBe('https://kudlamatrimony.com/storage/branding/logo.png')
        ->and(ShareImage::absolute('storage/blog/cover.jpg'))->toBe('https://kudlamatrimony.com/storage/blog/cover.jpg')
        ->and(ShareImage::absolute('https://cdn.example.com/a.png'))->toBe('https://cdn.example.com/a.png')
        ->and(ShareImage::absolute('http://example.com/a.png'))->toBe('http://example.com/a.png')
        ->and(ShareImage::absolute('//cdn.example.com/a.png'))->toBe('https://cdn.example.com/a.png')
        ->and(ShareImage::absolute('  '))->toBeNull()
        ->and(ShareImage::absolute(null))->toBeNull();

    config(['app.url' => 'https://kudlamatrimony.com/']);
    expect(ShareImage::absolute('/storage/x.png'))->toBe('https://kudlamatrimony.com/storage/x.png');
});

it('uses the page image, then the site share image, then the header logo', function () {
    expect(ShareImage::for())->toBe('https://kudlamatrimony.com/storage/branding/kudla-logo-km-wordmark.png');

    SiteSetting::setValue(ShareImage::SETTING, '/storage/branding/kudla-share-1200x630.png');
    expect(ShareImage::for(null, ''))->toBe('https://kudlamatrimony.com/storage/branding/kudla-share-1200x630.png')
        ->and(ShareImage::for(null, '/storage/blog/cover.jpg'))->toBe('https://kudlamatrimony.com/storage/blog/cover.jpg')
        ->and(ShareImage::for('https://cdn.example.com/faq.png', '/storage/blog/cover.jpg'))->toBe('https://cdn.example.com/faq.png');
});

it('has no image when the site has neither a share image nor a logo', function () {
    ThemeSetting::query()->update(['logo_url' => null]);
    Cache::forget('theme_settings');

    expect(ShareImage::for())->toBeNull()
        ->and(ShareImage::logo())->toBeNull();
});

it('asks X for the large card only when the picture is not the header logo', function () {
    expect(ShareImage::twitterCard(ShareImage::for()))->toBe('summary')
        ->and(ShareImage::twitterCard(null))->toBe('summary');

    SiteSetting::setValue(ShareImage::SETTING, '/storage/branding/kudla-share-1200x630.png');
    expect(ShareImage::twitterCard(ShareImage::for()))->toBe('summary_large_image');
});

it('prints an absolute og:image and twitter:image on the login and sign-up pages', function () {
    SiteSetting::setValue(ShareImage::SETTING, '/storage/branding/kudla-share-1200x630.png');

    $html = view('components.partials.seo-basic', ['seoPage' => 'login', 'seoTitle' => 'Login'])->render();

    expect($html)->toContain('<meta property="og:image" content="https://kudlamatrimony.com/storage/branding/kudla-share-1200x630.png">')
        ->toContain('<meta name="twitter:image" content="https://kudlamatrimony.com/storage/branding/kudla-share-1200x630.png">')
        ->toContain('<meta name="twitter:card" content="summary_large_image">');

    PageSeoSetting::create(['page_slug' => 'login', 'og_image_url' => 'https://cdn.example.com/login.png']);
    PageSeoSetting::clearCache();

    expect(view('components.partials.seo-basic', ['seoPage' => 'login', 'seoTitle' => 'Login'])->render())
        ->toContain('<meta property="og:image" content="https://cdn.example.com/login.png">');
});

it('prints absolute images on a page that uses the main layout', function () {
    // the header and footer read these too
    foreach (['2026_04_16_180000_create_static_pages_table', '2026_04_16_190000_create_advertisements_table', '2026_10_15_000001_create_blog_posts_table'] as $migration) {
        $m = require database_path("migrations/{$migration}.php");
        $m->down();
        $m->up();
    }

    $html = $this->withoutVite()->get('/faq')->assertOk()->getContent();

    expect($html)->toContain('<meta property="og:image" content="https://kudlamatrimony.com/storage/branding/kudla-logo-km-wordmark.png">')
        ->toContain('<meta name="twitter:card" content="summary">')
        ->toContain('"logo": "https://kudlamatrimony.com/storage/branding/kudla-logo-km-wordmark.png"')
        ->not->toContain('content="/storage/');

    SiteSetting::setValue(ShareImage::SETTING, '/storage/branding/kudla-share-1200x630.png');
    $html = $this->withoutVite()->get('/faq')->assertOk()->getContent();

    expect($html)->toContain('<meta property="og:image" content="https://kudlamatrimony.com/storage/branding/kudla-share-1200x630.png">')
        ->toContain('<meta name="twitter:image" content="https://kudlamatrimony.com/storage/branding/kudla-share-1200x630.png">')
        ->toContain('<meta name="twitter:card" content="summary_large_image">')
        ->toContain('"logo": "https://kudlamatrimony.com/storage/branding/kudla-logo-km-wordmark.png"');
});
