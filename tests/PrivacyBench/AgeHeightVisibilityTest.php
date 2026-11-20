<?php

use App\Models\Profile;
use App\Services\ProfileAccessService;
use App\Support\ProfileVisibility;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| Settings -> Who Can See My Profile -> "Show to members older / taller than me" (owner 20 Nov 2026,
| like Chavara's Search Visibility). On MySQL, with real heights like "160 cm - 5 ft 03 inch".
*/

beforeEach(function () {
    Mail::fake();
    Http::fake();
    $this->b = Bench::build();
});

it('shows women "younger / shorter" and men "older / taller", ticked by default, and saves only those two', function () {
    $her = $this->b->m['W'];
    $him = $this->b->m['VB'];
    $page = $this->actingAs($her->user)->get('/settings?section=search_visibility')->assertOk()->getContent();
    expect($page)->toMatch('/name="search_visible_to_younger" value="1"\s*checked/')
        ->toMatch('/name="search_visible_to_shorter" value="1"\s*checked/')
        ->not->toContain('name="search_visible_to_older"')->not->toContain('name="search_visible_to_taller"');
    $hisPage = $this->actingAs($him->user)->get('/settings?section=search_visibility')->assertOk()->getContent();
    expect($hisPage)->toContain('name="search_visible_to_older"')->toContain('name="search_visible_to_taller"')
        ->not->toContain('name="search_visible_to_younger"');

    $this->actingAs($her->user)->post('/settings/visibility', ['show_profile_to' => 'all', 'size_switches' => '1', 'search_visible_to_younger' => '1'])->assertRedirect();
    $her->refresh();
    expect((bool) $her->search_visible_to_younger)->toBeTrue()
        ->and((bool) $her->search_visible_to_shorter)->toBeFalse()
        ->and((bool) $her->search_visible_to_older)->toBeTrue()          // not on her page, so untouched
        ->and((bool) $her->search_visible_to_taller)->toBeTrue();
});

it('on MySQL: she is hidden from a younger, shorter man; he is hidden from an older, taller woman; the others still see them', function () {
    $b = $this->b;
    $her = $b->m['W'];
    $her->update(['date_of_birth' => '1996-06-01', 'height' => '160 cm - 5 ft 03 inch', 'search_visible_to_younger' => false, 'search_visible_to_shorter' => false]);
    $big = $b->m['VB'];
    $big->update(['date_of_birth' => '1990-01-01', 'height' => '180 cm - 5 ft 11 inch']);
    $small = $b->m['C'];
    $small->update(['date_of_birth' => '1998-01-01', 'height' => '158 cm - 5 ft 02 inch', 'search_visible_to_older' => false, 'search_visible_to_taller' => false]);
    $access = app(ProfileAccessService::class);
    $ids = fn (Profile $v) => ProfileVisibility::forMember(Profile::query(), $v->fresh())->pluck('profiles.id')->all();

    expect($ids($small))->not->toContain($her->id)
        ->and($access->canAccess($small->fresh(), $her->fresh()))->toBeFalse()
        ->and($ids($big))->toContain($her->id)                                     // control
        ->and($access->canAccess($big->fresh(), $her->fresh()))->toBeTrue()
        // the small man switched off older / taller: she is both, so she can't see him
        ->and($ids($her))->not->toContain($small->id)
        ->and($ids($her))->toContain($big->id);

    $this->actingAs($small->user)->get('/profile/' . $her->matri_id)->assertDontSee($her->full_name);
    $this->actingAs($big->user)->get('/profile/' . $her->matri_id)->assertOk();
});

it('shows an eye button on the password boxes', function () {
    $her = $this->b->m['W'];
    $page = $this->actingAs($her->user)->get('/settings?section=change_password')->assertOk()->getContent();
    expect(substr_count($page, 'data-password-eye'))->toBe(3);
    auth()->logout();
    $this->app['auth']->forgetGuards();
    expect(substr_count($this->get('/login')->getContent(), 'data-password-eye'))->toBe(1);
});
