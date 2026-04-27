<?php

use App\Http\Controllers\Api\V1\PhotoController;
use App\Http\Requests\Api\V1\Photo\UpdatePhotoPrivacyRequest;
use App\Models\PhotoPrivacySetting;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| PhotoController::updatePrivacy — POST /api/v1/photos/privacy
|--------------------------------------------------------------------------
| Exercises the dispatch against a real inline photo_privacy_settings
| table (same pattern as step-8 / step-9) so updateOrCreate actually
| persists. FormRequest rule behaviour is locked separately in
| UpdatePhotoPrivacyRequestTest.
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
*/

function createPhotoPrivacyTable(): void
{
    if (Schema::hasTable('photo_privacy_settings')) {
        return;
    }

    Schema::create('photo_privacy_settings', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id')->unique();
        $t->string('privacy_level')->default('visible_to_all');
        $t->boolean('show_profile_photo')->default(true);
        $t->boolean('show_album_photos')->default(true);
        $t->boolean('show_family_photos')->default(true);
        $t->string('profile_photo_privacy', 30)->default('visible_to_all');
        $t->string('album_photos_privacy', 30)->default('visible_to_all');
        $t->string('family_photos_privacy', 30)->default('interest_accepted');
        $t->timestamps();
    });
}

/** Build a user + profile with no privacy row pre-existing. */
function buildPrivacyUser(int $id = 7700): User
{
    $user = new User();
    $user->exists = true;
    $user->forceFill([
        'id' => $id,
        'email' => "privacy{$id}@example.com",
        'phone' => '9871234567',
        'is_active' => true,
    ]);

    $profile = new Profile();
    $profile->exists = true;
    $profile->forceFill([
        'id' => $id,
        'user_id' => $id,
        'matri_id' => "AM{$id}",
        'gender' => 'female',
        'is_active' => true,
    ]);
    $profile->setRelation('user', $user);
    $user->setRelation('profile', $profile);

    return $user;
}

/** Build a request that's already resolved through the FormRequest lifecycle. */
function buildPrivacyRequest(User $user, array $payload): UpdatePhotoPrivacyRequest
{
    $request = UpdatePhotoPrivacyRequest::create(
        '/api/v1/photos/privacy',
        'POST',
        $payload,
    );
    $request->setUserResolver(fn () => $user);
    $request->setContainer(app());
    $request->validateResolved();

    return $request;
}

beforeEach(function () {
    createPhotoPrivacyTable();
});

afterEach(function () {
    Schema::dropIfExists('photo_privacy_settings');
});

/* ==================================================================
 |  Guard paths
 | ================================================================== */

it('returns 422 PROFILE_REQUIRED when user has no profile', function () {
    $user = buildPrivacyUser();
    $user->setRelation('profile', null);

    // We can't call validateResolved() on a request with an invalid
    // payload + no profile — FormRequest would throw first. Skip the
    // FormRequest and call the controller directly with a pre-built
    // request that passes validation.
    $request = UpdatePhotoPrivacyRequest::create('/api/v1/photos/privacy', 'POST', [
        'privacy_level' => 'hidden',
    ]);
    $request->setUserResolver(fn () => $user);
    $request->setContainer(app());
    $request->validateResolved();

    $response = app(PhotoController::class)->updatePrivacy($request);

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

// NOTE: FormRequest rejection (empty payload, bad enum, etc.) is locked
// by UpdatePhotoPrivacyRequestTest. The controller only runs after
// validation has passed — ApiExceptionHandler maps any earlier
// ValidationException to the envelope at the framework layer.

/* ==================================================================
 |  Happy path
 | ================================================================== */

it('creates a new photo_privacy_settings row on first save', function () {
    $user = buildPrivacyUser();

    $request = buildPrivacyRequest($user, [
        'privacy_level' => 'hidden',
        'album_photos_privacy' => 'interest_accepted',
    ]);
    $response = app(PhotoController::class)->updatePrivacy($request);

    expect($response->getStatusCode())->toBe(200);

    $row = PhotoPrivacySetting::where('profile_id', $user->profile->id)->first();
    expect($row)->not->toBeNull();
    expect($row->privacy_level)->toBe('hidden');
    expect($row->album_photos_privacy)->toBe('interest_accepted');
});

it('response body contains the full privacy block', function () {
    $user = buildPrivacyUser();

    $request = buildPrivacyRequest($user, [
        'privacy_level' => 'hidden',
    ]);
    $response = app(PhotoController::class)->updatePrivacy($request);
    $data = $response->getData(true)['data'];

    expect($data)->toHaveKey('privacy');
    expect($data['privacy'])->toHaveKeys([
        'privacy_level',
        'profile_photo_privacy',
        'album_photos_privacy',
        'family_photos_privacy',
    ]);
    expect($data['privacy']['privacy_level'])->toBe('hidden');
});

/* ==================================================================
 |  Partial-update semantics
 | ================================================================== */

it('partial update only touches the provided fields', function () {
    $user = buildPrivacyUser();

    // Seed with defaults + explicit values so we can detect preservation.
    PhotoPrivacySetting::create([
        'profile_id' => $user->profile->id,
        'privacy_level' => 'visible_to_all',
        'profile_photo_privacy' => 'visible_to_all',
        'album_photos_privacy' => 'visible_to_all',
        'family_photos_privacy' => 'interest_accepted',
    ]);

    $request = buildPrivacyRequest($user, [
        'album_photos_privacy' => 'hidden',
    ]);
    app(PhotoController::class)->updatePrivacy($request);

    $row = PhotoPrivacySetting::where('profile_id', $user->profile->id)->first();

    expect($row->album_photos_privacy)->toBe('hidden');           // changed
    expect($row->privacy_level)->toBe('visible_to_all');          // preserved
    expect($row->profile_photo_privacy)->toBe('visible_to_all');  // preserved
    expect($row->family_photos_privacy)->toBe('interest_accepted'); // preserved
});

it('null values in payload do not overwrite saved fields', function () {
    $user = buildPrivacyUser();

    PhotoPrivacySetting::create([
        'profile_id' => $user->profile->id,
        'privacy_level' => 'interest_accepted',
        'profile_photo_privacy' => 'interest_accepted',
        'album_photos_privacy' => 'interest_accepted',
        'family_photos_privacy' => 'interest_accepted',
    ]);

    $request = buildPrivacyRequest($user, [
        'privacy_level' => null,                 // filtered by controller
        'album_photos_privacy' => 'hidden',      // the only real change
    ]);
    app(PhotoController::class)->updatePrivacy($request);

    $row = PhotoPrivacySetting::where('profile_id', $user->profile->id)->first();

    expect($row->privacy_level)->toBe('interest_accepted');  // NOT overwritten to null
    expect($row->album_photos_privacy)->toBe('hidden');      // updated
});
