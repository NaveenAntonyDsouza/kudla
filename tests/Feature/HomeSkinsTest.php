<?php

use Illuminate\Support\Facades\Blade;

/*
| Homepage designs "Tulu Nadu" (Kudla) and "Parish" (Catholic sites), owner
| 11 Oct 2026: the Classic layout with a cultural skin — artwork instead of
| the hero photo (no rings for Tulu weddings), homepage-only colours, fonts.
*/

it('offers the two new designs in the admin and lets the homepage use them', function () {
    expect(file_get_contents(app_path('Http/Controllers/HomeController.php')))->toContain("['classic', 'modern', 'premium', 'tulunadu', 'parish', 'coastal', 'garland', 'harmony']")
        ->and(file_get_contents(app_path('Filament/Pages/HomepageSettings.php')))->toContain("'tulunadu' => 'Tulu Nadu")->toContain("'parish' => 'Parish")->toContain("'coastal' => 'Coastal")
        ->and(trim(file_get_contents(resource_path('views/pages/home/tulunadu.blade.php'))))->toEndWith("@include('pages.home.classic', ['skin' => 'tulunadu'])")
        ->and(trim(file_get_contents(resource_path('views/pages/home/parish.blade.php'))))->toEndWith("@include('pages.home.classic', ['skin' => 'parish'])")
        ->and(trim(file_get_contents(resource_path('views/pages/home/coastal.blade.php'))))->toEndWith("@include('pages.home.classic', ['skin' => 'coastal'])");
});

it('skins Classic only when a design asks for it: artwork instead of the photo, a wrapper, dividers', function () {
    $classic = file_get_contents(resource_path('views/pages/home/classic.blade.php'));

    expect($classic)->toContain("\$skin = in_array(\$skin ?? null, ['tulunadu', 'parish', 'coastal'], true) ? \$skin : null;")
        ->toContain("<x-dynamic-component :component=\"'home-art.'.\$skin.'-hero'\" />")
        ->toContain('@elseif($heroImageUrl)')
        ->toContain('<div class="home-skin home-skin-{{ $skin }}">')
        ->and(substr_count($classic, "'-divider'"))->toBe(3)
        ->and($classic)->toContain('1 Corinthians 13:4');
});

it('draws Tulu Nadu wedding symbols (thorana, mallige, kalasha, deepa), never rings, with a phone version', function () {
    $art = Blade::render('<x-home-art.tulunadu-hero />');

    expect($art)->toContain('home-art-wide')->toContain('home-art-narrow')
        ->toContain('M0 14 Q 720 82 1440 14')->not->toContain('ring')
        ->and(substr_count($art, 'href="#tn-leaf"'))->toBeGreaterThan(20)
        ->and(Blade::render('<x-home-art.parish-hero />'))->toContain('pa-arch')->toContain('home-art-narrow')
        ->and(Blade::render('<x-home-art.tulunadu-divider />'))->toContain('home-divider')
        ->and(Blade::render('<x-home-art.parish-divider />'))->toContain('home-divider');
});

it('changes colours on the homepage only, inside the skin wrapper', function () {
    $tulu = view('pages.home.skins.style', ['skin' => 'tulunadu'])->render();
    $parish = view('pages.home.skins.style', ['skin' => 'parish'])->render();

    expect($tulu)->toContain('.home-skin-tulunadu {')->toContain('--color-primary: #8a1c2b')->toContain('Noto+Serif+Kannada')->not->toContain(':root')
        ->and($parish)->toContain('.home-skin-parish {')->toContain('--color-primary: #1f3a6b')->toContain('Cormorant+Garamond');
});

it('keeps the Coastal design secular: sea, palms and a tiled house, no wedding or religious symbols', function () {
    $art = Blade::render('<x-home-art.coastal-hero />');
    $divider = Blade::render('<x-home-art.coastal-divider />');
    $style = view('pages.home.skins.style', ['skin' => 'coastal'])->render();

    expect($art)->toContain('home-art-wide')->toContain('home-art-narrow')->toContain('href="#cs-palm"')
        ->not->toContain('tn-leaf')->not->toContain('ring')->not->toContain('cross')
        ->and($divider)->toContain('home-divider')->not->toContain('#f39c12')
        ->and($style)->toContain('.home-skin-coastal {')->toContain('--color-primary: #b71c1c')->not->toContain(':root');
});

it('offers the Tulu Nadu design in the admin on Kudla Matrimony only', function () {
    $offers = fn () => (fn () => static::offersTuluNadu())->call(new \App\Filament\Pages\HomepageSettings);

    config(['app.url' => 'https://kudlamatrimony.com']);
    expect($offers())->toBeTrue();

    config(['app.url' => 'https://amchimatrimony.com']);
    expect(file_get_contents(app_path('Filament/Pages/HomepageSettings.php')))
        ->toContain("fn (\$design) => \$design !== 'tulunadu' || static::offersTuluNadu()")
        ->toContain("str_contains((string) config('app.url'), 'kudlamatrimony')");
});
