<?php

use App\Http\Controllers\SearchController;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Analytics;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| PostHog: who did what (kudla, owner's choice 2026-10-05)
|--------------------------------------------------------------------------
| Signed-in members are identified by Matri ID only (no name, phone,
| email, religion or caste) so the weekly review can follow a member's
| searches, views and interests; logging out resets it. Searches record
| which filters were used, never the values.
*/

beforeEach(function () {
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Schema::create('user_memberships', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->boolean('is_active')->default(true);
        $t->timestamp('starts_at')->nullable();
        $t->timestamp('ends_at')->nullable();
        $t->timestamps();
    });
    SiteSetting::setValue('posthog_api_key', 'phc_test');
});

afterEach(function () {
    Schema::dropIfExists('user_memberships');
    Schema::dropIfExists('site_settings');
});

function phMember(): User
{
    $user = new User(['name' => 'Asha Rao', 'email' => 'asha@example.com', 'phone' => '9876543210']);
    $user->id = 41;
    $user->created_at = now()->setDate(2026, 8, 3);
    $user->setRelation('profile', new Profile(['matri_id' => 'KM100041', 'gender' => 'female', 'onboarding_completed' => true]));

    return $user;
}

it('identifies a signed-in member by Matri ID only', function () {
    Auth::setUser(phMember());

    $html = Blade::render("@include('components.partials.tracking-head')");

    expect($html)->toContain("posthog.identify('KM100041'")
        ->toMatch('/member_type.{1,20}free/')
        ->toMatch('/joined.{1,20}2026-08/')
        ->not->toContain('Asha')->not->toContain('asha@example.com')->not->toContain('9876543210');
});

it('resets PostHog on a signed-out page after a member used this device', function () {
    $html = Blade::render("@include('components.partials.tracking-head')");

    expect($html)->not->toContain('posthog.identify')->toContain('posthog.reset()');
});

it('never identifies staff', function () {
    $staff = phMember();
    $staff->role = 'admin';
    Auth::setUser($staff);

    expect(Blade::render("@include('components.partials.tracking-head')"))->not->toContain('posthog.identify');
});

it('records which search filters were used, not their values', function () {
    $request = Request::create('/search', 'GET', ['search' => 1, 'tab' => 'partner', 'caste' => ['Billava'], 'age_from' => '24', 'religion' => 'Any', 'keyword' => 'secret']);
    $request->setLaravelSession(app('session.store'));
    app()->instance('request', $request);

    $track = (new ReflectionMethod(SearchController::class, 'trackSearch'))->getClosure(new SearchController);
    $track('filters', $request, new LengthAwarePaginator([], 37, 20, 1));
    $track('filters', $request, new LengthAwarePaginator([], 37, 20, 2)); // paging on: not again

    $events = Analytics::pull();
    expect($events)->toHaveCount(1)
        ->and($events[0]['name'])->toBe('search_performed')
        ->and($events[0]['params'])->toBe(['search_type' => 'filters', 'tab' => 'partner', 'filters' => 'age_from,caste', 'filter_count' => 2, 'results' => 37, 'sort' => 'relevance'])
        ->and(json_encode($events))->not->toContain('Billava')->not->toContain('secret');
});
