<?php

namespace App\Http\Resources\V1;

use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Support\PhotoVisibility;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Photo shape for the mobile API.
 *
 * Privacy uses App\Support\PhotoVisibility::gate() — the SAME rule as the
 * website, per photo type (profile / album / family):
 *   - visible → absolute URLs as normal, is_blurred false, lock_reason null
 *   - locked  → url / thumbnail_url / medium_url / original_url are ALL
 *               null, is_blurred true, lock_reason 'hidden' |
 *               'after_acceptance'. Nothing of the image is sent: the app
 *               shows a placeholder ("This photo is hidden" + request
 *               button, or "Visible after acceptance"). Previously the real
 *               URLs were always sent and the app was merely asked to blur.
 *
 * $viewer defaults to the authenticated member, so a caller that forgets
 * to pass one gets that member's view — never an accidental "guest" that
 * would lock photos they're entitled to (e.g. after an accepted interest).
 *
 * UI-safe API contract points this class enforces:
 *   1. Timestamps → ISO 8601 (created_at / approved_at / uploaded_at)
 *   2. Booleans   → real bool (is_primary, is_visible, is_blurred)
 *   5. Photo URLs → absolute when present (via ProfilePhoto accessor
 *                   methods, which call Storage::disk($driver)->url($path));
 *                   null when the viewer may not see the photo
 *
 * Design references:
 *   - docs/mobile-app/reference/ui-safe-api-checklist.md
 *   - docs/mobile-app/design/05-photo-api.md
 */
class PhotoResource extends JsonResource
{
    /**
     * @param  ProfilePhoto  $resource
     * @param  Profile|null  $viewer  Who is looking. Defaults to the authenticated member.
     * @param  Profile|null  $owner   The photo's owner, if the caller already has it (saves a query per photo).
     */
    public function __construct($resource, public ?Profile $viewer = null, public ?Profile $owner = null)
    {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        /** @var ProfilePhoto $photo */
        $photo = $this->resource;

        $viewer = $this->viewer ?? auth()->user()?->profile;
        $isOwn = $viewer !== null && $viewer->id === $photo->profile_id;
        $lockReason = $isOwn ? null : $this->lockReason($viewer);
        $locked = $lockReason !== null;

        return [
            'id'                => (int) $photo->id,
            'photo_type'        => (string) $photo->photo_type,
            'url'               => $locked ? null : $photo->full_url,     // absolute via accessor
            'thumbnail_url'     => $locked ? null : $photo->thumb_url,    // absolute via accessor
            'medium_url'        => $locked ? null : $photo->medium_url,   // absolute via accessor
            'original_url'      => $isOwn ? $photo->original_full_url : null,  // only owner sees original
            'is_primary'        => (bool) $photo->is_primary,
            'is_visible'        => (bool) $photo->is_visible,
            'is_blurred'        => $locked,
            'lock_reason'       => $lockReason,
            'approval_status'   => (string) $photo->approval_status,
            'rejection_reason'  => $photo->approval_status === ProfilePhoto::STATUS_REJECTED
                                     ? ($photo->rejection_reason ?? null)
                                     : null,
            'display_order'     => (int) ($photo->display_order ?? 0),
            'storage_driver'    => (string) ($photo->storage_driver ?? 'public'),
            'approved_at'       => $photo->approved_at?->toUtcIso(),
            'created_at'        => $photo->created_at?->toUtcIso(),
        ];
    }

    /**
     * null when the viewer may see this photo, otherwise why not:
     * 'hidden' or 'after_acceptance'. Fails CLOSED — if the owner or the
     * privacy lookups can't be resolved, the photo is treated as hidden:
     * a privacy check must never fail open.
     */
    private function lockReason(?Profile $viewer): ?string
    {
        /** @var ProfilePhoto $photo */
        $photo = $this->resource;

        try {
            $owner = $this->owner ?? $photo->profile;
            if (! $owner) {
                return PhotoVisibility::HIDDEN;
            }

            $state = PhotoVisibility::gate($owner, $viewer, (string) $photo->photo_type);
        } catch (\Throwable) {
            return PhotoVisibility::HIDDEN;
        }

        return $state === PhotoVisibility::VISIBLE ? null : $state;
    }
}
