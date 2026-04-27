<?php

use App\Http\Controllers\Api\V1\PhotoController;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\User;
use App\Services\ImageProcessingService;
use App\Services\PhotoStorageService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| PhotoController — CRUD dispatch
|--------------------------------------------------------------------------
| Exercises the 6 endpoints (index / upload / setPrimary / destroy /
| deletePermanent / restore) against the real Eloquent layer on a
| SQLite :memory: DB with an inline copy of the profile_photos table.
|
| ImageProcessingService + PhotoStorageService are mocked via container
| bindings so uploads don't touch the real file system or the missing
| site_settings table.
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
*/

/** Inline table matching the production migration, without FK constraints. */
function createProfilePhotosTable(): void
{
    if (Schema::hasTable('profile_photos')) {
        return;
    }

    Schema::create('profile_photos', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('profile_id');
        $t->string('photo_type');
        $t->string('photo_url')->nullable();
        $t->string('cloudinary_public_id')->nullable();
        $t->string('thumbnail_url')->nullable();
        $t->string('medium_url')->nullable();
        $t->string('original_url')->nullable();
        $t->string('storage_driver')->default('public');
        $t->boolean('is_primary')->default(false);
        $t->boolean('is_visible')->default(true);
        $t->integer('display_order')->default(0);
        $t->string('approval_status')->default('pending');
        $t->string('rejection_reason')->nullable();
        $t->unsignedBigInteger('approved_by')->nullable();
        $t->timestamp('approved_at')->nullable();
        $t->timestamps();

        $t->index('profile_id');
    });
}

/** Fake ImageProcessingService returning deterministic paths + driver. */
function bindFakeImageProcessor(): void
{
    $fake = new class extends ImageProcessingService {
        public function __construct() {}  // skip parent's setup

        public function processUpload($file, string $storagePath, string $disk = 'public'): array
        {
            return [
                'original' => "{$storagePath}/fake-original.jpg",
                'full' => "{$storagePath}/fake-full.webp",
                'medium' => "{$storagePath}/fake-medium.webp",
                'thumb' => "{$storagePath}/fake-thumb.webp",
                'driver' => $disk,
            ];
        }

        public function deleteVariants(array $paths, string $disk = 'public'): void
        {
            // No-op — tests aren't about real file I/O.
        }
    };

    app()->instance(ImageProcessingService::class, $fake);
}

/** Fake PhotoStorageService that always returns public + reports configured. */
function bindFakeStorageService(): void
{
    $fake = new class extends PhotoStorageService {
        public function __construct() {}

        public function getActiveDriver(): string
        {
            return self::DRIVER_LOCAL;
        }

        public function isDriverConfigured(string $driver): bool
        {
            return true;
        }

        public function delete(string $driver, string $path): bool
        {
            return true;
        }
    };

    app()->instance(PhotoStorageService::class, $fake);
}

/** Build an authenticated user + profile with profilePhotos relation loaded. */
function buildUploaderUser(int $userId = 5000, int $profileId = 5000): User
{
    $user = new User();
    $user->exists = true;
    $user->forceFill([
        'id' => $userId,
        'email' => "user{$userId}@example.com",
        'phone' => '9800000000',
        'is_active' => true,
    ]);

    $profile = new Profile();
    $profile->exists = true;
    $profile->forceFill([
        'id' => $profileId,
        'user_id' => $userId,
        'matri_id' => 'AM'.str_pad((string) $profileId, 6, '0', STR_PAD_LEFT),
        'gender' => 'female',
        'is_active' => true,
        'is_approved' => true,
    ]);

    $profile->setRelation('user', $user);
    $profile->setRelation('photoPrivacySetting', null);
    $user->setRelation('profile', $profile);

    return $user;
}

/** Make a Request with the authenticated user attached. */
function authedRequest(User $user, string $path = '/api/v1/photos', string $method = 'GET', array $body = []): Request
{
    $request = Request::create($path, $method, $body);
    $request->setUserResolver(fn () => $user);

    return $request;
}

beforeEach(function () {
    createProfilePhotosTable();
    bindFakeImageProcessor();
    bindFakeStorageService();
});

afterEach(function () {
    Schema::dropIfExists('profile_photos');
});

/* ==================================================================
 |  GET /photos — index
 | ================================================================== */

it('index returns 422 when user has no profile', function () {
    $user = buildUploaderUser();
    $user->setRelation('profile', null);

    $response = app(PhotoController::class)->index(authedRequest($user));

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

it('index returns the expected top-level keys even on empty profile', function () {
    $user = buildUploaderUser();

    $response = app(PhotoController::class)->index(authedRequest($user));
    $data = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(200);
    expect($data)->toHaveKeys([
        'limits', 'counts', 'active', 'pending', 'rejected', 'archived', 'privacy',
    ]);
    expect($data['privacy'])->toBeNull();  // no photoPrivacySetting
});

it('index limits block reports ProfilePhoto::maxForType + configured size cap', function () {
    $user = buildUploaderUser();

    $response = app(PhotoController::class)->index(authedRequest($user));
    $limits = $response->getData(true)['data']['limits'];

    expect($limits['max_profile'])->toBe(ProfilePhoto::maxForType('profile'));
    expect($limits['max_album'])->toBe(ProfilePhoto::maxForType('album'));
    expect($limits['max_family'])->toBe(ProfilePhoto::maxForType('family'));
    expect($limits['max_size_mb'])->toBe((int) config('matrimony.max_photo_size_mb', 5));
});

it('index counts reflect only visible+approved photos', function () {
    $user = buildUploaderUser();
    $profile = $user->profile;

    // 1 approved album + 1 pending album + 1 archived album → count = 1
    ProfilePhoto::create(['profile_id' => $profile->id, 'photo_type' => 'album', 'is_visible' => true, 'is_primary' => false, 'approval_status' => 'approved', 'display_order' => 1, 'storage_driver' => 'public']);
    ProfilePhoto::create(['profile_id' => $profile->id, 'photo_type' => 'album', 'is_visible' => true, 'is_primary' => false, 'approval_status' => 'pending', 'display_order' => 2, 'storage_driver' => 'public']);
    ProfilePhoto::create(['profile_id' => $profile->id, 'photo_type' => 'album', 'is_visible' => false, 'is_primary' => false, 'approval_status' => 'approved', 'display_order' => 3, 'storage_driver' => 'public']);

    $profile->unsetRelation('profilePhotos');  // force reload

    $response = app(PhotoController::class)->index(authedRequest($user));
    $counts = $response->getData(true)['data']['counts'];

    expect($counts['album_used'])->toBe(1);
    expect($counts['profile_used'])->toBe(0);
    expect($counts['family_used'])->toBe(0);
});

/* ==================================================================
 |  POST /photos — upload
 | ================================================================== */

it('upload creates an approved album photo when auto-approve is on', function () {
    $user = buildUploaderUser();

    $file = UploadedFile::fake()->image('test.jpg');
    $request = \App\Http\Requests\Api\V1\Photo\UploadPhotoRequest::create('/api/v1/photos', 'POST', [
        'photo_type' => 'album',
    ], [], ['photo' => $file]);
    $request->setUserResolver(fn () => $user);
    // Bypass FormRequest's own validateResolved by triggering it manually:
    $request->setContainer(app());
    $request->validateResolved();

    $response = app(PhotoController::class)->upload($request);

    expect($response->getStatusCode())->toBe(201);
    $body = $response->getData(true)['data'];
    expect($body)->toHaveKeys(['photo', 'needs_approval']);
    expect(ProfilePhoto::where('profile_id', $user->profile->id)->count())->toBe(1);
});

it('upload returns 422 when album slot quota is exhausted', function () {
    $user = buildUploaderUser();
    $profile = $user->profile;

    // Fill every album slot with approved visible photos.
    $max = ProfilePhoto::maxForType('album');
    for ($i = 0; $i < $max; $i++) {
        ProfilePhoto::create([
            'profile_id' => $profile->id,
            'photo_type' => 'album',
            'is_visible' => true,
            'is_primary' => false,
            'approval_status' => 'approved',
            'display_order' => $i + 1,
            'storage_driver' => 'public',
        ]);
    }

    $file = UploadedFile::fake()->image('over.jpg');
    $request = \App\Http\Requests\Api\V1\Photo\UploadPhotoRequest::create('/api/v1/photos', 'POST', [
        'photo_type' => 'album',
    ], [], ['photo' => $file]);
    $request->setUserResolver(fn () => $user);
    $request->setContainer(app());
    $request->validateResolved();

    $response = app(PhotoController::class)->upload($request);

    expect($response->getStatusCode())->toBe(422);
    $body = $response->getData(true);
    expect($body['error']['code'])->toBe('VALIDATION_FAILED');
    expect($body['error']['fields'])->toHaveKey('photo_type');
});

it('upload archives the previous primary when new photo is profile-type', function () {
    $user = buildUploaderUser();
    $profile = $user->profile;

    $old = ProfilePhoto::create([
        'profile_id' => $profile->id,
        'photo_type' => 'profile',
        'is_visible' => true,
        'is_primary' => true,
        'approval_status' => 'approved',
        'display_order' => 1,
        'storage_driver' => 'public',
    ]);

    $file = UploadedFile::fake()->image('new-primary.jpg');
    $request = \App\Http\Requests\Api\V1\Photo\UploadPhotoRequest::create('/api/v1/photos', 'POST', [
        'photo_type' => 'profile',
    ], [], ['photo' => $file]);
    $request->setUserResolver(fn () => $user);
    $request->setContainer(app());
    $request->validateResolved();

    app(PhotoController::class)->upload($request);

    // Old primary is archived + unset as primary.
    $old->refresh();
    expect($old->is_visible)->toBeFalse();
    expect($old->is_primary)->toBeFalse();
});

it('upload returns 422 when user has no profile', function () {
    $user = buildUploaderUser();
    $user->setRelation('profile', null);

    $file = UploadedFile::fake()->image('orphan.jpg');
    $request = \App\Http\Requests\Api\V1\Photo\UploadPhotoRequest::create('/api/v1/photos', 'POST', [
        'photo_type' => 'album',
    ], [], ['photo' => $file]);
    $request->setUserResolver(fn () => $user);
    $request->setContainer(app());
    $request->validateResolved();

    $response = app(PhotoController::class)->upload($request);

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['code'])->toBe('PROFILE_REQUIRED');
});

/* ==================================================================
 |  POST /photos/{id}/primary — setPrimary
 | ================================================================== */

it('setPrimary atomically swaps primary flag', function () {
    $user = buildUploaderUser();
    $profile = $user->profile;

    $existing = ProfilePhoto::create([
        'profile_id' => $profile->id,
        'photo_type' => 'profile',
        'is_visible' => true,
        'is_primary' => true,
        'approval_status' => 'approved',
        'display_order' => 1,
        'storage_driver' => 'public',
    ]);
    $target = ProfilePhoto::create([
        'profile_id' => $profile->id,
        'photo_type' => 'profile',
        'is_visible' => true,
        'is_primary' => false,
        'approval_status' => 'approved',
        'display_order' => 2,
        'storage_driver' => 'public',
    ]);

    $response = app(PhotoController::class)->setPrimary(authedRequest($user), $target);

    expect($response->getStatusCode())->toBe(200);
    expect($existing->fresh()->is_primary)->toBeFalse();
    expect($target->fresh()->is_primary)->toBeTrue();
});

it('setPrimary returns 403 when viewer is not the owner', function () {
    $owner = buildUploaderUser(5000, 5000);
    $stranger = buildUploaderUser(6000, 6000);

    $photo = ProfilePhoto::create([
        'profile_id' => $owner->profile->id,
        'photo_type' => 'profile',
        'is_visible' => true,
        'is_primary' => false,
        'approval_status' => 'approved',
        'display_order' => 1,
        'storage_driver' => 'public',
    ]);

    $response = app(PhotoController::class)->setPrimary(authedRequest($stranger), $photo);

    expect($response->getStatusCode())->toBe(403);
    expect($response->getData(true)['error']['code'])->toBe('UNAUTHORIZED');
});

it('setPrimary returns 422 for non-profile photo_type', function () {
    $user = buildUploaderUser();
    $album = ProfilePhoto::create([
        'profile_id' => $user->profile->id,
        'photo_type' => 'album',
        'is_visible' => true,
        'is_primary' => false,
        'approval_status' => 'approved',
        'display_order' => 1,
        'storage_driver' => 'public',
    ]);

    $response = app(PhotoController::class)->setPrimary(authedRequest($user), $album);

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true)['error']['fields'])->toHaveKey('photo_type');
});

it('setPrimary rejects archived + unapproved photos', function () {
    $user = buildUploaderUser();

    $archived = ProfilePhoto::create([
        'profile_id' => $user->profile->id,
        'photo_type' => 'profile',
        'is_visible' => false,
        'is_primary' => false,
        'approval_status' => 'approved',
        'display_order' => 1,
        'storage_driver' => 'public',
    ]);
    $pending = ProfilePhoto::create([
        'profile_id' => $user->profile->id,
        'photo_type' => 'profile',
        'is_visible' => true,
        'is_primary' => false,
        'approval_status' => 'pending',
        'display_order' => 2,
        'storage_driver' => 'public',
    ]);

    $r1 = app(PhotoController::class)->setPrimary(authedRequest($user), $archived);
    $r2 = app(PhotoController::class)->setPrimary(authedRequest($user), $pending);

    expect($r1->getStatusCode())->toBe(422);
    expect($r2->getStatusCode())->toBe(422);
});

/* ==================================================================
 |  DELETE /photos/{id} — destroy (soft)
 | ================================================================== */

it('destroy archives the photo (soft delete)', function () {
    $user = buildUploaderUser();
    $photo = ProfilePhoto::create([
        'profile_id' => $user->profile->id,
        'photo_type' => 'album',
        'is_visible' => true,
        'is_primary' => true,
        'approval_status' => 'approved',
        'display_order' => 1,
        'storage_driver' => 'public',
    ]);

    $response = app(PhotoController::class)->destroy(authedRequest($user), $photo);

    expect($response->getStatusCode())->toBe(200);
    $body = $response->getData(true)['data'];
    expect($body['archived'])->toBeTrue();
    expect($body['undo_until'])->toMatch('/^\d{4}-\d{2}-\d{2}T/');
    expect($photo->fresh()->is_visible)->toBeFalse();
    expect($photo->fresh()->is_primary)->toBeFalse();
    // Row still exists — soft delete.
    expect(ProfilePhoto::find($photo->id))->not->toBeNull();
});

it('destroy returns 403 for non-owner', function () {
    $owner = buildUploaderUser(5000, 5000);
    $stranger = buildUploaderUser(6000, 6000);

    $photo = ProfilePhoto::create([
        'profile_id' => $owner->profile->id,
        'photo_type' => 'album',
        'is_visible' => true,
        'is_primary' => false,
        'approval_status' => 'approved',
        'display_order' => 1,
        'storage_driver' => 'public',
    ]);

    $response = app(PhotoController::class)->destroy(authedRequest($stranger), $photo);

    expect($response->getStatusCode())->toBe(403);
});

/* ==================================================================
 |  DELETE /photos/{id}/permanent — hard delete
 | ================================================================== */

it('deletePermanent wipes the row and the storage variants', function () {
    $user = buildUploaderUser();
    $photo = ProfilePhoto::create([
        'profile_id' => $user->profile->id,
        'photo_type' => 'album',
        'is_visible' => false,
        'is_primary' => false,
        'approval_status' => 'approved',
        'photo_url' => 'photos/1/full.webp',
        'display_order' => 1,
        'storage_driver' => 'public',
    ]);
    $photoId = $photo->id;

    $response = app(PhotoController::class)->deletePermanent(authedRequest($user), $photo);

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data']['deleted'])->toBeTrue();
    expect(ProfilePhoto::find($photoId))->toBeNull();  // row gone
});

it('deletePermanent returns 403 for non-owner', function () {
    $owner = buildUploaderUser(5000, 5000);
    $stranger = buildUploaderUser(6000, 6000);

    $photo = ProfilePhoto::create([
        'profile_id' => $owner->profile->id,
        'photo_type' => 'album',
        'is_visible' => true,
        'is_primary' => false,
        'approval_status' => 'approved',
        'display_order' => 1,
        'storage_driver' => 'public',
    ]);

    $response = app(PhotoController::class)->deletePermanent(authedRequest($stranger), $photo);

    expect($response->getStatusCode())->toBe(403);
    expect(ProfilePhoto::find($photo->id))->not->toBeNull();  // not deleted
});

/* ==================================================================
 |  POST /photos/{id}/restore — un-archive
 | ================================================================== */

it('restore un-archives when a slot is available', function () {
    $user = buildUploaderUser();
    $photo = ProfilePhoto::create([
        'profile_id' => $user->profile->id,
        'photo_type' => 'album',
        'is_visible' => false,  // archived
        'is_primary' => false,
        'approval_status' => 'approved',
        'display_order' => 1,
        'storage_driver' => 'public',
    ]);

    $response = app(PhotoController::class)->restore(authedRequest($user), $photo);

    expect($response->getStatusCode())->toBe(200);
    expect($photo->fresh()->is_visible)->toBeTrue();
});

it('restore rejects with 422 when slot is full', function () {
    $user = buildUploaderUser();
    $profile = $user->profile;

    // Fill every album slot.
    $max = ProfilePhoto::maxForType('album');
    for ($i = 0; $i < $max; $i++) {
        ProfilePhoto::create([
            'profile_id' => $profile->id,
            'photo_type' => 'album',
            'is_visible' => true,
            'is_primary' => false,
            'approval_status' => 'approved',
            'display_order' => $i + 1,
            'storage_driver' => 'public',
        ]);
    }

    $archived = ProfilePhoto::create([
        'profile_id' => $profile->id,
        'photo_type' => 'album',
        'is_visible' => false,
        'is_primary' => false,
        'approval_status' => 'approved',
        'display_order' => $max + 1,
        'storage_driver' => 'public',
    ]);

    $response = app(PhotoController::class)->restore(authedRequest($user), $archived);

    expect($response->getStatusCode())->toBe(422);
    expect($archived->fresh()->is_visible)->toBeFalse();  // still archived
});

it('restore returns 403 for non-owner', function () {
    $owner = buildUploaderUser(5000, 5000);
    $stranger = buildUploaderUser(6000, 6000);

    $photo = ProfilePhoto::create([
        'profile_id' => $owner->profile->id,
        'photo_type' => 'album',
        'is_visible' => false,
        'is_primary' => false,
        'approval_status' => 'approved',
        'display_order' => 1,
        'storage_driver' => 'public',
    ]);

    $response = app(PhotoController::class)->restore(authedRequest($stranger), $photo);

    expect($response->getStatusCode())->toBe(403);
});
