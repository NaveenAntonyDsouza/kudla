<?php

use App\Models\SiteSetting;
use App\Models\ThemeSetting;
use App\Support\SiteContact;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
| Header and footer review (25 Oct 2026, owner OK'd all ten points after
| comparing with Chavara Matrimony): Register Free is the filled button, a
| call / WhatsApp strip for visitors, the number + WhatsApp in sign-up
| headers, a real header on the login page, About Us once, contact details
| and app buttons in the footer, "not a dating site" (switchable), no "!."
| after the tagline, and footers in the brand's deep colour.
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
        foreach (['primary_color', 'primary_hover', 'primary_light', 'secondary_color', 'logo_url'] as $c) {
            $t->string($c)->nullable();
        }
        $t->timestamps();
    });
    ThemeSetting::create(['primary_color' => '#8B1D91']);
    Cache::flush();
});

afterEach(function () {
    Schema::dropIfExists('site_settings');
    Schema::dropIfExists('theme_settings');
});

it('formats the site phone, WhatsApp and the footer colour', function () {
    expect(SiteContact::phoneDisplay())->toBe('')->and(SiteContact::phoneHref())->toBeNull();

    SiteSetting::setValue('phone', '7760020186');
    SiteSetting::setValue('whatsapp', '7760020186');
    expect(SiteContact::phoneDisplay())->toBe('+91 77600 20186')
        ->and(SiteContact::phoneHref())->toBe('tel:+917760020186')
        ->and(SiteContact::whatsappHref())->toBe('https://wa.me/917760020186')
        ->and(SiteContact::footerColour())->toBe('#2d0b33');                  // purple deepened almost to black

    ThemeSetting::query()->update(['primary_color' => 'oops']);
    Cache::forget('theme_settings');
    expect(SiteContact::footerColour())->toBe('#111827');
});

it('ends the footer tagline with one full stop, never "!."', function () {
    expect(SiteContact::sentence('ಇತ್ತೆ ಪೊದು ನಾಡುನಿ ಮಸ್ತ್ ಸುಲಭ!'))->toBe('ಇತ್ತೆ ಪೊದು ನಾಡುನಿ ಮಸ್ತ್ ಸುಲಭ!')
        ->and(SiteContact::sentence('Find Your Life Partner'))->toBe('Find Your Life Partner.')
        ->and(SiteContact::sentence('Ready?'))->toBe('Ready?')
        ->and(SiteContact::sentence(''))->toBe('');
});

it('shows the call / WhatsApp strip and the sign-up help only when a number is set', function () {
    expect(trim(view('components.partials.contact-strip')->render()))->toBe('')
        ->and(trim(view('components.partials.signup-help')->render()))->toBe('');

    SiteSetting::setValue('phone', '7760020186');
    SiteSetting::setValue('whatsapp', '7760020186');

    expect(view('components.partials.contact-strip')->render())->toContain('href="tel:+917760020186"')->toContain('+91 77600 20186')
        ->toContain('href="https://wa.me/917760020186"')->toContain('href="/faq"')
        ->and(view('components.partials.signup-help')->render())->toContain('+91 77600 20186')->toContain('wa.me/917760020186')->not->toContain('Call Us');
});

it('switches the "not a dating site" note off for dating-style sites', function () {
    expect(SiteContact::showNotDatingNote())->toBeTrue();
    SiteSetting::setValue('show_not_dating_note', '0');
    expect(SiteContact::showNotDatingNote())->toBeFalse();
});

it('wires the headers and footers', function () {
    $app = file_get_contents(resource_path('views/components/layouts/app.blade.php'));
    $auth = file_get_contents(resource_path('views/components/layouts/auth.blade.php'));

    // Register Free filled, Login outline (desktop)
    expect($app)->toContain("<a href=\"/register\" class=\"px-5 py-2 text-sm font-semibold text-white bg-(--color-primary)")
        ->toContain("<a href=\"/login\" class=\"px-4 py-2 text-sm font-semibold text-(--color-primary) border border-(--color-primary)")
        ->toContain("@include('components.partials.contact-strip')")
        ->toContain('SiteContact::sentence($siteTagline)')->not->toContain('{{ $siteTagline }}. ')
        ->toContain("->contains('slug', 'about-us')")
        ->toContain('SiteContact::phoneDisplay()')->toContain("getValue('app_play_store_url', '')")
        ->toContain('is for people who want to marry. It is not a dating site.')
        ->and(substr_count($app, 'SiteContact::footerColour()'))->toBe(2)
        ->and($auth)->toContain('<header')->toContain('Register Free')->toContain('SiteContact::footerColour()')->not->toContain('h-12 mx-auto');

    foreach (['components/layouts/registration', 'components/layouts/onboarding', 'auth/register-step1', 'auth/register-verify', 'auth/register-verify-email', 'auth/register-complete'] as $view) {
        $blade = file_get_contents(resource_path("views/{$view}.blade.php"));
        expect($blade)->toContain("@include('components.partials.signup-help')")->not->toContain('Call Us')->toContain('SiteContact::footerColour()');
    }
});
