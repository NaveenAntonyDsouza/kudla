<?php

use App\Http\Resources\V1\PhotoResource;
use App\Models\PhotoPrivacySetting;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\User;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| PhotoResource — absolute URL contract + shape
|--------------------------------------------------------------------------
| Locks what Flutter's `cached_network_image` needs:
|   - Every URL field starts with http:// or https:// (absolute)
|   - `original_url` only populated for the owner (prevents free-tier leak
|     of full-resolution images)
|   - `is_blurred` respects the current PhotoPrivacySetting schema
|   - All timestamps are ISO 8601 or null
|   - Booleans are real booleans
|   - Missing relations don't crash — stable shape under partial data
|
| The fuller "gated_premium + photo_access_grants" blur logic lands in
| step-8 which owns the photo_privacy_settings column migration + the
| PhotoAccessGrant model. Step-7's job is the URL contract + test coverage
| of what works today.
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
*/

/** Build an in-memory ProfilePhoto with all the attributes the Resource reads. */
function buildPhoto(array $overrides = []): ProfilePhoto
{
    $photo = new ProfilePhoto();
    $photo->exists = true;
    $photo->forceFill(array_merge([
        'id' => 555,
        'profile_id' => 42,
        'photo_type' => 'album',
        'photo_url' => 'profile-photos/42/album-main.jpg',
        'thumbnail_url' => 'profile-photos/42/album-thumb.jpg',
        'medium_url' => 'profile-photos/42/album-med.jpg',
        'original_url' => 'profile-photos/42/album-orig.jpg',
        'storage_driver' => 'public',
        'is_primary' => false,
        'is_visible' => true,
        'display_order' => 1,
        'approval_status' => ProfilePhoto::STATUS_APPROVED,
        'rejection_reason' => null,
        'approved_at' => Carbon::parse('2026-04-01 10:00:00'),
        'created_at' => Carbon::parse('2026-03-30 09:00:00'),
    ], $overrides));

    return $photo;
}

/** Build the owning Profile + optionally a PhotoPrivacySetting, wired onto the photo. */
function wirePhotoProfile(ProfilePhoto $photo, ?array $privacyOverrides = null, array $profileOverrides = []): Profile
{
    $owner = new Profile();
    $owner->exists = true;
    $owner->forceFill(array_merge([
        'id' => $photo->profile_id,
        'matri_id' => 'AM'.str_pad((string) $photo->profile_id, 6, '0', STR_PAD_LEFT),
        'gender' => 'female',
        'is_active' => true,
        'is_approved' => true,
    ], $profileOverrides));

    if ($privacyOverrides === null) {
        $owner->setRelation('photoPrivacySetting', null);
    } else {
        $privacy = new PhotoPrivacySetting();
        $privacy->exists = true;
        $privacy->forceFill(array_merge([
            'profile_id' => $owner->id,
        ], $privacyOverrides));
        $owner->setRelation('photoPrivacySetting', $privacy);
    }

    $photo->setRelation('profile', $owner);

    return $owner;
}

/** Build a viewer Profile (different id from photo owner by default). */
function buildViewer(int $id = 99, bool $premium = false): Profile
{
    $user = new User();
    $user->exists = true;
    $user->forceFill([
        'id' => $id,
        'email' => "viewer{$id}@example.com",
        'is_active' => true,
    ]);
    // An empty collection means relationLoaded('userMemberships') is true but
    // the premium check below short-circuits via our override.
    $user->setRelation('userMemberships', new \Illuminate\Database\Eloquent\Collection());

    $viewer = new Profile();
    $viewer->exists = true;
    $viewer->forceFill([
        'id' => $id,
        'user_id' => $id,
        'matri_id' => 'AM'.str_pad((string) $id, 6, '0', STR_PAD_LEFT),
        'gender' => 'male',
        'is_active' => true,
    ]);
    $viewer->setRelation('user', $user);

    // Override isPremium via a closure would be ideal, but we can't. Instead:
    // if $premium === false, PhotoResource's shouldBlurFor calls
    // `$viewer->user?->isPremium()`. That call hits user_memberships which
    // doesn't exist in test DB → throws. We'd need to wrap in try/catch,
    // BUT the stub PhotoResource doesn't — it calls directly. So $premium
    // toggling isn't testable cleanly here without monkey-patching.
    //
    // Workaround for the "viewer not premium" case: we EXPECT a DB error
    // on that path when blur_non_premium is true. To test it, we'll set
    // blur_non_premium on the privacy row WITHOUT pre-loading user
    // memberships beyond empty, which makes isPremium() throw.
    // Tests that hit this path will assert the thrown exception OR run
    // the resource with viewer=null to skip the isPremium path.

    return $viewer;
}

/* ==================================================================
 |  Shape — 15 keys, all present every time
 | ================================================================== */

it('returns all 15 expected keys', function () {
    $photo = buildPhoto();
    wirePhotoProfile($photo);

    $data = (new PhotoResource($photo))->resolve();

    expect($data)->toHaveKeys([
        'id', 'photo_type', 'url', 'thumbnail_url', 'medium_url', 'original_url',
        'is_primary', 'is_visible', 'is_blurred',
        'approval_status', 'rejection_reason',
        'display_order', 'storage_driver',
        'approved_at', 'created_at',
    ]);
});

it('returns primitive types for every field', function () {
    $photo = buildPhoto();
    wirePhotoProfile($photo);

    $data = (new PhotoResource($photo))->resolve();

    expect($data['id'])->toBeInt();
    expect($data['photo_type'])->toBeString();
    expect($data['is_primary'])->toBeBool();
    expect($data['is_visible'])->toBeBool();
    expect($data['is_blurred'])->toBeBool();
    expect($data['approval_status'])->toBeString();
    expect($data['display_order'])->toBeInt();
    expect($data['storage_driver'])->toBeString();
});

/* ==================================================================
 |  URL contract — all absolute, every variant
 | ================================================================== */

it('url is absolute (starts with http:// or https://)', function () {
    $photo = buildPhoto();
    wirePhotoProfile($photo);

    $data = (new PhotoResource($photo))->resolve();

    expect($data['url'])->toMatch('#^https?://#');
});

it('thumbnail_url is absolute', function () {
    $photo = buildPhoto();
    wirePhotoProfile($photo);

    $data = (new PhotoResource($photo))->resolve();

    expect($data['thumbnail_url'])->toMatch('#^https?://#');
});

it('medium_url is absolute', function () {
    $photo = buildPhoto();
    wirePhotoProfile($photo);

    $data = (new PhotoResource($photo))->resolve();

    expect($data['medium_url'])->toMatch('#^https?://#');
});

it('thumbnail_url falls back to full url when thumbnail column is empty', function () {
    $photo = buildPhoto(['thumbnail_url' => null]);
    wirePhotoProfile($photo);

    $data = (new PhotoResource($photo))->resolve();

    // Accessor's fallback kicks in — still absolute, still the full photo URL.
    expect($data['thumbnail_url'])->toMatch('#^https?://#');
    expect($data['thumbnail_url'])->toBe($data['url']);
});

it('medium_url falls back to full url when medium column is empty', function () {
    $photo = buildPhoto(['medium_url' => null]);
    wirePhotoProfile($photo);

    $data = (new PhotoResource($photo))->resolve();

    expect($data['medium_url'])->toBe($data['url']);
});

/* ==================================================================
 |  original_url — owner-only gate
 | ================================================================== */

it('original_url is populated when viewer is the photo owner', function () {
    $photo = buildPhoto(['profile_id' => 42]);
    $owner = wirePhotoProfile($photo);

    $data = (new PhotoResource($photo, viewer: $owner))->resolve();

    expect($data['original_url'])->toBeString();
    expect($data['original_url'])->toMatch('#^https?://#');
});

it('original_url is null when viewer is NOT the photo owner', function () {
    $photo = buildPhoto(['profile_id' => 42]);
    wirePhotoProfile($photo);
    $stranger = buildViewer(id: 99);  // id !== profile_id

    $data = (new PhotoResource($photo, viewer: $stranger))->resolve();

    expect($data['original_url'])->toBeNull();
});

it('original_url is null when viewer is anonymous (null)', function () {
    $photo = buildPhoto();
    wirePhotoProfile($photo);

    $data = (new PhotoResource($photo, viewer: null))->resolve();

    expect($data['original_url'])->toBeNull();
});

/* ==================================================================
 |  is_blurred — current-schema behaviour (step-8 expands this)
 | ================================================================== */

it('is_blurred is true when viewer is null (anonymous / public view)', function () {
    $photo = buildPhoto();
    wirePhotoProfile($photo);

    $data = (new PhotoResource($photo))->resolve();

    expect($data['is_blurred'])->toBeTrue();
});

it('is_blurred is false when viewer is the owner', function () {
    $photo = buildPhoto(['profile_id' => 42]);
    $owner = wirePhotoProfile($photo);

    $data = (new PhotoResource($photo, viewer: $owner))->resolve();

    expect($data['is_blurred'])->toBeFalse();
});

it('is_blurred is false when target has no PhotoPrivacySetting row', function () {
    $photo = buildPhoto(['profile_id' => 42]);
    wirePhotoProfile($photo, privacyOverrides: null);  // no privacy row
    $stranger = buildViewer(99);

    $data = (new PhotoResource($photo, viewer: $stranger))->resolve();

    expect($data['is_blurred'])->toBeFalse();
});

it('is_blurred is false when privacy has blur_non_premium=false', function () {
    $photo = buildPhoto(['profile_id' => 42]);
    wirePhotoProfile($photo, privacyOverrides: [
        'blur_non_premium' => false,
    ]);
    $stranger = buildViewer(99);

    $data = (new PhotoResource($photo, viewer: $stranger))->resolve();

    expect($data['is_blurred'])->toBeFalse();
});

/* ==================================================================
 |  rejection_reason — surfaced only when approval_status=rejected
 | ================================================================== */

it('rejection_reason is null when approval_status is approved', function () {
    $photo = buildPhoto([
        'approval_status' => ProfilePhoto::STATUS_APPROVED,
        'rejection_reason' => 'blurry',  // stale reason from a prior rejection
    ]);
    wirePhotoProfile($photo);

    $data = (new PhotoResource($photo))->resolve();

    expect($data['rejection_reason'])->toBeNull();
    expect($data['approval_status'])->toBe('approved');
});

it('rejection_reason is surfaced when approval_status is rejected', function () {
    $photo = buildPhoto([
        'approval_status' => ProfilePhoto::STATUS_REJECTED,
        'rejection_reason' => 'blurry',
    ]);
    wirePhotoProfile($photo);

    $data = (new PhotoResource($photo))->resolve();

    expect($data['rejection_reason'])->toBe('blurry');
    expect($data['approval_status'])->toBe('rejected');
});

it('rejection_reason is null when approval_status is pending', function () {
    $photo = buildPhoto([
        'approval_status' => ProfilePhoto::STATUS_PENDING,
        'rejection_reason' => null,
    ]);
    wirePhotoProfile($photo);

    $data = (new PhotoResource($photo))->resolve();

    expect($data['rejection_reason'])->toBeNull();
    expect($data['approval_status'])->toBe('pending');
});

/* ==================================================================
 |  Timestamps — ISO 8601 or null
 | ================================================================== */

it('created_at is ISO 8601 formatted', function () {
    $photo = buildPhoto();
    wirePhotoProfile($photo);

    $data = (new PhotoResource($photo))->resolve();

    expect($data['created_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
});

it('approved_at is ISO 8601 when set, null when unset', function () {
    $photo = buildPhoto(['approved_at' => null]);
    wirePhotoProfile($photo);

    $data = (new PhotoResource($photo))->resolve();

    expect($data['approved_at'])->toBeNull();

    $photo2 = buildPhoto(['approved_at' => Carbon::parse('2026-04-10 15:30:00')]);
    wirePhotoProfile($photo2);

    $data2 = (new PhotoResource($photo2))->resolve();

    expect($data2['approved_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
});

/* ==================================================================
 |  Defensive — no crash on missing relations
 | ================================================================== */

it('does not crash when photo has no profile relation', function () {
    $photo = buildPhoto();
    // Don't wire profile — shouldBlurFor needs to handle null profile gracefully.

    $data = (new PhotoResource($photo, viewer: null))->resolve();

    // Anonymous viewer with no profile relation → blurred (public fallback).
    expect($data['is_blurred'])->toBeTrue();
    expect($data['url'])->toMatch('#^https?://#');
});

it('display_order defaults to 0 when null in DB', function () {
    $photo = buildPhoto(['display_order' => null]);
    wirePhotoProfile($photo);

    $data = (new PhotoResource($photo))->resolve();

    expect($data['display_order'])->toBe(0);
});

it('storage_driver defaults to public when null', function () {
    $photo = buildPhoto(['storage_driver' => null]);
    wirePhotoProfile($photo);

    $data = (new PhotoResource($photo))->resolve();

    expect($data['storage_driver'])->toBe('public');
});
