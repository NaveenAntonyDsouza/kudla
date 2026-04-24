<?php

namespace App\Http\Resources\V1;

use App\Models\Profile;
use App\Models\ProfilePhoto;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Photo shape for the mobile API.
 *
 * Step 1 ships a MINIMAL version: absolute URLs (from model accessors)
 * + a stub `is_blurred` that's true only for viewers with no explicit
 * privacy grant. The full 7-gate privacy logic lands in Week 3 step-7
 * when we build the photo CRUD endpoints.
 *
 * UI-safe API contract points this class enforces:
 *   1. Timestamps → ISO 8601 (created_at / approved_at / uploaded_at)
 *   2. Booleans   → real bool (is_primary, is_visible, is_blurred)
 *   5. Photo URLs → always absolute (via ProfilePhoto accessor methods,
 *                   which call Storage::disk($driver)->url($path))
 *
 * Design references:
 *   - docs/mobile-app/reference/ui-safe-api-checklist.md
 *   - docs/mobile-app/design/05-photo-api.md
 */
class PhotoResource extends JsonResource
{
    /**
     * @param  ProfilePhoto  $resource
     * @param  Profile|null  $viewer  The user viewing this photo. null for own-profile / public view.
     */
    public function __construct($resource, public ?Profile $viewer = null)
    {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        /** @var ProfilePhoto $photo */
        $photo = $this->resource;

        $isOwn = $this->viewer !== null && $this->viewer->id === $photo->profile_id;
        $isBlurred = ! $isOwn && $this->shouldBlurFor($this->viewer);

        return [
            'id'                => (int) $photo->id,
            'photo_type'        => (string) $photo->photo_type,
            'url'               => $photo->full_url,          // absolute via accessor
            'thumbnail_url'     => $photo->thumb_url,         // absolute via accessor
            'medium_url'        => $photo->medium_url,        // absolute via accessor
            'original_url'      => $isOwn ? $photo->original_full_url : null,  // only owner sees original
            'is_primary'        => (bool) $photo->is_primary,
            'is_visible'        => (bool) $photo->is_visible,
            'is_blurred'        => $isBlurred,
            'approval_status'   => (string) $photo->approval_status,
            'rejection_reason'  => $photo->approval_status === ProfilePhoto::STATUS_REJECTED
                                     ? ($photo->rejection_reason ?? null)
                                     : null,
            'display_order'     => (int) ($photo->display_order ?? 0),
            'storage_driver'    => (string) ($photo->storage_driver ?? 'public'),
            'approved_at'       => $photo->approved_at?->toIso8601String(),
            'created_at'        => $photo->created_at?->toIso8601String(),
        ];
    }

    /**
     * Stub — returns true only if the viewing profile is NOT the owner
     * AND the photo owner has `blur_non_premium` set AND the viewer is
     * not premium. Full 7-gate logic (gated_premium, photo_access_grants,
     * etc.) lands in step-7.
     */
    private function shouldBlurFor(?Profile $viewer): bool
    {
        if ($viewer === null) {
            return true;  // public / anonymous viewers always see blurred
        }

        /** @var ProfilePhoto $photo */
        $photo = $this->resource;
        $privacy = $photo->profile?->photoPrivacySetting;

        if (! $privacy) {
            return false;  // no privacy row = default visible
        }

        if (($privacy->blur_non_premium ?? false) && ! ($viewer->user?->isPremium() ?? false)) {
            return true;
        }

        return false;
    }
}
