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
| orange with a new homepage). "Harmony" (same day, owner: make the homepage
| culture- and religion-neutral) is the same page with abstract ribbons, plus
| new sections each with an admin switch: plans, safety, browse profiles by,
| contact & help, and personal matchmaking (off until offered). Same content and admin switches as Classic and
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
    Schema::dropIfExists('membership_plans');
    Schema::create('membership_plans', function (Blueprint $t) {
        $t->id();
        $t->string('plan_name');
        $t->string('slug')->nullable();
        $t->integer('duration_months')->default(0);
        $t->integer('price_inr')->default(0);
        $t->integer('strike_price_inr')->nullable();
        $t->json('features')->nullable();
        $t->boolean('is_popular')->default(false);
        $t->boolean('is_highlighted')->default(false);
        $t->integer('sort_order')->default(0);
        $t->boolean('is_active')->default(true);
        $t->timestamps();
    });
    ThemeSetting::create([
        'primary_color' => '#8B1D91', 'primary_hover' => '#6B1571', 'primary_light' => '#F3E8F7',
        'secondary_color' => '#E37404', 'secondary_hover' => '#C25F02', 'secondary_light' => '#FFF1E0',
        'heading_font' => 'Playfair Display', 'body_font' => 'Inter',
        'logo_url' => '/storage/branding/kudla-logo-purple-orange.png',
    ]);
    SiteSetting::setValue('hero_heading', 'ಇತ್ತೆ ಪೊದು ನಾಡುನಿ ಮಸ್ತ್ ಸುಲಭ');
    SiteSetting::setValue('search_heading', '');
    Cache::flush();
    Cache::put('home.browse.tabs', [], 60);   // Discover counts need the member tables; tests below set their own tabs

    $this->home = fn (string $design = 'garland') => view('pages.home.' . $design, [
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
    foreach (['site_settings', 'theme_settings', 'page_seo_settings', 'static_pages', 'advertisements', 'blog_posts', 'testimonials', 'membership_plans'] as $table) {
        Schema::dropIfExists($table);
    }
});

it('is offered in the admin and accepted by the homepage', function () {
    expect(file_get_contents(app_path('Http/Controllers/HomeController.php')))->toContain("'coastal', 'garland', 'harmony']")
        ->and(file_get_contents(app_path('Filament/Pages/HomepageSettings.php')))->toContain("'garland' => 'Garland");
});

it('keeps Classic\'s registration and search forms', function () {
    $html = ($this->home)();

    expect($html)->toContain('class="home-garland variant-garland"')
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
        ->and($html)->toContain('Search for Your Life Partner')        // blank admin heading falls back
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

it('offers Harmony: the same page with ribbons instead of garlands', function () {
    expect(file_get_contents(app_path('Http/Controllers/HomeController.php')))->toContain("'garland', 'harmony']")
        ->and(file_get_contents(app_path('Filament/Pages/HomepageSettings.php')))->toContain("'harmony' => 'Harmony")
        ->and(trim(file_get_contents(resource_path('views/pages/home/harmony.blade.php'))))->toEndWith("@include('pages.home.garland', ['variant' => 'harmony'])");

    $html = ($this->home)('harmony');

    expect($html)->toContain('class="home-garland variant-harmony"')->toContain('g-hero-harmony')
        ->toContain('h-art-wide')->toContain('h-art-narrow')
        ->not->toContain('class="g-art g-art-wide"')->not->toContain('id="glw-o"')            // no garlands
        ->toContain('action="' . route('register.store1') . '"')
        ->toContain('ಇತ್ತೆ ಪೊದು ನಾಡುನಿ ಮಸ್ತ್ ಸುಲಭ');

    $art = Blade::render('<x-home-art.harmony-hero />');
    expect(substr_count($art, '<path d="M'))->toBeGreaterThan(50)
        ->and($art)->not->toContain('cross')->not->toContain('ring')->not->toContain('flower')
        ->and(Blade::render('<x-home-art.harmony-divider />'))->toContain('g-ornament');
});

it('shows plans, safety and contact by default, and personal matchmaking only when switched on', function () {
    DB::table('membership_plans')->insert([
        ['plan_name' => 'Free', 'price_inr' => 0, 'duration_months' => 0, 'strike_price_inr' => null, 'features' => json_encode(['Create your profile']), 'is_popular' => 0, 'sort_order' => 0, 'is_active' => 1],
        ['plan_name' => 'Gold', 'price_inr' => 2499, 'duration_months' => 6, 'strike_price_inr' => 3999, 'features' => json_encode(['View up to 200 contacts']), 'is_popular' => 1, 'sort_order' => 2, 'is_active' => 1],
        ['plan_name' => 'Silver', 'price_inr' => 1499, 'duration_months' => 3, 'strike_price_inr' => null, 'features' => json_encode('["Saved as text"]'), 'is_popular' => 0, 'sort_order' => 1, 'is_active' => 1],
        ['plan_name' => 'Old', 'price_inr' => 1, 'duration_months' => 1, 'strike_price_inr' => null, 'features' => null, 'is_popular' => 0, 'sort_order' => 5, 'is_active' => 0],
    ]);
    SiteSetting::setValue('phone', '7760020186');
    SiteSetting::setValue('whatsapp', '7760020186');
    SiteSetting::setValue('email', 'info@kudlamatrimony.com');
    SiteSetting::setValue('address', 'Ganjimutt, Mangaluru');
    $html = ($this->home)('harmony');

    expect($html)->toContain('Membership Plans')->toContain('&#8377;2,499')->toContain('<s>&#8377;3,999</s>')->toContain('for 6 months')
        ->toContain('Most popular')->toContain('View up to 200 contacts')
        ->toContain('Saved as text</li>')->not->toContain('[&quot;Saved')           // features saved as JSON text still list properly
        ->not->toContain('<h3>Old</h3>')
        ->toContain('Your Safety Comes First')->and(substr_count($html, 'It is not a dating site.'))->toBeLessThan(2)->and($html)  // once, in the footer
        ->toContain('href="' . route('report-misuse') . '"')
        ->toContain("We're Here to Help")->toContain('+91 77600 20186')->toContain('href="tel:+917760020186"')
        ->toContain('href="https://wa.me/917760020186"')->toContain('mailto:info@kudlamatrimony.com')->toContain('Ganjimutt, Mangaluru')
        ->not->toContain('Personal Matchmaking');

    SiteSetting::setValue('show_assisted_matchmaking', '1');
    SiteSetting::setValue('assisted_title', 'Assisted Service');
    foreach (['show_plans_section', 'show_safety_section', 'show_contact_section'] as $key) {
        SiteSetting::setValue($key, '0');
    }
    $html = ($this->home)('harmony');

    expect($html)->toContain('Assisted Service')->toContain('Meet your advisor')
        ->toContain('https://wa.me/917760020186?text=Hi%2C%20I%20would%20like%20to%20know%20about%20personal%20matchmaking.')
        ->not->toContain('<h2 class="g-h2">Membership Plans</h2>')->not->toContain('Your Safety Comes First')->not->toContain("We're Here to Help");
});

it('lists every religion and community side by side in "Browse profiles by"', function () {
    Cache::put('home.browse.tabs', [
        'religion' => ['label' => 'Religion & Community', 'religions' => [['label' => 'Christian', 'url' => '/discover/christian-matrimony'], ['label' => 'Muslim', 'url' => '/discover/muslim-matrimony']],
            'links' => [['label' => 'Bunts', 'url' => '/discover/community-matrimony/bunts']], 'all' => 'community-matrimony', 'all_url' => '/discover/community-matrimony'],
        'nri' => ['label' => 'NRI', 'religions' => [], 'links' => [['label' => 'United Arab Emirates', 'url' => '/discover/nri-matrimony/uae']], 'all' => 'nri-matrimony', 'all_url' => '/discover/nri-matrimony'],
    ], 60);
    $html = ($this->home)('harmony');

    expect($html)->toContain('Browse Profiles By')->toContain('role="tablist"')
        ->toContain('>Religion &amp; Community</button>')->toContain('>NRI</button>')
        ->toContain('<a href="/discover/muslim-matrimony" class="g-chip">Muslim</a>')
        ->toContain('<a href="/discover/nri-matrimony/uae" class="g-chip">United Arab Emirates</a>');   // every tab is in the HTML

    SiteSetting::setValue('show_browse_section', '0');
    expect(($this->home)('harmony'))->not->toContain('Browse Profiles By');
});

it('builds browse links from Discover pages that have members, without "Profiles" in the label', function () {
    Cache::put('discover.counts.second-marriage', ['widow' => 1, 'divorced' => 4], 60);

    expect(\App\Http\Controllers\DiscoverController::homeLinks('second-marriage'))->toBe([
        ['label' => 'Divorced', 'url' => route('discover.results', ['second-marriage', 'divorced'])],
        ['label' => 'Widow / Widower', 'url' => route('discover.results', ['second-marriage', 'widow'])],
    ])
        ->and(array_column(\App\Http\Controllers\DiscoverController::homeLinks('second-marriage', 1, true), 'label'))->toBe(['Divorced'])
        ->and(\App\Http\Controllers\DiscoverController::homeLinks('kannadiga-matrimony'))->toBe([])
        ->and(\App\Http\Controllers\DiscoverController::homeLinks('no-such-page'))->toBe([]);

    // religions with members get a chip each, A–Z (24 Oct: an array union once dropped them all)
    Cache::forget('home.browse.tabs');
    Cache::put('discover.counts.muslim-matrimony', ['sunni' => 2], 60);
    Cache::put('discover.counts.hindu-matrimony', ['bunts' => 30], 60);
    Cache::put('discover.counts.jain-matrimony', [], 60);
    $tabs = \App\Support\HomeBrowse::tabs();
    expect(array_column($tabs['religion']['religions'], 'label'))->toBe(['Hindu', 'Muslim'])
        ->and($tabs['religion']['religions'][1]['url'])->toBe(route('discover.category', 'muslim-matrimony'))
        ->and($tabs['second']['links'][0]['label'])->toBe('Divorced')
        ->and($tabs['second']['religions'])->toBe([]);

    // the hourly rebuild only runs where the homepage shows the tabs
    SiteSetting::setValue('homepage_template', 'classic');
    Cache::put('home.browse.tabs', ['kept' => true], 60);
    \App\Support\HomeBrowse::refresh();
    expect(Cache::get('home.browse.tabs'))->toBe(['kept' => true])
        ->and(file_get_contents(base_path('routes/console.php')))->toContain('HomeBrowse::refresh()');
});

it('lets the admin switch each new section on or off, matchmaking off by default', function () {
    $admin = file_get_contents(app_path('Filament/Pages/HomepageSettings.php'));

    foreach (['show_plans_section', 'show_safety_section', 'show_browse_section', 'show_contact_section', 'show_assisted_matchmaking'] as $key) {
        expect($admin)->toContain("Toggle::make('{$key}')")->toContain("'{$key}' => (\$settings['{$key}']");
    }
    expect($admin)->toContain("'show_assisted_matchmaking' => (\$settings['show_assisted_matchmaking'] ?? '0') === '1'")
        ->toContain("'show_contact_section', 'show_assisted_matchmaking', 'show_not_dating_note', 'show_app_section'];");
});

it('centres two Why Choose cards in two columns instead of leaving a third column empty', function () {
    SiteSetting::setValue('why_choose_us', json_encode([
        ['title' => 'Community Focused', 'description' => 'Families.', 'icon' => 'heart'],
        ['title' => 'Easy to Use', 'description' => 'Simple.', 'icon' => 'star'],
    ]));

    expect(($this->home)('harmony'))->toContain('<div class="g-why cols-2">')
        ->toContain('.home-garland .g-why.cols-2 { max-width: 820px;')
        ->not->toContain('100% Privacy');
});

it('names the after-the-fact check honestly when profiles go live without review', function () {
    expect(file_get_contents(resource_path('views/pages/home/sections/safety.blade.php')))
        ->toContain(": ['Fake profiles removed', 'Our team removes fake or misleading profiles, and you can report anyone to us.'")
        ->not->toContain('Watched by our team');
});
