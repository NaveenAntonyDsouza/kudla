<?php

use App\Models\Profile;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\ProfileAccessService;
use App\Support\PhoneNumber;
use App\Support\ProfileVisibility;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| Three settings for a worldwide, app-only site (24 Nov 2026, the owner's Catholic dating app):
| phone numbers from any country, new members who see no one until staff approve them after the
| verification call, and "app only" (the website is one download page plus the information pages).
| All three are off by default, so every other site behaves exactly as before.
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    Cache::flush();
    foreach ([PhoneNumber::SETTING, ProfileVisibility::APPROVED_ONLY_SETTING, \App\Http\Middleware\AppOnlyMode::SETTING] as $key) {
        SiteSetting::setValue($key, '0');
    }
    $this->signUp = fn (array $extra) => [
        'full_name' => 'Phone Tester', 'gender' => 'female', 'date_of_birth' => '1995-02-02',
        'email' => 'phone-'.uniqid().'@bench.test', 'password' => 'phone-pass-9', 'accept_terms' => '1',
    ] + $extra;
});

afterEach(function () {
    foreach ([PhoneNumber::SETTING, ProfileVisibility::APPROVED_ONLY_SETTING, \App\Http\Middleware\AppOnlyMode::SETTING] as $key) {
        SiteSetting::setValue($key, '0');
    }
});

it('turns a number and its country code into the international form', function () {
    expect(PhoneNumber::international164('+44', '07700 900123'))->toBe('+447700900123')
        ->and(PhoneNumber::international164('+1', '(555) 201-3344'))->toBe('+15552013344')
        ->and(PhoneNumber::international164('+44', '+61 412 345 678'))->toBe('+61412345678')    // typed with its own code
        ->and(PhoneNumber::international164(null, '0091 98765 43210'))->toBe('+919876543210')
        ->and(PhoneNumber::international164('+91', '123'))->toBeNull()
        ->and(PhoneNumber::international164('+91', ''))->toBeNull()
        ->and(PhoneNumber::international164('+44', '1234567890123456'))->toBeNull();             // longer than the format allows
});

it('keeps 10-digit Indian numbers when the setting is off, and takes any country when it is on', function () {
    $this->post('/register', ($this->signUp)(['phone' => '447700900123', 'phone_code' => '+44']))->assertSessionHasErrors('phone');

    SiteSetting::setValue(PhoneNumber::SETTING, '1');
    $this->post('/register', ($this->signUp)(['phone' => '07700 900124', 'phone_code' => '+44']))->assertSessionHasNoErrors();
    expect(User::where('phone', '+447700900124')->exists())->toBeTrue();
    auth()->logout();

    // the same number again is refused, however it is typed
    $this->post('/register', ($this->signUp)(['phone' => '+44 7700 900124']))->assertSessionHasErrors('phone');

    // the app API: phone_code + the national number
    $this->postJson('/api/v1/auth/register/step-1', ($this->signUp)(['phone' => '0412 345 678', 'phone_code' => '+61']))->assertSuccessful();
    expect(User::where('phone', '+61412345678')->exists())->toBeTrue();
    $this->postJson('/api/v1/auth/register/step-1', ($this->signUp)(['phone' => '9123 4567', 'phone_code' => '+65']))->assertSuccessful();

    // they sign in with their number however they type it (with or without the country code)
    foreach (['07700 900124', '+44 7700 900124', '0044 7700 900124', '0412 345 678', '+61 412 345 678', '9123 4567'] as $typed) {
        $this->postJson('/api/v1/auth/login/password', ['login' => $typed, 'password' => 'phone-pass-9'])->assertOk();
    }
    $this->postJson('/api/v1/auth/login/password', ['login' => '+61 412 345 679', 'password' => 'phone-pass-9'])->assertStatus(401);
    Cache::flush();
    $this->getJson('/api/v1/site/settings')->assertJsonPath('data.features.international_phones', true);
});

it('shows a member nothing until approval, only where the setting is on', function () {
    $b = Bench::build();
    $viewer = $b->m['V'];
    $target = $b->m['T'];
    $viewer->forceFill(['is_approved' => false])->save();
    $access = app(ProfileAccessService::class);

    // off: waiting members can still look around, as today
    expect(ProfileVisibility::forMember(Profile::query(), $viewer->fresh())->count())->toBeGreaterThan(0)
        ->and($access->check($viewer->fresh(), $target))->not->toBe(ProfileAccessService::REASON_VIEWER_PENDING);

    SiteSetting::setValue(ProfileVisibility::APPROVED_ONLY_SETTING, '1');
    expect(ProfileVisibility::forMember(Profile::query(), $viewer->fresh())->count())->toBe(0)
        ->and($access->check($viewer->fresh(), $target))->toBe(ProfileAccessService::REASON_VIEWER_PENDING)
        ->and($access->check($viewer->fresh(), $viewer->fresh()))->toBe(ProfileAccessService::REASON_SELF);   // own profile still fine

    $token = $viewer->user->createToken('test')->plainTextToken;
    $this->withToken($token)->getJson('/api/v1/profiles/'.$target->matri_id)
        ->assertStatus(403)->assertJsonPath('error.code', 'PENDING_APPROVAL');
    expect(fn () => app(\App\Services\InterestService::class)->send($viewer->fresh(), $target, 'msg_suitable', null))->toThrow(\RuntimeException::class);
    // the app learns it on sign-in and app start, so it can show a waiting screen
    $this->withToken($token)->getJson('/api/v1/auth/me')->assertJsonPath('data.profile.is_approved', false);

    // approved members are not affected, and they are told at once (in the app and by push)
    $viewer->forceFill(['is_approved' => true])->save();
    expect(ProfileVisibility::forMember(Profile::query(), $viewer->fresh())->count())->toBeGreaterThan(0);
    $this->app['auth']->forgetGuards();
    $this->withToken($token)->getJson('/api/v1/auth/me')->assertJsonPath('data.profile.is_approved', true);
    $notice = \App\Models\Notification::where('user_id', $viewer->user_id)->where('type', 'profile_approved')->get();
    expect($notice)->toHaveCount(1);
    $listed = collect($this->withToken($token)->getJson('/api/v1/notifications?per_page=50')->json('data'))->firstWhere('type', 'profile_approved');
    expect($listed['target']['screen'] ?? null)->toBe('home');

    // where the setting is off, approval stays email-only, as before
    SiteSetting::setValue(ProfileVisibility::APPROVED_ONLY_SETTING, '0');
    $target->forceFill(['is_approved' => false])->save();
    $target->forceFill(['is_approved' => true])->save();
    expect(\App\Models\Notification::where('user_id', $target->user_id)->where('type', 'profile_approved')->count())->toBe(0);
});

it('makes the website one download page plus the information pages, only where the setting is on', function () {
    $this->get('/login')->assertOk();                                  // off: the usual website

    SiteSetting::setValue(\App\Http\Middleware\AppOnlyMode::SETTING, '1');
    SiteSetting::setValue('app_play_store_url', 'https://play.google.com/store/apps/details?id=com.example.app');
    $home = $this->get('/')->assertOk()->getContent();
    expect($home)->toContain('Google Play')->toContain('/privacy-policy')->not->toContain('/register');

    foreach (['/login', '/register', '/membership-plans'] as $page) {
        $this->get($page)->assertRedirect('/');
    }
    // member pages first ask to sign in, and the sign-in page then sends them to the download page
    foreach (['/search', '/dashboard'] as $page) {
        expect($this->followingRedirects()->get($page)->getContent())->toContain('Google Play');
    }
    foreach (['/privacy-policy', '/terms-condition', '/contact-us', '/forgot-password'] as $page) {
        $this->get($page)->assertOk();
    }
    // Play asks dating apps for a child-safety page: open too (404 here only if the page is not written yet)
    foreach (['/child-safety', '/refund-policy'] as $page) {
        expect($this->get($page)->getStatusCode())->not->toBe(302);
    }
    expect($home)->toContain('/child-safety');
    $this->getJson('/api/v1/site/settings')->assertOk();               // the app keeps working
    $this->post('/register', [])->assertNotFound();                    // no web sign-up
});

it('shows signed-out visitors no members where members wait for approval', function () {
    Bench::build();
    expect(ProfileVisibility::forVisitors(Profile::query())->count())->toBeGreaterThan(0);   // off: as before

    SiteSetting::setValue(ProfileVisibility::APPROVED_ONLY_SETTING, '1');
    expect(ProfileVisibility::forVisitors(Profile::query())->count())->toBe(0);
    // the app API's public browse lists (no token) are empty too
    $this->app['auth']->forgetGuards();
    $first = collect($this->getJson('/api/v1/discover/nri-matrimony')->json('data.subcategories') ?? $this->getJson('/api/v1/discover/nri-matrimony')->json('data'))->first();
    if (is_array($first) && isset($first['slug'])) {
        $this->getJson('/api/v1/discover/nri-matrimony/'.$first['slug'])->assertOk()->assertJsonCount(0, 'data');
    }
});
