<?php

use App\Models\SiteSetting;
use App\Support\WhatsApp;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| WhatsApp: working links + the floating chat button
|--------------------------------------------------------------------------
| kudla saved its number as 10 digits, so every wa.me link opened an error
| (wa.me needs the country code). Placeholder numbers must show nothing.
*/

beforeEach(function () {
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
});

afterEach(fn () => Schema::dropIfExists('site_settings'));

it('turns the saved number into one wa.me can open', function (?string $saved, ?string $digits) {
    expect(WhatsApp::digits($saved))->toBe($digits);
})->with([
    'indian 10 digits' => ['77600 20186', '917760020186'],
    'with +91' => ['+91 77600 20186', '917760020186'],
    'leading zero' => ['077600 20186', '917760020186'],
    'us number' => ['+1 (415) 555-0123', '14155550123'],
    'placeholder' => ['+91 00000 00000', null],
    'empty' => ['', null],
    'too short' => ['12345', null],
]);

it('shows the button only where switched on and a real number is set', function () {
    $render = fn () => Blade::render('<x-whatsapp-button />');

    SiteSetting::setValue('whatsapp', '77600 20186');
    expect($render())->not->toContain('wa-fab');

    SiteSetting::setValue('whatsapp_button_enabled', '1');
    expect($render())->toContain('wa-fab')->toContain('https://wa.me/917760020186?text=');

    SiteSetting::setValue('whatsapp', '+91 00000 00000');
    expect($render())->not->toContain('wa-fab');
});

it('opens the chat saying what the visitor is looking at', function (string $routeName, array $params, string $expected) {
    SiteSetting::setValue('site_name', 'Kudla Matrimony');
    $request = Request::create('/x');
    $route = (new Route('GET', '/x', []))->name($routeName);
    $route->bind($request);
    foreach ($params as $key => $value) {
        $route->setParameter($key, $value);
    }
    $request->setRouteResolver(fn () => $route);

    expect(WhatsApp::prefilledMessage($request))->toBe($expected);
})->with([
    'registration' => ['register.step3', [], 'Hi Kudla Matrimony, I need help with registration.'],
    'step one' => ['register', [], 'Hi Kudla Matrimony, I need help with registration.'],
    'plans' => ['membership.index', [], 'Hi Kudla Matrimony, I have a question about membership plans.'],
    'a profile' => ['profile.view', ['profile' => 'km100500'], 'Hi Kudla Matrimony, I have a question.'],
    'anything else' => ['home', [], 'Hi Kudla Matrimony, I have a question.'],
]);

it('is on every page layout', function () {
    foreach (['app', 'auth', 'onboarding', 'public', 'registration'] as $layout) {
        expect(file_get_contents(resource_path("views/components/layouts/{$layout}.blade.php")))->toContain('<x-whatsapp-button />');
    }
    expect(file_get_contents(resource_path('views/auth/register-step1.blade.php')))->toContain('<x-whatsapp-button />');
});

it('hides the button from logged-in members except while signing up or finishing their profile', function (?string $routeName, bool $loggedIn, bool $shown) {
    if ($loggedIn) {
        $user = new \App\Models\User();
        $user->id = 7;
        auth()->setUser($user);
    }
    $request = Request::create('/x');
    if ($routeName) {
        $route = (new Route('GET', '/x', []))->name($routeName);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
    }

    expect(WhatsApp::buttonShownOn($request))->toBe($shown);
})->with([
    'visitor, home' => ['home', false, true],
    'visitor, a profile' => ['profile.view', false, true],
    'member, a profile' => ['profile.view', true, false],
    'member, dashboard' => ['dashboard', true, false],
    'member, registration step' => ['register.step3', true, true],
    'member, onboarding' => ['onboarding.photo', true, true],
]);
