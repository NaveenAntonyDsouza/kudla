<?php

use App\Models\SiteSetting;
use App\Models\ThemeSetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| "Garland" homepage design (24 Oct 2026, owner: switch Kudla to purple and
| orange with a new homepage). Same content and admin switches as Classic and
| Classic's registration and search forms, a new look: marigold-and-jasmine
| garlands over a twilight sky in the theme colours, a floating search card,
| ivory sections, numbered steps, community chips.
*/

beforeEach(function () {
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
        foreach (['primary_color', 'primary_hover', 'primary_light', 'secondary_color', 'secondary_hover', 'secondary_light', 'heading_font', 'body_font', 'logo_url', 'favicon_url'] as $c) {
            $t->string($c)->nullable();
        }
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
    foreach (['2026_04_16_180000_create_static_pages_table', '2026_04_16_190000_create_advertisements_table', '2026_10_15_000001_create_blog_posts_table', '2026_03_31_152110_create_testimonials_table'] as $migration) {
        $m = require database_path("migrations/{$migration}.php");
        $m->down();
        $m->up();
    }
    ThemeSetting::create([
        'primary_color' => '#8B1D91', 'primary_hover' => '#6B1571', 'primary_light' => '#F3E8F7',
        'secondary_color' => '#E37404', 'secondary_hover' => '#C25F02', 'secondary_light' => '#FFF1E0',
        'heading_font' => 'Playfair Display', 'body_font' => 'Inter',
        'logo_url' => '/storage/branding/kudla-logo-purple-orange.png',
    ]);
    SiteSetting::setValue('hero_heading', 'ಇತ್ತೆ ಪೊದು ನಾಡುನಿ ಮಸ್ತ್ ಸುಲಭ');
    SiteSetting::setValue('search_heading', '');
    Cache::flush();

    $this->home = fn () => view('pages.home.garland', [
        'communities' => collect(['Jain' => collect([(object) ['id' => 7, 'community_name' => 'Bunt Jain']])]),
        'stats' => ['members' => 679, 'marriages' => 10, 'years' => 4],
        'featuredProfiles' => collect(),
        'showFeatured' => true,
        'showCommunities' => true,
        'showWhyChooseUs' => true,
        'showSuccessStories' => true,
        'totalProfiles' => 0,
        'faqs' => collect([(object) ['question' => 'Is registration free?', 'answer' => 'Yes, registration is free.']]),
        'siteTitle' => 'Kudla Matrimony',
        'siteMetaDesc' => 'Tulu Nadu matrimony',
    ])->render();
    view()->share('errors', new \Illuminate\Support\ViewErrorBag);   // normally shared by the web middleware
});

afterEach(function () {
    foreach (['site_settings', 'theme_settings', 'page_seo_settings', 'static_pages', 'advertisements', 'blog_posts', 'testimonials'] as $table) {
        Schema::dropIfExists($table);
    }
});

it('is offered in the admin and accepted by the homepage', function () {
    expect(file_get_contents(app_path('Http/Controllers/HomeController.php')))->toContain("'coastal', 'garland']")
        ->and(file_get_contents(app_path('Filament/Pages/HomepageSettings.php')))->toContain("'garland' => 'Garland");
});

it('keeps Classic\'s registration and search forms', function () {
    $html = ($this->home)();

    expect($html)->toContain('class="home-garland"')
        ->toContain('action="' . route('register.store1') . '"')
        ->toContain('name="full_name"')->toContain('name="date_of_birth"')->toContain('name="email"')->toContain('name="password"')
        ->toContain('<input type="hidden" name="gender" :value="gender">')
        ->toContain('<form action="/search" method="GET" class="g-search-grid">')
        ->toContain('name="age_from"')->toContain('name="age_to"')->toContain('name="community"')
        ->toContain('ಇತ್ತೆ ಪೊದು ನಾಡುನಿ ಮಸ್ತ್ ಸುಲಭ');
});

it('draws garlands, ornaments and sections from the admin content', function () {
    Schema::disableForeignKeyConstraints();   // testimonials.user_id points at users, not needed here
    DB::table('testimonials')->insert(['couple_names' => 'Anitha & Prakash', 'location' => 'Udupi', 'story' => 'We met here.', 'is_visible' => 1, 'display_order' => 1, 'created_at' => now(), 'updated_at' => now()]);
    $html = ($this->home)();

    expect($html)->toContain('g-art-wide')->toContain('g-art-narrow')
        ->and(substr_count($html, 'class="g-ornament"'))->toBeGreaterThanOrEqual(5)
        ->and($html)->toContain('Search for Your Perfect Partner')        // blank admin heading falls back
        ->toContain('How It Works')->toContain('Why Choose')
        ->toContain('Anitha &amp; Prakash')
        ->toContain('href="' . url('/search/quick-search?caste=Bunt+Jain') . '" class="g-chip">Bunt Jain</a>')
        ->toContain('Is registration free?')
        ->toContain('targetMembers: 679');
});

it('mixes its shades from the site theme colours', function () {
    $html = ($this->home)();

    expect($html)->toContain('--g-p: #8b1d91;')->toContain('--g-a: #e37404;')
        ->toContain('--g-deep: #400d47;')                                 // night sky: theme purple deepened
        ->toContain('--g-a-rgb: 227,116,4;');

    ThemeSetting::query()->update(['primary_color' => '#1F3A6B', 'secondary_color' => 'not-a-colour']);
    Cache::forget('theme_settings');
    $html = ($this->home)();

    expect($html)->toContain('--g-p: #1f3a6b;')->toContain('--g-deep: #171839;')
        ->toContain('--g-a: #e37404;');                                   // invalid colour falls back to marigold
});

it('hangs marigold and jasmine garlands only, with a phone version', function () {
    $art = Blade::render('<x-home-art.garland-hero />');

    expect($art)->toContain('class="g-art g-art-wide"')->toContain('class="g-art g-art-narrow"')
        ->toContain('id="glw-o"')->toContain('id="glw-y"')->toContain('id="glw-j"')->toContain('id="gln-o"')
        ->and(substr_count($art, '<use '))->toBeGreaterThan(250)
        ->and($art)->not->toContain('cross')->not->toContain('kalasha')->not->toContain('ring')
        ->and(Blade::render('<x-home-art.garland-divider />'))->toContain('g-ornament')
        ->and(Blade::render('<x-home-art.garland-divider :light="true" />'))->toContain('rgba(255,255,255,0.55)');
});
