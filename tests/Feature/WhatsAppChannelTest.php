<?php

use App\Models\SiteSetting;
use App\Support\WhatsApp;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
| WhatsApp channel (Oct 2026, Kudla first): "Follow us on WhatsApp" in the
| footer, sign-up complete page, dashboard, /whatsapp and every email, only
| on sites that set a channel link in Site Settings.
*/

beforeEach(function () {
    Schema::dropIfExists('site_settings');
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Cache::flush();
});

afterEach(function () {
    Schema::dropIfExists('site_settings');
});

it('only accepts a real WhatsApp channel link', function () {
    expect(WhatsApp::channelUrl())->toBeNull();

    SiteSetting::setValue('whatsapp_channel_url', 'https://whatsapp.com/channel/0029VbDdgmeC6ZvrgX3l2T36');
    expect(WhatsApp::channelUrl())->toBe('https://whatsapp.com/channel/0029VbDdgmeC6ZvrgX3l2T36');

    foreach (['http://whatsapp.com/channel/abc', 'https://evil.example/channel/abc', 'https://chat.whatsapp.com/abc', 'javascript:alert(1)'] as $bad) {
        SiteSetting::setValue('whatsapp_channel_url', $bad);
        expect(WhatsApp::channelUrl())->toBeNull();
    }
});

it('has no /whatsapp page on a site without a channel', function () {
    $this->get('/whatsapp')->assertNotFound();
});

it('puts the channel in the email footer only when set', function () {
    $render = fn () => view('emails.database-template', ['body' => '<p>Hi</p>', 'siteName' => 'Kudla Matrimony'])->render();

    expect($render())->not->toContain('on WhatsApp');

    SiteSetting::setValue('whatsapp_channel_url', 'https://whatsapp.com/channel/0029VbDdgmeC6ZvrgX3l2T36');

    expect($render())->toContain('href="https://whatsapp.com/channel/0029VbDdgmeC6ZvrgX3l2T36"')
        ->toContain('Follow Kudla Matrimony on WhatsApp');
});

it('shows the follow card on the sign-up complete page, the dashboard and the footer', function () {
    expect(file_get_contents(resource_path('views/auth/register-complete.blade.php')))->toContain('<x-whatsapp-channel')
        ->and(file_get_contents(resource_path('views/dashboard/index.blade.php')))->toContain('<x-whatsapp-channel compact />')
        ->and(file_get_contents(resource_path('views/components/layouts/app.blade.php')))->toContain("route('whatsapp')");
});

it('no longer sends people who need help to a placeholder phone number', function () {
    foreach (['auth/register-complete', 'auth/register-verify-email', 'auth/register-verify', 'components/layouts/onboarding', 'components/layouts/registration'] as $view) {
        expect(file_get_contents(resource_path("views/{$view}.blade.php")))
            ->not->toContain('1800123456')
            ->toContain("@include('components.partials.signup-help')");
    }
    // the shared partial reads the real site phone (Oct 2026 header review)
    expect(file_get_contents(resource_path('views/components/partials/signup-help.blade.php')))->toContain('SiteContact::phoneDisplay()')->not->toContain('1800123456')
        ->and(file_get_contents(app_path('Support/SiteContact.php')))->toContain("SiteSetting::getValue('phone', '')");
});
