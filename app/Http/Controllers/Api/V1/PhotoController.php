<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Photo\UploadPhotoRequest;
use App\Http\Resources\V1\PhotoResource;
use App\Http\Responses\ApiResponse;
use App\Models\ProfilePhoto;
use App\Models\SiteSetting;
use App\Services\ImageProcessingService;
use App\Services\PhotoStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Photo CRUD for the authenticated user.
 *
 *   GET    /photos                       — list + limits + counts + privacy
 *   POST   /photos                       — upload one photo (multipart)
 *   POST   /photos/{id}/primary          — set as primary (profile type only)
 *   DELETE /photos/{id}                  — soft-delete (archive)
 *   DELETE /photos/{id}/permanent        — hard-delete (wipes storage + row)
 *   POST   /photos/{id}/restore          — un-archive (within undo window)
 *
 * Mirrors App\Http\Controllers\PhotoController (web) endpoint-for-endpoint
 * so users get identical behaviour whether they edit from the browser
 * or the Flutter app. Differences:
 *
 *   - Soft-delete and hard-delete are SEPARATE routes (not a ?permanent=1
 *     query toggle) for a cleaner REST contract.
 *   - Auto-approval still honours site_settings.auto_approve_{type}_photos.
 *   - Per-type slot limit error returns envelope 422 with the same
 *     machine-readable code (VALIDATION_FAILED) and a human message that
 *     Flutter can display verbatim.
 *
 * Design reference:
 *   docs/mobile-app/phase-2a-api/week-03-profiles-photos-search/step-09-photo-crud-endpoints.md
 */
class PhotoController extends BaseApiController
{
    public function __construct(
        private ImageProcessingService $images,
        private PhotoStorageService $storage,
    ) {}

    /* ==================================================================
     |  GET /photos — list everything grouped + limits + counts
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Photos
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "limits": {"max_profile": 1, "max_album": 9, "max_family": 3, "max_size_mb": 5},
     *     "counts": {"profile_used": 1, "album_used": 3, "family_used": 0},
     *     "active": {"profile": [], "album": [], "family": []},
     *     "pending": [],
     *     "rejected": [],
     *     "archived": [],
     *     "privacy": {"privacy_level": "visible_to_all", "profile_photo_privacy": null, "album_photos_privacy": null, "family_photos_privacy": null}
     *   }
     * }
     *
     * @response 401 scenario="unauthenticated" {"success": false, "error": {"code": "UNAUTHENTICATED", "message": "..."}}
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function index(Request $request): JsonResponse
    {
        $profile = $request->user()->profile;
        if (! $profile) {
            return ApiResponse::error(
                'PROFILE_REQUIRED',
                'Complete registration before managing photos.',
                null,
                422,
            );
        }

        // Load relations the resource + privacy block read so the per-photo
        // rendering below doesn't issue N+1 queries.
        $profile->loadMissing(['profilePhotos', 'photoPrivacySetting']);

        $photos = $profile->profilePhotos;
        $activeApproved = fn (string $type) => $photos
            ->where('photo_type', $type)
            ->where('is_visible', true)
            ->where('approval_status', ProfilePhoto::STATUS_APPROVED)
            ->values();

        return ApiResponse::ok([
            'limits' => [
                'max_profile' => ProfilePhoto::maxForType('profile'),
                'max_album' => ProfilePhoto::maxForType('album'),
                'max_family' => ProfilePhoto::maxForType('family'),
                'max_size_mb' => (int) config('matrimony.max_photo_size_mb', 5),
            ],
            'counts' => [
                'profile_used' => $activeApproved('profile')->count(),
                'album_used' => $activeApproved('album')->count(),
                'family_used' => $activeApproved('family')->count(),
            ],
            'active' => [
                'profile' => $this->renderMany($activeApproved('profile'), $profile),
                'album' => $this->renderMany($activeApproved('album'), $profile),
                'family' => $this->renderMany($activeApproved('family'), $profile),
            ],
            'pending' => $this->renderMany(
                $photos->where('approval_status', ProfilePhoto::STATUS_PENDING)->values(),
                $profile,
            ),
            'rejected' => $this->renderMany(
                $photos->where('approval_status', ProfilePhoto::STATUS_REJECTED)->values(),
                $profile,
            ),
            'archived' => $this->renderMany(
                $photos->where('is_visible', false)->values(),
                $profile,
            ),
            'privacy' => $this->privacyBlock($profile),
        ]);
    }

    /* ==================================================================
     |  POST /photos — upload a new photo
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Photos
     *
     * @response 201 scenario="success" {
     *   "success": true,
     *   "data": {"photo": {"id": 1, "url": "...", "is_primary": true}, "needs_approval": false}
     * }
     *
     * @response 422 scenario="validation-failed" {"success": false, "error": {"code": "VALIDATION_FAILED", "message": "...", "fields": {"photo": ["..."]}}}
     * @response 422 scenario="slot-full" {"success": false, "error": {"code": "VALIDATION_FAILED", "message": "Maximum 9 album photos allowed. Delete or archive one first.", "fields": {"photo_type": ["..."]}}}
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function upload(UploadPhotoRequest $request): JsonResponse
    {
        $profile = $request->user()->profile;
        if (! $profile) {
            return ApiResponse::error(
                'PROFILE_REQUIRED',
                'Complete registration before uploading photos.',
                null,
                422,
            );
        }

        $validated = $request->validated();
        $type = $validated['photo_type'];

        // Per-type slot limit check (only for album + family — profile
        // auto-replaces the previous slot below).
        if ($type !== 'profile') {
            $max = ProfilePhoto::maxForType($type);
            $current = $profile->profilePhotos()
                ->visible()
                ->approved()
                ->ofType($type)
                ->count();

            if ($current >= $max) {
                return ApiResponse::error(
                    'VALIDATION_FAILED',
                    "Maximum {$max} {$type} photos allowed. Delete or archive one first.",
                    ['photo_type' => ["Slot limit reached ({$max})."]],
                    422,
                );
            }
        }

        // Auto-approval per site setting (same key the web reads). Defensive:
        // if site_settings is unreachable (test env, DB hiccup), default to
        // auto-approve so the uploader isn't left with a permanently-pending
        // photo from a transient infrastructure problem.
        $autoApprove = true;
        try {
            $autoApprove = SiteSetting::getValue("auto_approve_{$type}_photos", '1') === '1';
        } catch (\Throwable $e) {
            // Fall through to $autoApprove = true default.
        }
        $approvalStatus = $autoApprove
            ? ProfilePhoto::STATUS_APPROVED
            : ProfilePhoto::STATUS_PENDING;

        // Profile type: archive the previous primary BEFORE processing the
        // new file so we don't briefly have two profile photos live.
        if ($type === 'profile') {
            $profile->profilePhotos()
                ->visible()
                ->ofType('profile')
                ->update(['is_visible' => false, 'is_primary' => false]);
        }

        // Pick the active storage driver (defaults to public if the
        // configured one isn't set up — matches web safety net).
        $driver = $this->storage->getActiveDriver();
        if (! $this->storage->isDriverConfigured($driver)) {
            $driver = PhotoStorageService::DRIVER_LOCAL;
        }

        // ImageProcessingService generates 4 variants (original, full,
        // medium, thumb) + applies watermark (if enabled) + returns paths.
        $folder = "photos/{$profile->id}";
        $paths = $this->images->processUpload($request->file('photo'), $folder, $driver);

        // Make this the primary only if it's a profile-type upload AND
        // it auto-approved (an admin-pending photo shouldn't be primary).
        $isPrimary = ($type === 'profile' && $autoApprove);
        if ($isPrimary) {
            $profile->profilePhotos()->update(['is_primary' => false]);
        }

        $nextOrder = (int) ($profile->profilePhotos()->ofType($type)->max('display_order') ?? 0) + 1;

        $photo = ProfilePhoto::create([
            'profile_id' => $profile->id,
            'photo_type' => $type,
            'photo_url' => $paths['full'],
            'thumbnail_url' => $paths['thumb'],
            'medium_url' => $paths['medium'],
            'original_url' => $paths['original'],
            'storage_driver' => $paths['driver'],
            'is_primary' => $isPrimary,
            'is_visible' => true,
            'display_order' => $nextOrder,
            'approval_status' => $approvalStatus,
            'approved_at' => $autoApprove ? Carbon::now() : null,
        ]);

        return ApiResponse::created([
            'photo' => (new PhotoResource($photo, viewer: $profile))->resolve(),
            'needs_approval' => ! $autoApprove,
        ]);
    }

    /* ==================================================================
     |  POST /photos/{id}/primary
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Photos
     *
     * @urlParam id integer required The ProfilePhoto id.
     *
     * @response 200 scenario="success" {"success": true, "data": {"photo_id": 1, "is_primary": true}}
     * @response 403 scenario="not-owner" {"success": false, "error": {"code": "UNAUTHORIZED", "message": "You do not have permission to perform this action."}}
     * @response 404 scenario="not-found" {"success": false, "error": {"code": "NOT_FOUND", "message": "..."}}
     * @response 422 scenario="wrong-type" {"success": false, "error": {"code": "VALIDATION_FAILED", "message": "Only profile-type photos can be primary.", "fields": {"photo_type": ["..."]}}}
     * @response 422 scenario="archived-or-pending" {"success": false, "error": {"code": "VALIDATION_FAILED", "message": "..."}}
     */
    public function setPrimary(Request $request, ProfilePhoto $photo): JsonResponse
    {
        if (($guard = $this->ensureOwns($request, $photo)) !== null) {
            return $guard;
        }

        if ($photo->photo_type !== 'profile') {
            return ApiResponse::error(
                'VALIDATION_FAILED',
                'Only profile-type photos can be primary.',
                ['photo_type' => ["Must be 'profile', got '{$photo->photo_type}'."]],
                422,
            );
        }
        if (! $photo->is_visible) {
            return ApiResponse::error(
                'VALIDATION_FAILED',
                'Cannot set an archived photo as primary. Restore it first.',
                ['is_visible' => ['Photo is archived.']],
                422,
            );
        }
        if (! $photo->isApproved()) {
            return ApiResponse::error(
                'VALIDATION_FAILED',
                'Cannot set an unapproved photo as primary. Wait for moderator approval.',
                ['approval_status' => ['Photo is not approved.']],
                422,
            );
        }

        // Atomic swap: clear all primary flags on this profile, then set
        // this one.
        $request->user()->profile->profilePhotos()->update(['is_primary' => false]);
        $photo->update(['is_primary' => true]);

        return ApiResponse::ok([
            'photo_id' => (int) $photo->id,
            'is_primary' => true,
        ]);
    }

    /* ==================================================================
     |  DELETE /photos/{id} — soft-delete (archive)
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Photos
     *
     * @urlParam id integer required The ProfilePhoto id.
     *
     * @response 200 scenario="archived" {"success": true, "data": {"archived": true, "photo_id": 1, "undo_until": "2026-05-25T..."}}
     * @response 403 scenario="not-owner" {"success": false, "error": {"code": "UNAUTHORIZED", "message": "..."}}
     * @response 404 scenario="not-found" {"success": false, "error": {"code": "NOT_FOUND", "message": "..."}}
     */
    public function destroy(Request $request, ProfilePhoto $photo): JsonResponse
    {
        if (($guard = $this->ensureOwns($request, $photo)) !== null) {
            return $guard;
        }

        $photo->update(['is_visible' => false, 'is_primary' => false]);

        return ApiResponse::ok([
            'archived' => true,
            'photo_id' => (int) $photo->id,
            // 30-day undo window — Flutter can show "Undo" for that long.
            // Hard delete is a separate endpoint, not time-based.
            'undo_until' => Carbon::now()->addDays(30)->toIso8601String(),
        ]);
    }

    /* ==================================================================
     |  DELETE /photos/{id}/permanent — hard-delete + wipe storage
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Photos
     *
     * @urlParam id integer required The ProfilePhoto id.
     *
     * @response 200 scenario="deleted" {"success": true, "data": {"deleted": true, "photo_id": 1}}
     * @response 403 scenario="not-owner" {"success": false, "error": {"code": "UNAUTHORIZED", "message": "..."}}
     * @response 404 scenario="not-found" {"success": false, "error": {"code": "NOT_FOUND", "message": "..."}}
     */
    public function deletePermanent(Request $request, ProfilePhoto $photo): JsonResponse
    {
        if (($guard = $this->ensureOwns($request, $photo)) !== null) {
            return $guard;
        }

        // Wipe all 4 storage variants from the driver this photo lives on
        // (hybrid mode — different photos can live on different drivers).
        $driver = $photo->storage_driver ?: PhotoStorageService::DRIVER_LOCAL;
        $this->images->deleteVariants($photo->getAllStoragePaths(), $driver);

        $photoId = (int) $photo->id;
        $photo->delete();

        return ApiResponse::ok([
            'deleted' => true,
            'photo_id' => $photoId,
        ]);
    }

    /* ==================================================================
     |  POST /photos/{id}/restore — un-archive
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Photos
     *
     * @urlParam id integer required The ProfilePhoto id.
     *
     * @response 200 scenario="restored" {"success": true, "data": {"photo": {}}}
     * @response 403 scenario="not-owner" {"success": false, "error": {"code": "UNAUTHORIZED", "message": "..."}}
     * @response 404 scenario="not-found" {"success": false, "error": {"code": "NOT_FOUND", "message": "..."}}
     * @response 422 scenario="slot-full" {"success": false, "error": {"code": "VALIDATION_FAILED", "message": "Cannot restore: slot limit reached."}}
     */
    public function restore(Request $request, ProfilePhoto $photo): JsonResponse
    {
        if (($guard = $this->ensureOwns($request, $photo)) !== null) {
            return $guard;
        }

        // Restoring counts against the active quota — reject if full.
        $profile = $request->user()->profile;
        $type = $photo->photo_type;
        $max = ProfilePhoto::maxForType($type);
        $current = $profile->profilePhotos()->visible()->approved()->ofType($type)->count();

        if ($current >= $max) {
            return ApiResponse::error(
                'VALIDATION_FAILED',
                "Cannot restore: maximum {$max} {$type} photos already active.",
                ['photo_type' => ["Slot limit reached ({$max})."]],
                422,
            );
        }

        $photo->update(['is_visible' => true]);

        // If no primary exists on this profile, auto-promote the restored
        // photo (matches web's restore() behaviour).
        $hasPrimary = $profile->profilePhotos()
            ->where('is_primary', true)
            ->where('is_visible', true)
            ->approved()
            ->exists();
        if (! $hasPrimary && $photo->isApproved()) {
            $photo->update(['is_primary' => true]);
        }

        return ApiResponse::ok([
            'photo' => (new PhotoResource($photo, viewer: $profile))->resolve(),
        ]);
    }

    /* ==================================================================
     |  Private helpers
     | ================================================================== */

    /**
     * Guard: returns null when the auth'd profile owns the photo, or a
     * 403 envelope response when it doesn't. Caller returns the guard
     * when non-null.
     */
    private function ensureOwns(Request $request, ProfilePhoto $photo): ?JsonResponse
    {
        $profile = $request->user()->profile;

        if (! $profile) {
            return ApiResponse::error(
                'PROFILE_REQUIRED',
                'Complete registration before managing photos.',
                null,
                422,
            );
        }

        if ($photo->profile_id !== $profile->id) {
            return ApiResponse::error(
                'UNAUTHORIZED',
                'You do not have permission to modify this photo.',
                null,
                403,
            );
        }

        return null;
    }

    /**
     * Render a Collection of ProfilePhoto via PhotoResource with the
     * viewer set to the owner (so original_url is populated and
     * is_blurred is false — it's their own photos).
     */
    private function renderMany($photos, $viewer): array
    {
        return $photos
            ->map(fn (ProfilePhoto $p) => (new PhotoResource($p, viewer: $viewer))->resolve())
            ->values()
            ->all();
    }

    /**
     * Shape the privacy block in the GET /photos response. Uses the
     * existing photo_privacy_settings schema (privacy_level enum +
     * per-type strings). The step-10 photo-privacy endpoint will be the
     * write-side counterpart for this block.
     *
     * Returns null when the row doesn't exist yet — Flutter treats that
     * as "privacy not yet configured, show defaults."
     */
    private function privacyBlock($profile): ?array
    {
        $pp = $profile->photoPrivacySetting;
        if (! $pp) {
            return null;
        }

        return [
            'privacy_level' => $pp->privacy_level,
            'profile_photo_privacy' => $pp->profile_photo_privacy ?? null,
            'album_photos_privacy' => $pp->album_photos_privacy ?? null,
            'family_photos_privacy' => $pp->family_photos_privacy ?? null,
        ];
    }
}
