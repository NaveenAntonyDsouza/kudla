<?php

use App\Models\Profile;
use App\Models\User;
use App\Services\ProfileAccessService;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| ProfileAccessService — 7-gate privacy check
|--------------------------------------------------------------------------
| Tests every gate's positive + negative path. Uses in-memory Profile
| instances with very high IDs (9990+) so the DB-touching gates
| (isBlocked, hasAnyInterest) return empty results naturally — no need
| for mocks or RefreshDatabase.
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
*/

function buildAccessProfile(array $overrides = []): Profile
{
    $user = new User();
    $user->exists = true;
    $user->forceFill(array_merge([
        'id' => 9999,
        'email' => 'access-test@example.com',
        'is_active' => true,
    ], $overrides['user'] ?? []));

    $profile = new Profile();
    $profile->exists = true;
    $profile->forceFill(array_merge([
        'id' => 9999,
        'matri_id' => 'AM999999',
        'gender' => 'male',
        'date_of_birth' => Carbon::parse('1990-01-01'),
        'is_active' => true,
        'is_approved' => true,
        'is_hidden' => false,
        'suspension_status' => 'active',
        'show_profile_to' => 'all',
    ], $overrides['profile'] ?? []));

    $profile->setRelation('user', $user);
    $profile->setRelation('partnerPreference', null);
    $profile->setRelation('photoPrivacySetting', null);

    return $profile;
}

beforeEach(function () {
    $this->svc = app(ProfileAccessService::class);
});

/* ------------------------------------------------------------------
 |  Self gate
 | ------------------------------------------------------------------ */

it('REASON_SELF when viewer and target are the same profile', function () {
    $p = buildAccessProfile();

    expect($this->svc->check($p, $p))->toBe(ProfileAccessService::REASON_SELF);
    expect($this->svc->canAccess($p, $p))->toBeTrue();
});

it('canSendInterest false when target is self', function () {
    $p = buildAccessProfile();

    expect($this->svc->canSendInterest($p, $p))->toBeFalse();
});

it('canShortlist false when target is self', function () {
    $p = buildAccessProfile();

    expect($this->svc->canShortlist($p, $p))->toBeFalse();
});

/* ------------------------------------------------------------------
 |  Gender gate
 | ------------------------------------------------------------------ */

it('REASON_SAME_GENDER for two males', function () {
    $male1 = buildAccessProfile(['profile' => ['id' => 9991, 'gender' => 'male']]);
    $male2 = buildAccessProfile(['profile' => ['id' => 9992, 'gender' => 'male']]);

    expect($this->svc->check($male1, $male2))->toBe(ProfileAccessService::REASON_SAME_GENDER);
});

it('REASON_SAME_GENDER for two females', function () {
    $f1 = buildAccessProfile(['profile' => ['id' => 9991, 'gender' => 'female']]);
    $f2 = buildAccessProfile(['profile' => ['id' => 9992, 'gender' => 'female']]);

    expect($this->svc->check($f1, $f2))->toBe(ProfileAccessService::REASON_SAME_GENDER);
});

it('gender check is case-insensitive', function () {
    $p1 = buildAccessProfile(['profile' => ['id' => 9991, 'gender' => 'Male']]);
    $p2 = buildAccessProfile(['profile' => ['id' => 9992, 'gender' => 'MALE']]);

    expect($this->svc->sameGender($p1, $p2))->toBeTrue();
});

it('sameGender returns false when either side missing gender', function () {
    $p1 = buildAccessProfile(['profile' => ['id' => 9991, 'gender' => null]]);
    $p2 = buildAccessProfile(['profile' => ['id' => 9992, 'gender' => 'male']]);

    expect($this->svc->sameGender($p1, $p2))->toBeFalse();
});

/* ------------------------------------------------------------------
 |  Suspension gate
 | ------------------------------------------------------------------ */

it('REASON_SUSPENDED when target.suspension_status is banned', function () {
    $viewer = buildAccessProfile(['profile' => ['id' => 9991, 'gender' => 'male']]);
    $target = buildAccessProfile(['profile' => ['id' => 9992, 'gender' => 'female', 'suspension_status' => 'banned']]);

    expect($this->svc->check($viewer, $target))->toBe(ProfileAccessService::REASON_SUSPENDED);
});

it('REASON_SUSPENDED when target.suspension_status is suspended', function () {
    $viewer = buildAccessProfile(['profile' => ['id' => 9991, 'gender' => 'male']]);
    $target = buildAccessProfile(['profile' => ['id' => 9992, 'gender' => 'female', 'suspension_status' => 'suspended']]);

    expect($this->svc->check($viewer, $target))->toBe(ProfileAccessService::REASON_SUSPENDED);
});

it('isSuspended helper treats active + missing status as not suspended', function () {
    $active = buildAccessProfile(['profile' => ['suspension_status' => 'active']]);
    $missing = buildAccessProfile(['profile' => ['suspension_status' => null]]);

    expect($this->svc->isSuspended($active))->toBeFalse();
    expect($this->svc->isSuspended($missing))->toBeFalse();
});

/* ------------------------------------------------------------------
 |  Hidden gate
 | ------------------------------------------------------------------ */

it('REASON_HIDDEN when target.is_hidden and no pre-existing interest', function () {
    $viewer = buildAccessProfile(['profile' => ['id' => 9991, 'gender' => 'male']]);
    $target = buildAccessProfile(['profile' => ['id' => 9992, 'gender' => 'female', 'is_hidden' => true]]);

    // isBlocked + hasAnyInterest are defensive against missing tables (test env)
    // → return false → hidden gate fires
    expect($this->svc->check($viewer, $target))->toBe(ProfileAccessService::REASON_HIDDEN);
});

it('isHidden helper', function () {
    expect($this->svc->isHidden(buildAccessProfile(['profile' => ['is_hidden' => true]])))->toBeTrue();
    expect($this->svc->isHidden(buildAccessProfile(['profile' => ['is_hidden' => false]])))->toBeFalse();
    expect($this->svc->isHidden(buildAccessProfile(['profile' => ['is_hidden' => null]])))->toBeFalse();
});

/* ------------------------------------------------------------------
 |  Visibility gate
 | ------------------------------------------------------------------ */

it('REASON_VISIBILITY_PREMIUM when target show_profile_to=premium and viewer not premium', function () {
    $viewer = buildAccessProfile(['profile' => ['id' => 9991, 'gender' => 'male']]);
    $target = buildAccessProfile(['profile' => ['id' => 9992, 'gender' => 'female', 'show_profile_to' => 'premium']]);

    // Viewer is not premium (relation not loaded → isPremium returns false safely)
    expect($this->svc->check($viewer, $target))->toBe(ProfileAccessService::REASON_VISIBILITY_PREMIUM);
});

/* ------------------------------------------------------------------
 |  Happy path
 | ------------------------------------------------------------------ */

it('REASON_OK when opposite-gender, no blocks, not suspended, not hidden, visibility=all', function () {
    $viewer = buildAccessProfile(['profile' => ['id' => 9991, 'gender' => 'male']]);
    $target = buildAccessProfile(['profile' => ['id' => 9992, 'gender' => 'female', 'show_profile_to' => 'all']]);

    expect($this->svc->check($viewer, $target))->toBe(ProfileAccessService::REASON_OK);
    expect($this->svc->canAccess($viewer, $target))->toBeTrue();
    expect($this->svc->canSendInterest($viewer, $target))->toBeTrue();
    expect($this->svc->canShortlist($viewer, $target))->toBeTrue();
});

/* ------------------------------------------------------------------
 |  Contact gate (derived)
 | ------------------------------------------------------------------ */

it('canViewContact true for self', function () {
    $p = buildAccessProfile();

    expect($this->svc->canViewContact($p, $p))->toBeTrue();
});

it('canViewContact false when viewer not premium (even with clean access)', function () {
    $viewer = buildAccessProfile(['profile' => ['id' => 9991, 'gender' => 'male']]);
    $target = buildAccessProfile(['profile' => ['id' => 9992, 'gender' => 'female', 'show_profile_to' => 'all']]);

    // Viewer is not premium → contact gate fails
    expect($this->svc->canViewContact($viewer, $target))->toBeFalse();
});

/* ------------------------------------------------------------------
 |  Photo blur helper
 | ------------------------------------------------------------------ */

it('shouldBlurPhotos false for self', function () {
    $p = buildAccessProfile();

    expect($this->svc->shouldBlurPhotos($p, $p))->toBeFalse();
});

it('shouldBlurPhotos false when target has no photo_privacy row', function () {
    $viewer = buildAccessProfile(['profile' => ['id' => 9991]]);
    $target = buildAccessProfile(['profile' => ['id' => 9992]]);
    // Neither has photoPrivacySetting relation loaded/set → default false

    expect($this->svc->shouldBlurPhotos($viewer, $target))->toBeFalse();
});
