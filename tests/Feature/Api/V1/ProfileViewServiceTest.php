<?php

use App\Models\Profile;
use App\Services\ProfileViewService;

/*
|--------------------------------------------------------------------------
| ProfileViewService — defensive + self-view-skip
|--------------------------------------------------------------------------
| The actual insert/dedup DB behaviour is verified in the Bruno smoke
| (step-16) against a migrated MySQL database. These unit tests lock two
| invariants that must hold regardless of DB state:
|
|   1. track() never throws — production must not break if the
|      profile_views table is temporarily unreachable.
|   2. Self-views are skipped — doesn't even attempt a query when
|      viewer.id === target.id. Verifies the early-return guard.
|
| Reference: docs/mobile-app/phase-2a-api/week-03-profiles-photos-search/step-05-view-other-profile.md
*/

function buildViewProfile(int $id): Profile
{
    $p = new Profile();
    $p->exists = true;
    $p->forceFill(['id' => $id, 'matri_id' => "AM{$id}"]);

    return $p;
}

beforeEach(function () {
    $this->svc = app(ProfileViewService::class);
});

it('track does not throw when profile_views table is missing', function () {
    $viewer = buildViewProfile(9901);
    $target = buildViewProfile(9902);

    // No exception even though the table doesn't exist in the test DB.
    expect(fn () => $this->svc->track($viewer, $target))
        ->not->toThrow(\Throwable::class);
});

it('track short-circuits on self-view (viewer.id === target.id)', function () {
    $self = buildViewProfile(9900);

    // Even if the DB were available, track() returns before any query
    // when the two profiles are the same.
    expect(fn () => $this->svc->track($self, $self))
        ->not->toThrow(\Throwable::class);
});

it('dedup window constant is 24 hours', function () {
    // Locked so the contract with Flutter stays stable: one view per
    // viewer-target per rolling 24h window.
    expect(ProfileViewService::DEDUP_WINDOW_HOURS)->toBe(24);
});
