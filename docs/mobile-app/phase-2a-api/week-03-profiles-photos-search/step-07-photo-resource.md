# Step 7 — Complete `PhotoResource` (Absolute URL Contract)

## Goal
Finalize `PhotoResource` so every response returns absolute URLs regardless of storage driver (local/Cloudinary/R2/S3). Flutter's `cached_network_image` needs absolute URLs.

## Prerequisites
- [ ] [step-06 — update profile section](step-06-update-profile-section.md) complete
- [ ] `App\Services\PhotoStorageService` exists (from current codebase)

## Procedure

### 1. Audit `PhotoStorageService::getUrl()`

Read `app/Services/PhotoStorageService.php`. Confirm it:
- Returns absolute URL for local driver (`url('/storage/...')`)
- Returns Cloudinary-built URL for `storage_driver=cloudinary`
- Returns CDN URL for R2/S3

If any path returns relative, **fix it now** (affects web too).

### 2. Complete `PhotoResource`

`app/Http/Resources/V1/PhotoResource.php`:

```php
<?php

namespace App\Http\Resources\V1;

use App\Services\PhotoStorageService;
use Illuminate\Http\Resources\Json\JsonResource;

class PhotoResource extends JsonResource
{
    public function __construct($resource, public ?\App\Models\Profile $viewer = null)
    {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        $svc = app(PhotoStorageService::class);
        $isOwn = $this->viewer && $this->viewer->id === $this->profile_id;
        $isBlurred = ! $isOwn && $this->shouldBlurFor($this->viewer);

        return [
            'id' => $this->id,
            'url' => $svc->getUrl($this->resource, 'full'),
            'thumbnail_url' => $svc->getUrl($this->resource, 'thumbnail'),
            'medium_url' => $svc->getUrl($this->resource, 'medium'),
            'original_url' => $isOwn ? $svc->getUrl($this->resource, 'original') : null,
            'photo_type' => $this->photo_type,
            'is_primary' => (bool) $this->is_primary,
            'is_visible' => (bool) $this->is_visible,
            'is_blurred' => $isBlurred,
            'approval_status' => $this->approval_status,
            'rejection_reason' => $this->when($this->approval_status === 'rejected', $this->rejection_reason),
            'display_order' => $this->display_order ?? 0,
            'storage_driver' => $this->storage_driver,
            'uploaded_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function shouldBlurFor(?\App\Models\Profile $viewer): bool
    {
        if (! $viewer) return true;

        $privacy = $this->profile?->photoPrivacySetting;
        if (! $privacy) return false;

        // gated_premium: hide entirely unless viewer has explicit access grant
        if ($privacy->gated_premium) {
            return ! $this->viewerHasGrant($viewer);
        }

        // blur_non_premium: blur if viewer is free
        if ($privacy->blur_non_premium && ! $viewer->user?->activeMembership) {
            return ! $this->viewerHasGrant($viewer);
        }

        return false;
    }

    private function viewerHasGrant(\App\Models\Profile $viewer): bool
    {
        return \App\Models\PhotoAccessGrant::where('grantor_profile_id', $this->profile_id)
            ->where('grantee_profile_id', $viewer->id)
            ->exists();
    }
}
```

### 3. Test via tinker

```bash
php artisan tinker
>>> $p = \App\Models\ProfilePhoto::first();
>>> (new \App\Http\Resources\V1\PhotoResource($p))->resolve();
# URLs should all start with http://  or https://
```

## Verification

- [ ] All three URL variants (full/thumbnail/medium) are absolute
- [ ] `original_url` is null for non-owners (prevents leaking full-res)
- [ ] `is_blurred` flag correct per privacy setting

## Commit

```bash
git add app/Http/Resources/V1/PhotoResource.php
git commit -m "phase-2a wk-03: step-07 complete PhotoResource with absolute URLs"
```

## Next step
→ [step-08-photo-access-grants.md](step-08-photo-access-grants.md)
