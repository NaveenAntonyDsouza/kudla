<?php

use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\PrivacyBench\Bench;

/*
| Admin sign-in (6 Nov 2026, owner): the sign-in page says only "Admin", not the site's
| name; "Remember me" keeps a staff browser signed in for 7 days, not Laravel's 400.
*/

beforeEach(function () {
    Bench::guard();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->staff = User::create(['name' => 'Sign In Staff', 'email' => 'sign-in-staff@bench.test', 'password' => bcrypt('x-signin-pass-3'),
        'role' => 'admin', 'is_active' => true, 'staff_role_id' => DB::table('staff_roles')->where('slug', 'super_admin')->value('id'), 'email_verified_at' => now()]);
});

it('does not name the site on the sign-in page, in the admin top bar or in the browser tab', function () {
    \App\Models\ThemeSetting::query()->update(['site_name' => 'Bench Site Name']);
    $path = '/' . trim((string) config('app.admin_path', 'admin'), '/');

    $page = (string) $this->get($path . '/login')->assertOk()->getContent();
    $inside = (string) $this->actingAs($this->staff, 'admin')->get($path . '/users')->assertOk()->getContent();
    foreach ([$page, $inside] as $html) {
        expect($html)->toContain('Admin')->not->toContain('Bench Site Name Admin');
    }
    expect($inside)->toMatch('#<title>\s*Users\s*-\s*Admin\s*</title>#'); // the browser tab
});

it('remembers a staff browser for 7 days', function () {
    Livewire::test(Login::class)
        ->fillForm(['email' => 'sign-in-staff@bench.test', 'password' => 'x-signin-pass-3', 'remember' => true])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $cookie = collect(Cookie::getQueuedCookies())->first(fn ($c) => str_starts_with($c->getName(), 'remember_admin_'));
    expect($cookie)->not->toBeNull()
        ->and(abs($cookie->getExpiresTime() - now()->addDays(7)->timestamp))->toBeLessThan(120);
});

it('keeps a remembered staff member signed in after the 2-hour session has gone', function () {
    Livewire::test(Login::class)
        ->fillForm(['email' => 'sign-in-staff@bench.test', 'password' => 'x-signin-pass-3', 'remember' => true])
        ->call('authenticate');
    $cookie = collect(Cookie::getQueuedCookies())->first(fn ($c) => str_starts_with($c->getName(), 'remember_admin_'));
    expect($cookie)->not->toBeNull();

    // a new visit after the session expired: no session, only the remember cookie
    auth('admin')->logoutCurrentDevice();
    app('auth')->forgetGuards();
    $this->flushSession();
    $res = $this->withCookie($cookie->getName(), $cookie->getValue())
        ->get('/' . trim((string) config('app.admin_path', 'admin'), '/'));

    fwrite(STDERR, "\n  status " . $res->getStatusCode() . ' ' . ($res->headers->get('Location') ?? '') . "\n");
    expect($res->getStatusCode())->toBe(200);
});

it('signs out only the browser where Sign out is pressed', function () {
    $path = '/' . trim((string) config('app.admin_path', 'admin'), '/');
    $remember = function (): \Symfony\Component\HttpFoundation\Cookie {
        Livewire::test(Login::class)
            ->fillForm(['email' => 'sign-in-staff@bench.test', 'password' => 'x-signin-pass-3', 'remember' => true])
            ->call('authenticate');
        $cookie = collect(Cookie::getQueuedCookies())->first(fn ($c) => str_starts_with($c->getName(), 'remember_admin_'));
        Cookie::flushQueuedCookies();
        auth('admin')->logoutCurrentDevice();
        app('auth')->forgetGuards();

        return $cookie;
    };
    $laptop = $remember();
    $phone = $remember();

    // Sign out on the phone
    $this->withCookie($phone->getName(), $phone->getValue())->get($path)->assertOk();
    $this->post(route('filament.admin.auth.logout'))->assertRedirect();
    app('auth')->forgetGuards();
    $this->flushSession();

    // the laptop, its session long gone, is still let in by its remember cookie
    $this->withCookie($laptop->getName(), $laptop->getValue())->get($path)->assertOk();
    expect($this->staff->fresh()->remember_token)->not->toBeNull();
});
