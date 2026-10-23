<?php

use App\Filament\Pages\ThemeBranding;
use App\Models\SiteSetting;
use App\Models\ThemeSetting;
use App\Models\User;
use App\Support\HeaderLogo;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

/*
| 23 Oct 2026: kudlamatrimony.com moved to a stacked logo (Kudla over
| Matrimony.com), which looked small at the fixed 40 px header height. Each
| site now sets its header logo height in Theme & Branding (default 40 px, so
| the other sites look the same as before).
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
    ThemeSetting::create([
        'primary_color' => '#B71C1C', 'primary_hover' => '#8E1515', 'primary_light' => '#FFEBEE',
        'secondary_color' => '#F57C00', 'secondary_hover' => '#E65100', 'secondary_light' => '#FFF3E0',
        'heading_font' => 'Playfair Display', 'body_font' => 'Inter',
        'logo_url' => '/storage/branding/kudla-logo-km-wordmark.png',
    ]);
    Cache::flush();
});

afterEach(function () {
    foreach (['site_settings', 'theme_settings', 'static_pages', 'advertisements', 'blog_posts'] as $table) {
        Schema::dropIfExists($table);
    }
});

it('is 40 px unless the site sets another height, kept between 24 and 60', function () {
    expect(HeaderLogo::height())->toBe(40);

    SiteSetting::setValue(HeaderLogo::SETTING, '48');
    expect(HeaderLogo::height())->toBe(48);

    expect(HeaderLogo::clamp('200'))->toBe(60)
        ->and(HeaderLogo::clamp(10))->toBe(24)
        ->and(HeaderLogo::clamp('47.6'))->toBe(48)
        ->and(HeaderLogo::clamp('abc'))->toBe(40)
        ->and(HeaderLogo::clamp(null))->toBe(40);
});

it('draws the header logo at the site height on a page that uses the main layout', function () {
    foreach (['2026_04_16_180000_create_static_pages_table', '2026_04_16_190000_create_advertisements_table', '2026_10_15_000001_create_blog_posts_table'] as $migration) {
        $m = require database_path("migrations/{$migration}.php");
        $m->down();
        $m->up();
    }
    $logo = '<img src="/storage/branding/kudla-logo-km-wordmark.png" alt="';

    $html = $this->withoutVite()->get('/faq')->assertOk()->getContent();
    expect($html)->toContain($logo)
        ->toContain('style="height: 40px; width: auto"')
        ->not->toContain('kudla-logo-km-wordmark.png" alt="' . config('app.name') . '" class="h-10"');

    SiteSetting::setValue(HeaderLogo::SETTING, '48');
    $html = $this->withoutVite()->get('/faq')->assertOk()->getContent();
    expect($html)->toContain('style="height: 48px; width: auto"')
        ->not->toContain('style="height: 40px; width: auto"');
});

it('uses the setting in every header, not a fixed class', function () {
    $headers = ['components/layouts/app', 'components/layouts/onboarding', 'components/layouts/public', 'components/layouts/registration',
        'auth/register-step1', 'auth/register-verify', 'auth/register-verify-email', 'auth/register-complete'];
    foreach ($headers as $view) {
        expect(file_get_contents(resource_path("views/{$view}.blade.php")))
            ->toContain('style="height: {{ \App\Support\HeaderLogo::height() }}px; width: auto"');
    }

    // the sign-up pages draw their own header: no view may keep a fixed-height header logo
    $fixed = collect(\Illuminate\Support\Facades\File::allFiles(resource_path('views')))
        ->filter(fn ($f) => preg_match('/\$theme->logo_url \}\}" alt="\{\{ \$siteName \}\}" class="h-10"/', $f->getContents()))
        ->map(fn ($f) => $f->getRelativePathname())->values()->all();
    expect($fixed)->toBe([]);
});

it('saves the height from Theme & Branding and keeps it when colours are reset', function () {
    Schema::dropIfExists('users');
    Schema::create('users', function (Blueprint $t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('password')->nullable();
        $t->string('role')->nullable();
        $t->unsignedBigInteger('staff_role_id')->nullable();
        $t->unsignedBigInteger('branch_id')->nullable();
        $t->rememberToken();
        $t->timestamps();
    });
    DB::table('users')->insert(['id' => 1, 'name' => 'Admin', 'role' => 'admin', 'created_at' => now(), 'updated_at' => now()]);
    $this->actingAs(User::find(1));
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(ThemeBranding::class)
        ->assertSet('data.header_logo_height', 40)
        ->set('data.header_logo_height', 48)
        ->call('save')
        ->assertHasNoErrors();
    expect(HeaderLogo::height())->toBe(48);

    Livewire::test(ThemeBranding::class)
        ->assertSet('data.header_logo_height', 48)
        ->call('resetToDefaults')
        ->assertSet('data.header_logo_height', 48)
        ->assertSet('data.heading_font', 'Playfair Display')
        ->assertSet('data.primary_color', '#8B1D91')
        ->call('save')
        ->assertHasNoErrors();
    expect(HeaderLogo::height())->toBe(48);

    Livewire::test(ThemeBranding::class)
        ->set('data.header_logo_height', 90)
        ->call('save')
        ->assertHasErrors(['data.header_logo_height']);
    expect(HeaderLogo::height())->toBe(48);

    Schema::dropIfExists('users');
});
