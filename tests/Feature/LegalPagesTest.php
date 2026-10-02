<?php

use App\Models\SiteSetting;
use App\Models\StaticPage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Legal pages (resources/legal) + `legal:install`
|--------------------------------------------------------------------------
| Five sites had no Privacy / Terms / Refund pages at all. The installer
| adds them from reviewed drafts and never overwrites an existing page
| unless told to; Grievance Officer and courts city come from settings.
*/

beforeEach(function () {
    Schema::create('static_pages', function (Blueprint $t) {
        $t->id();
        $t->string('slug')->unique();
        $t->string('title');
        $t->longText('content')->nullable();
        $t->string('meta_title')->nullable();
        $t->text('meta_description')->nullable();
        $t->boolean('is_active')->default(true);
        $t->boolean('is_system')->default(false);
        $t->integer('sort_order')->default(0);
        $t->boolean('show_in_footer')->default(false);
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

it('installs all six pages on a site that has none', function () {
    $this->artisan('legal:install', ['variant' => 'matrimony-in', '--about' => 'crastamatrimony.com'])->assertSuccessful();

    expect(StaticPage::orderBy('sort_order')->pluck('slug')->all())->toBe([
        'privacy-policy', 'terms-condition', 'about-us', 'refund-policy', 'child-safety', 'report-misuse',
    ])->and(StaticPage::where('show_in_footer', true)->where('is_active', true)->count())->toBe(6);
});

it('keeps existing pages unless they are named in --replace', function () {
    StaticPage::create(['slug' => 'privacy-policy', 'title' => 'Privacy', 'content' => 'EDITED BY ADMIN', 'is_active' => true]);
    StaticPage::create(['slug' => 'about-us', 'title' => 'About', 'content' => 'OUR STORY', 'is_active' => true]);

    $this->artisan('legal:install', ['variant' => 'matrimony-in'])->assertSuccessful();
    expect(StaticPage::where('slug', 'privacy-policy')->value('content'))->toBe('EDITED BY ADMIN');

    $this->artisan('legal:install', ['variant' => 'matrimony-in', '--replace' => ['privacy-policy']])->assertSuccessful();
    expect(StaticPage::where('slug', 'privacy-policy')->value('content'))->toContain('Grievance Officer')
        ->and(StaticPage::where('slug', 'about-us')->value('content'))->toBe('OUR STORY');
});

it('a dry run changes nothing', function () {
    $this->artisan('legal:install', ['variant' => 'dating-us', '--dry-run' => true])->assertSuccessful();

    expect(StaticPage::count())->toBe(0);
});

it('fills in the Grievance Officer and courts from settings, with safe fallbacks', function () {
    $this->artisan('legal:install', ['variant' => 'matrimony-in'])->assertSuccessful();
    $privacy = fn () => StaticPage::where('slug', 'privacy-policy')->first()->rendered_content;
    $terms = fn () => StaticPage::where('slug', 'terms-condition')->first()->rendered_content;
    config(['app.name' => 'Test Matrimony']);

    expect($privacy())->toContain('<strong>Grievance Officer, Test Matrimony</strong>')
        ->and($terms())->toContain('courts in India have jurisdiction');

    SiteSetting::setValue('grievance_officer_name', "Naveen D'Souza");
    SiteSetting::setValue('legal_courts_city', 'Mangalore');

    expect($privacy())->toContain('<strong>Naveen D&#039;Souza</strong>')
        ->and($terms())->toContain('courts at Mangalore have jurisdiction');
});

it('drafts have no unfilled [BRACKETS] and only known placeholders', function () {
    $known = ['app_name', 'email', 'phone', 'grievance_officer', 'courts'];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('legal'), FilesystemIterator::SKIP_DOTS)) as $file) {
        $html = file_get_contents($file->getPathname());
        expect($html)->not->toMatch('/\[[A-Z ]{3,}\]/');
        preg_match_all('/\{\{ ?([a-z_]+) ?\}\}/', $html, $m);
        expect(array_diff(array_unique($m[1]), $known))->toBe([], $file->getFilename());
    }
});
