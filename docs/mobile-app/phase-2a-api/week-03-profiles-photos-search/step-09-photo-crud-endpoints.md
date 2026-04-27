# Step 9 — Photo CRUD Endpoints

## Goal
`GET /photos` (list grouped), `POST /photos` (upload multipart), `POST /photos/{id}/primary`, `DELETE /photos/{id}` (archive), `POST /photos/{id}/restore`.

## Prerequisites
- [ ] [step-08 — photo_access_grants](step-08-photo-access-grants.md) complete

## Procedure

### 1. Create `PhotoController`

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\PhotoResource;
use App\Http\Responses\ApiResponse;
use App\Models\ProfilePhoto;
use App\Services\ImageProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhotoController extends BaseApiController
{
    public function __construct(private ImageProcessingService $images) {}

    /**
     * @authenticated
     * @group Photos
     */
    public function index(Request $request): JsonResponse
    {
        $profile = $request->user()->profile;
        $photos = $profile->profilePhotos()->get();

        return ApiResponse::ok([
            'limits' => [
                'max_profile' => config('matrimony.max_profile_photos', 1),
                'max_album' => config('matrimony.max_album_photos', 9),
                'max_family' => config('matrimony.max_family_photos', 3),
                'max_size_mb' => config('matrimony.max_photo_size_mb', 30),
            ],
            'counts' => [
                'profile_used' => $photos->where('photo_type', 'profile')->where('is_visible', true)->count(),
                'album_used' => $photos->where('photo_type', 'album')->where('is_visible', true)->count(),
                'family_used' => $photos->where('photo_type', 'family')->where('is_visible', true)->count(),
            ],
            'active' => [
                'profile' => PhotoResource::collection($photos->where('photo_type', 'profile')->where('is_visible', true)->where('approval_status', 'approved')->values()),
                'album' => PhotoResource::collection($photos->where('photo_type', 'album')->where('is_visible', true)->where('approval_status', 'approved')->values()),
                'family' => PhotoResource::collection($photos->where('photo_type', 'family')->where('is_visible', true)->where('approval_status', 'approved')->values()),
            ],
            'pending' => PhotoResource::collection($photos->where('approval_status', 'pending')->values()),
            'rejected' => PhotoResource::collection($photos->where('approval_status', 'rejected')->values()),
            'archived' => PhotoResource::collection($photos->where('is_visible', false)->values()),
            'privacy' => $profile->photoPrivacySetting ? [
                'gated_premium' => (bool) $profile->photoPrivacySetting->gated_premium,
                'show_watermark' => (bool) $profile->photoPrivacySetting->show_watermark,
                'blur_non_premium' => (bool) $profile->photoPrivacySetting->blur_non_premium,
            ] : null,
        ]);
    }

    /**
     * @authenticated
     * @group Photos
     */
    public function upload(Request $request): JsonResponse
    {
        $data = $request->validate([
            'photo' => 'required|file|mimetypes:image/jpeg,image/png,image/webp,image/heic|max:' . (config('matrimony.max_photo_size_mb', 30) * 1024),
            'photo_type' => 'required|in:profile,album,family',
        ]);

        $profile = $request->user()->profile;
        $quotaMap = [
            'profile' => config('matrimony.max_profile_photos', 1),
            'album' => config('matrimony.max_album_photos', 9),
            'family' => config('matrimony.max_family_photos', 3),
        ];
        $quota = $quotaMap[$data['photo_type']];
        $existing = $profile->profilePhotos()
            ->where('photo_type', $data['photo_type'])
            ->where('is_visible', true)
            ->count();

        if ($existing >= $quota && $data['photo_type'] !== 'profile') {
            return ApiResponse::error(
                'VALIDATION_FAILED',
                "You've reached the {$quota}-photo {$data['photo_type']} limit. Delete or archive one first.",
                fields: ['photo_type' => [__("limit_reached")]],
                status: 422,
            );
        }

        // For profile type: archive previous first
        if ($data['photo_type'] === 'profile') {
            $profile->profilePhotos()
                ->where('photo_type', 'profile')
                ->where('is_visible', true)
                ->update(['is_primary' => false, 'is_visible' => false]);
        }

        $photo = $this->images->processUpload($data['photo'], $profile, $data['photo_type']);

        return ApiResponse::created([
            'photo' => (new PhotoResource($photo, viewer: $profile))->resolve(),
            'needs_approval' => $photo->approval_status === 'pending',
        ]);
    }

    /**
     * @authenticated
     * @group Photos
     */
    public function setPrimary(Request $request, ProfilePhoto $photo): JsonResponse
    {
        abort_if($photo->profile_id !== $request->user()->profile->id, 403);
        abort_if($photo->photo_type !== 'profile', 422, 'Only profile photos can be primary');

        $profile = $request->user()->profile;
        $profile->profilePhotos()->where('photo_type', 'profile')->update(['is_primary' => false]);

        $photo->update(['is_primary' => true]);

        return ApiResponse::ok(['photo_id' => $photo->id, 'is_primary' => true]);
    }

    /**
     * @authenticated
     * @group Photos
     */
    public function destroy(Request $request, ProfilePhoto $photo): JsonResponse
    {
        abort_if($photo->profile_id !== $request->user()->profile->id, 403);

        if ($request->boolean('permanent')) {
            app(\App\Services\PhotoStorageService::class)->delete($photo);
            $photo->delete();
            return ApiResponse::ok(['deleted' => true]);
        }

        $photo->update(['is_visible' => false]);
        return ApiResponse::ok([
            'archived' => true,
            'photo_id' => $photo->id,
            'undo_until' => now()->addDays(30)->toIso8601String(),
        ]);
    }

    /**
     * @authenticated
     * @group Photos
     */
    public function restore(Request $request, ProfilePhoto $photo): JsonResponse
    {
        abort_if($photo->profile_id !== $request->user()->profile->id, 403);
        $photo->update(['is_visible' => true]);
        return ApiResponse::ok(['photo' => (new PhotoResource($photo))->resolve()]);
    }
}
```

### 2. Routes

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/photos', [\App\Http\Controllers\Api\V1\PhotoController::class, 'index']);
    Route::post('/photos', [\App\Http\Controllers\Api\V1\PhotoController::class, 'upload'])->middleware('throttle:20,60');
    Route::post('/photos/{photo}/primary', [\App\Http\Controllers\Api\V1\PhotoController::class, 'setPrimary']);
    Route::delete('/photos/{photo}', [\App\Http\Controllers\Api\V1\PhotoController::class, 'destroy']);
    Route::post('/photos/{photo}/restore', [\App\Http\Controllers\Api\V1\PhotoController::class, 'restore']);
});
```

### 3. Pest test

```php
it('uploads a photo', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('t')->plainTextToken;

    Storage::fake('public');

    $response = postJson('/api/v1/photos', [
        'photo' => UploadedFile::fake()->image('photo.jpg', 800, 1000),
        'photo_type' => 'album',
    ], ['Authorization' => "Bearer $token"]);

    $response->assertCreated()->assertJsonStructure(['data' => ['photo' => ['url'], 'needs_approval']]);
});
```

## Verification

- [ ] Upload → new photo appears in `/photos` response under `active.album`
- [ ] 10th album photo upload returns 422 with clear message
- [ ] `setPrimary` swaps atomically
- [ ] `destroy` without `permanent=1` archives (is_visible=false), with `permanent=1` hard deletes
- [ ] `restore` resets is_visible=true

## Commit

```bash
git add app/Http/Controllers/Api/V1/PhotoController.php routes/api.php tests/Feature/Api/V1/
git commit -m "phase-2a wk-03: step-09 photo CRUD endpoints"
```

## Next step
→ [step-10-photo-privacy-endpoint.md](step-10-photo-privacy-endpoint.md)
