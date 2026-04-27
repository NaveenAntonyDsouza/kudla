<?php

use App\Models\PhotoAccessGrant;
use App\Models\Profile;
use App\Services\PhotoAccessService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| PhotoAccessService — binary grant store
|--------------------------------------------------------------------------
| Covers both correctness (against a real SQLite :memory: table) and
| defensive behaviour (when the table is missing — step-8's defensive
| pattern).
|
| The real `photo_access_grants` migration uses foreignId()->constrained()
| which requires the profiles table to exist. Here we recreate the table
| shape inline without FK constraints so the test can run against the
| SQLite :memory: DB alone. The schema under test is identical otherwise
| — id, grantor_profile_id, grantee_profile_id, granted_at, unique, index.
|
| Reference: docs/mobile-app/phase-2a-api/week-03-profiles-photos-search/step-08-photo-access-grants.md
*/

/** Create a minimal in-memory Profile (no DB row needed — service takes id only). */
function buildGrantProfile(int $id): Profile
{
    $p = new Profile();
    $p->exists = true;
    $p->forceFill(['id' => $id, 'matri_id' => "AM{$id}"]);

    return $p;
}

/** Set up a table matching the real migration minus FK constraints. */
function createGrantsTable(): void
{
    if (Schema::hasTable('photo_access_grants')) {
        return;
    }

    Schema::create('photo_access_grants', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('grantor_profile_id');
        $t->unsignedBigInteger('grantee_profile_id');
        $t->timestamp('granted_at')->useCurrent();
        $t->unique(['grantor_profile_id', 'grantee_profile_id']);
        $t->index('grantee_profile_id');
    });
}

beforeEach(function () {
    $this->svc = app(PhotoAccessService::class);
});

afterEach(function () {
    // Keep the table alive within a test but drop between tests so the
    // "no table" defensive cases start from a clean slate.
    Schema::dropIfExists('photo_access_grants');
});

/* ==================================================================
 |  Correctness (table exists) — exercises the real Eloquent path
 | ================================================================== */

it('grant creates a PhotoAccessGrant row', function () {
    createGrantsTable();
    $grantor = buildGrantProfile(1001);
    $grantee = buildGrantProfile(1002);

    $this->svc->grant($grantor, $grantee);

    expect(PhotoAccessGrant::where('grantor_profile_id', 1001)
        ->where('grantee_profile_id', 1002)
        ->exists())->toBeTrue();
});

it('grant is idempotent — calling twice does not duplicate', function () {
    createGrantsTable();
    $grantor = buildGrantProfile(1001);
    $grantee = buildGrantProfile(1002);

    $this->svc->grant($grantor, $grantee);
    $this->svc->grant($grantor, $grantee);

    expect(PhotoAccessGrant::where('grantor_profile_id', 1001)
        ->where('grantee_profile_id', 1002)
        ->count())->toBe(1);
});

it('hasAccess returns true after grant', function () {
    createGrantsTable();
    $grantor = buildGrantProfile(1001);
    $grantee = buildGrantProfile(1002);

    $this->svc->grant($grantor, $grantee);

    expect($this->svc->hasAccess($grantor, $grantee))->toBeTrue();
});

it('hasAccess returns false before grant', function () {
    createGrantsTable();
    $grantor = buildGrantProfile(1001);
    $grantee = buildGrantProfile(1002);

    expect($this->svc->hasAccess($grantor, $grantee))->toBeFalse();
});

it('hasAccess is directional — grantor/grantee cannot be swapped', function () {
    createGrantsTable();
    $grantor = buildGrantProfile(1001);
    $grantee = buildGrantProfile(1002);

    $this->svc->grant($grantor, $grantee);

    // The grant is 1001 → 1002. Swapping sides must NOT report access.
    expect($this->svc->hasAccess($grantee, $grantor))->toBeFalse();
});

it('revoke removes the grant row', function () {
    createGrantsTable();
    $grantor = buildGrantProfile(1001);
    $grantee = buildGrantProfile(1002);

    $this->svc->grant($grantor, $grantee);
    expect($this->svc->hasAccess($grantor, $grantee))->toBeTrue();

    $this->svc->revoke($grantor, $grantee);
    expect($this->svc->hasAccess($grantor, $grantee))->toBeFalse();
});

it('revoke is idempotent — does not throw on non-existent grant', function () {
    createGrantsTable();
    $grantor = buildGrantProfile(1001);
    $grantee = buildGrantProfile(1002);

    // No grant exists; revoke should be a silent no-op.
    expect(fn () => $this->svc->revoke($grantor, $grantee))
        ->not->toThrow(\Throwable::class);
});

it('unique constraint keeps at most one grant per (grantor, grantee)', function () {
    createGrantsTable();
    $grantor = buildGrantProfile(1001);
    $grantee = buildGrantProfile(1002);

    $this->svc->grant($grantor, $grantee);
    $this->svc->grant($grantor, $grantee);
    $this->svc->grant($grantor, $grantee);

    expect(PhotoAccessGrant::count())->toBe(1);
});

/* ==================================================================
 |  Defensive (table missing) — production tables always exist
 | ================================================================== */

it('grant does not throw when photo_access_grants table is missing', function () {
    // Don't create the table — simulates production DB hiccup / test env.
    $grantor = buildGrantProfile(2001);
    $grantee = buildGrantProfile(2002);

    expect(fn () => $this->svc->grant($grantor, $grantee))
        ->not->toThrow(\Throwable::class);
});

it('revoke does not throw when table is missing', function () {
    $grantor = buildGrantProfile(2001);
    $grantee = buildGrantProfile(2002);

    expect(fn () => $this->svc->revoke($grantor, $grantee))
        ->not->toThrow(\Throwable::class);
});

it('hasAccess returns false (never true) when table is missing', function () {
    $grantor = buildGrantProfile(2001);
    $grantee = buildGrantProfile(2002);

    // Defensive: a DB error must not accidentally unblur photos.
    expect($this->svc->hasAccess($grantor, $grantee))->toBeFalse();
});
