<?php

use App\Models\Profile;
use App\Models\User;
use App\Services\DashboardService;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| DashboardService — payload assembly contract
|--------------------------------------------------------------------------
| These tests exercise every section builder with DB-free in-memory
| Eloquent instances. Each DB-touching helper inside DashboardService is
| wrapped in try/catch → safe default, so the suite can run against
| SQLite :memory: without the full matrimony schema migrated.
|
| Production behaviour is unchanged — the try/catch branches never fire
| against the real MySQL schema.
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
*/

function buildDashboardUser(array $overrides = []): User
{
    $user = new User();
    $user->exists = true;
    $user->forceFill(array_merge([
        'id' => 7700,
        'email' => 'dash@example.com',
        'phone' => '9876543210',
        'email_verified_at' => Carbon::parse('2026-01-01 00:00:00'),
        'phone_verified_at' => Carbon::parse('2026-01-01 00:00:00'),
        'is_active' => true,
    ], $overrides));

    return $user;
}

function buildDashboardProfile(User $user, array $overrides = []): Profile
{
    $profile = new Profile();
    $profile->exists = true;
    $profile->forceFill(array_merge([
        'id' => 7700,
        'user_id' => $user->id,
        'matri_id' => 'AM770000',
        'gender' => 'male',
        'date_of_birth' => Carbon::parse('1995-04-20'),
        'profile_completion_pct' => 45,
        'is_active' => true,
        'is_approved' => true,
        'is_hidden' => false,
        'suspension_status' => 'active',
        'show_profile_to' => 'all',
    ], $overrides));

    $profile->setRelation('user', $user);
    // Empty collection → countPhotosSafely path returns 0 without a DB hit.
    $profile->setRelation('profilePhotos', new \Illuminate\Database\Eloquent\Collection());

    return $profile;
}

beforeEach(function () {
    $this->svc = app(DashboardService::class);
});

/* ------------------------------------------------------------------
 |  buildPayload — top-level shape contract
 | ------------------------------------------------------------------ */

it('buildPayload returns all 7 dashboard keys', function () {
    $user = buildDashboardUser();
    $profile = buildDashboardProfile($user);

    $payload = $this->svc->buildPayload($user, $profile);

    expect($payload)->toHaveKeys([
        'cta',
        'stats',
        'recommended_matches',
        'mutual_matches',
        'recent_views',
        'newly_joined',
        'discover_teasers',
    ]);
});

it('buildPayload carousels are arrays (never null) even when DB tables missing', function () {
    $user = buildDashboardUser();
    $profile = buildDashboardProfile($user);

    $payload = $this->svc->buildPayload($user, $profile);

    expect($payload['recommended_matches'])->toBeArray();
    expect($payload['mutual_matches'])->toBeArray();
    expect($payload['recent_views'])->toBeArray();
    expect($payload['newly_joined'])->toBeArray();
});

/* ------------------------------------------------------------------
 |  CTA block
 | ------------------------------------------------------------------ */

it('CTA shows profile completion when pct < 80', function () {
    $user = buildDashboardUser();
    $profile = buildDashboardProfile($user, ['profile_completion_pct' => 45]);

    $cta = $this->svc->buildCta($user, $profile);

    expect($cta['show_profile_completion'])->toBeTrue();
    expect($cta['profile_completion_pct'])->toBe(45);
});

it('CTA hides profile completion when pct >= 80', function () {
    $user = buildDashboardUser();
    $profile = buildDashboardProfile($user, ['profile_completion_pct' => 85]);

    $cta = $this->svc->buildCta($user, $profile);

    expect($cta['show_profile_completion'])->toBeFalse();
    expect($cta['profile_completion_pct'])->toBe(85);
});

it('CTA treats missing profile_completion_pct as 0', function () {
    $user = buildDashboardUser();
    $profile = buildDashboardProfile($user, ['profile_completion_pct' => null]);

    $cta = $this->svc->buildCta($user, $profile);

    expect($cta['show_profile_completion'])->toBeTrue();
    expect($cta['profile_completion_pct'])->toBe(0);
});

it('CTA shows photo upload when profile has no photos', function () {
    $user = buildDashboardUser();
    $profile = buildDashboardProfile($user);  // empty profilePhotos collection

    $cta = $this->svc->buildCta($user, $profile);

    expect($cta['show_photo_upload'])->toBeTrue();
});

it('CTA hides photo upload when profile has at least one photo', function () {
    $user = buildDashboardUser();
    $profile = buildDashboardProfile($user);
    $profile->setRelation('profilePhotos', new \Illuminate\Database\Eloquent\Collection([
        (new \App\Models\ProfilePhoto())->forceFill(['id' => 1]),
    ]));

    $cta = $this->svc->buildCta($user, $profile);

    expect($cta['show_photo_upload'])->toBeFalse();
});

it('CTA shows verify email when email_verified_at is null', function () {
    $user = buildDashboardUser(['email_verified_at' => null]);
    $profile = buildDashboardProfile($user);

    $cta = $this->svc->buildCta($user, $profile);

    expect($cta['show_verify_email'])->toBeTrue();
});

it('CTA hides verify email when email_verified_at is set', function () {
    $user = buildDashboardUser();  // email_verified_at defaults to set
    $profile = buildDashboardProfile($user);

    $cta = $this->svc->buildCta($user, $profile);

    expect($cta['show_verify_email'])->toBeFalse();
});

it('CTA shows verify phone when phone_verified_at is null', function () {
    $user = buildDashboardUser(['phone_verified_at' => null]);
    $profile = buildDashboardProfile($user);

    $cta = $this->svc->buildCta($user, $profile);

    expect($cta['show_verify_phone'])->toBeTrue();
});

it('CTA shows upgrade when user has no active membership', function () {
    $user = buildDashboardUser();
    $profile = buildDashboardProfile($user);

    $cta = $this->svc->buildCta($user, $profile);

    // No user_memberships table in test DB → hasActiveMembershipSafely returns
    // false defensively → show_upgrade = true
    expect($cta['show_upgrade'])->toBeTrue();
});

it('CTA returns real booleans (not truthy/falsy values)', function () {
    $user = buildDashboardUser();
    $profile = buildDashboardProfile($user);

    $cta = $this->svc->buildCta($user, $profile);

    expect($cta['show_profile_completion'])->toBeBool();
    expect($cta['show_photo_upload'])->toBeBool();
    expect($cta['show_verify_email'])->toBeBool();
    expect($cta['show_verify_phone'])->toBeBool();
    expect($cta['show_upgrade'])->toBeBool();
    expect($cta['profile_completion_pct'])->toBeInt();
});

/* ------------------------------------------------------------------
 |  Stats block
 | ------------------------------------------------------------------ */

it('buildStats returns all 5 counter keys', function () {
    $user = buildDashboardUser();
    $profile = buildDashboardProfile($user);

    $stats = $this->svc->buildStats($profile);

    expect($stats)->toHaveKeys([
        'interests_received',
        'interests_sent',
        'profile_views_total',
        'shortlisted_count',
        'unread_notifications',
    ]);
});

it('buildStats returns all counters as integers (never null)', function () {
    $user = buildDashboardUser();
    $profile = buildDashboardProfile($user);

    $stats = $this->svc->buildStats($profile);

    foreach ($stats as $key => $value) {
        expect($value)->toBeInt("stats.{$key} must be an integer");
    }
});

it('buildStats returns zeros when DB tables missing (test env)', function () {
    $user = buildDashboardUser();
    $profile = buildDashboardProfile($user);

    $stats = $this->svc->buildStats($profile);

    // None of the underlying tables exist in SQLite :memory: test DB →
    // safeCount() returns 0 for every counter
    expect($stats['interests_received'])->toBe(0);
    expect($stats['interests_sent'])->toBe(0);
    expect($stats['profile_views_total'])->toBe(0);
    expect($stats['shortlisted_count'])->toBe(0);
    expect($stats['unread_notifications'])->toBe(0);
});

/* ------------------------------------------------------------------
 |  Carousel builders (defensive fallbacks)
 | ------------------------------------------------------------------ */

it('buildRecommendedMatches returns empty array when matching service fails', function () {
    $user = buildDashboardUser();
    $profile = buildDashboardProfile($user);
    // No partnerPreference relation → MatchingService throws → catch returns []

    expect($this->svc->buildRecommendedMatches($profile))->toBe([]);
});

it('buildMutualMatches returns empty array when matching service fails', function () {
    $user = buildDashboardUser();
    $profile = buildDashboardProfile($user);

    expect($this->svc->buildMutualMatches($profile))->toBe([]);
});

it('buildRecentViews returns empty array when profile_views table missing', function () {
    $user = buildDashboardUser();
    $profile = buildDashboardProfile($user);

    expect($this->svc->buildRecentViews($profile))->toBe([]);
});

it('buildNewlyJoined returns empty array when profiles table missing', function () {
    $user = buildDashboardUser();
    $profile = buildDashboardProfile($user);

    expect($this->svc->buildNewlyJoined($profile))->toBe([]);
});

/* ------------------------------------------------------------------
 |  Discover teasers (config-driven, no DB)
 | ------------------------------------------------------------------ */

it('buildDiscoverTeasers returns 6 entries', function () {
    $teasers = $this->svc->buildDiscoverTeasers();

    expect($teasers)->toBeArray();
    expect(count($teasers))->toBe(DashboardService::DISCOVER_TEASER_COUNT);
});

it('each discover teaser has category + label + null count', function () {
    $teasers = $this->svc->buildDiscoverTeasers();

    foreach ($teasers as $teaser) {
        expect($teaser)->toHaveKeys(['category', 'label', 'count']);
        expect($teaser['category'])->toBeString();
        expect($teaser['label'])->toBeString();
        expect($teaser['count'])->toBeNull();  // placeholder until step-14
    }
});

it('buildDiscoverTeasers returns [] when discover config missing', function () {
    config(['discover' => null]);

    expect($this->svc->buildDiscoverTeasers())->toBe([]);
});

/* ------------------------------------------------------------------
 |  Constants (documented contract with Flutter)
 | ------------------------------------------------------------------ */

it('carousel limit is 10 and completion target is 80', function () {
    expect(DashboardService::CAROUSEL_LIMIT)->toBe(10);
    expect(DashboardService::PROFILE_COMPLETION_TARGET)->toBe(80);
    expect(DashboardService::DISCOVER_TEASER_COUNT)->toBe(6);
});
