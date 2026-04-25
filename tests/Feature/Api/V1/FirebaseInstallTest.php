<?php

use Kreait\Laravel\Firebase\Facades\Firebase;

/*
|--------------------------------------------------------------------------
| Firebase install smoke test (kreait/laravel-firebase ^7.1)
|--------------------------------------------------------------------------
| Step 6 only installs the package — actual push dispatch lands in step 7.
| These tests catch the failure modes the install introduces:
|
|   1. Service provider boots without errors (no fatal at app boot).
|   2. The Firebase facade is registered (catches autoload / discovery
|      regression).
|   3. The published config exists at config/firebase.php (catches a
|      missed vendor:publish on a fresh clone).
|   4. config('firebase.projects.app.credentials.file') resolves — even
|      with FIREBASE_CREDENTIALS unset, kreait shouldn't blow up at boot;
|      it lazy-loads when Firebase::messaging() is called.
|
| We deliberately do NOT call Firebase::messaging() here — that would
| try to read a real credentials file which the buyer hasn't uploaded
| yet. Push-dispatch behaviour (with credentials present + with them
| missing) is covered by step 7's tests.
|
| Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-06-fcm-install.md
*/

it('firebase service provider boots cleanly without credentials', function () {
    // ServiceProvider in the loaded list ⇒ register() + boot() ran without
    // throwing. Any Laravel-13 incompatibility would surface here as a
    // missing entry / fatal at app startup.
    expect(app()->getLoadedProviders())
        ->toHaveKey(\Kreait\Laravel\Firebase\ServiceProvider::class);
});

it('Firebase facade class is autoloadable', function () {
    expect(class_exists(Firebase::class))->toBeTrue();
});

it('config/firebase.php is published and readable', function () {
    expect(config()->has('firebase'))->toBeTrue();
    expect(config('firebase.default'))->not->toBeEmpty();
});

it('default firebase project config has credentials key wired (even if unset)', function () {
    // The published config exposes firebase.projects.{default}.credentials,
    // which resolves to env('FIREBASE_CREDENTIALS') — null when the buyer
    // hasn't placed the JSON yet. We assert the KEY is present in the
    // array (wiring intact), not that it has a value.
    $defaultProject = config('firebase.default');

    expect(config("firebase.projects.{$defaultProject}"))
        ->toBeArray()
        ->toHaveKey('credentials');
});
