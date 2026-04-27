# 5. Photo API

Covers: upload (multipart), list, set primary, archive/delete, privacy toggles, photo request lifecycle.

**Source:** `App\Http\Controllers\PhotoController`, `App\Http\Controllers\PhotoRequestController`, `App\Services\PhotoStorageService`, `App\Services\ImageProcessingService`, `App\Services\WatermarkService`, `App\Services\CloudinaryService`.

---

## 5.1 Multi-driver storage — recap

Drivers (set via admin → Site Settings → Photo Storage):
- `public` — Local disk under `storage/app/public/photos/`
- `cloudinary` — Cloudinary URLs (signed, CDN)
- `r2` — Cloudflare R2 (S3-compatible, via `league/flysystem-aws-s3-v3`)
- `s3` — AWS S3

**Hybrid mode:** new photos go to `active_storage_driver`; old photos stay where they are (`storage_driver` column on `profile_photos`). `PhotoStorageService::getUrl(ProfilePhoto $p)` picks the right URL builder per row.

**Absolute URL contract:** every Photo API response resolves URLs through `PhotoStorageService::getUrl()`, guaranteed absolute. Applies via `PhotoResource`.

---

## 5.2 Photo types & limits

From `config/matrimony.php`:

| Type | Max | Notes |
|------|-----|-------|
| `profile` | 1 | Auto-archives previous when new uploaded |
| `album` | 9 | |
| `family` | 3 | |

Max file size: 30 MB (config says 30; existing web UI says 5; **reconcile** — API should honour config value, which is 30).

---

## 5.3 `GET /api/v1/photos`

Returns all photos for authenticated user, grouped by status and type.

**Response:**
```json
{
  "success": true,
  "data": {
    "limits": {
      "max_profile": 1, "max_album": 9, "max_family": 3, "max_size_mb": 30
    },
    "counts": {
      "profile_used": 1, "album_used": 3, "family_used": 0
    },
    "active": {
      "profile": [ /* PhotoResource */ ],
      "album": [ /* up to 9 */ ],
      "family": [ /* up to 3 */ ]
    },
    "pending": [],       // uploaded, awaiting admin approval
    "rejected": [],      // rejected by admin, user can see reason
    "archived": [],      // soft-deleted (is_visible=false), can restore
    "privacy": {
      "gated_premium": false,
      "show_watermark": true,
      "blur_non_premium": false
    }
  }
}
```

### PhotoResource shape
```json
{
  "id": 1247,
  "url": "https://.../photos/p_1247.webp",
  "thumbnail_url": "https://.../photos/p_1247_thumb.webp",
  "medium_url": "https://.../photos/p_1247_medium.webp",
  "original_url": "https://.../photos/p_1247_orig.jpg",
  "photo_type": "profile",
  "is_primary": true,
  "is_visible": true,
  "approval_status": "approved",    // pending | approved | rejected
  "rejection_reason": null,         // string if rejected
  "display_order": 0,
  "storage_driver": "cloudinary",
  "uploaded_at": "2026-04-20T10:30:00Z"
}
```

---

## 5.4 `POST /api/v1/photos` — Upload

**Content-Type:** `multipart/form-data`

**Fields:**
- `photo` (file, required) — JPEG/PNG/WEBP/HEIC, max 30 MB
- `photo_type` (string, required) — `profile | album | family`

**Behaviour:**
1. Validates file type + size + per-type quota.
2. `ImageProcessingService::processUpload($file, $profile, $photo_type)`:
   - Applies watermark (if enabled in SiteSetting) via `WatermarkService`
   - Generates variants: thumbnail (150×150), medium (400×400), full (800×1200 max)
   - All variants converted to WebP (25–35% smaller than JPEG)
3. Stores on `active_storage_driver`.
4. Creates `ProfilePhoto` row with `storage_driver`, `photo_url`, `thumbnail_url`, `medium_url`, `original_url`.
5. Auto-approves if `auto_approve_{type}_photos` site setting is `1`, else pending.
6. If `photo_type = profile`, archives previous profile photo (sets `is_visible=false`, `is_primary=false`) and sets new one as primary.

**Rate limit:** 20 uploads/hour/user.

**Response 201:**
```json
{
  "success": true,
  "data": {
    "photo": { /* PhotoResource */ },
    "needs_approval": false           // true if pending
  }
}
```

**Error 422 on quota:**
```json
{ "success": false, "error": { "code": "VALIDATION_FAILED", "message": "You've reached the 9-photo album limit. Delete or archive one first.", "fields": {"photo_type": ["..."]} } }
```

**Error 422 on bad file:**
```json
{ "success": false, "error": { "code": "VALIDATION_FAILED", "fields": { "photo": ["File must be JPEG, PNG, WEBP or HEIC."] } } }
```

---

## 5.5 `POST /api/v1/photos/{photo}/primary`

Set a photo as primary. Only allowed on `photo_type = profile`.

**Response:**
```json
{
  "success": true,
  "data": {
    "photo_id": 1247,
    "is_primary": true,
    "previously_primary_id": 1180
  }
}
```

**Business rule:** exactly one photo per profile can be primary. This endpoint atomically toggles.

---

## 5.6 `DELETE /api/v1/photos/{photo}`

Soft-delete (archives). Sets `is_visible = false`. Photo remains in DB for 30 days then hard-deleted by a scheduled job.

**Response:**
```json
{ "success": true, "data": { "archived": true, "photo_id": 1247, "undo_until": "2026-05-23T00:00:00Z" } }
```

### `POST /api/v1/photos/{photo}/restore`

Un-archives. Resets `is_visible = true`. Returns refreshed PhotoResource.

### `DELETE /api/v1/photos/{photo}?permanent=1`

Hard delete — removes from storage immediately. No undo.

---

## 5.7 `POST /api/v1/photos/privacy`

Update photo privacy settings (applies to all photos of the user).

**Request:**
```json
{
  "gated_premium": true,           // photos only visible to premium viewers
  "show_watermark": true,          // diagonal watermark on all photos (server-applied on upload)
  "blur_non_premium": false        // blurred preview for non-premium viewers
}
```

**Response:**
```json
{ "success": true, "data": { "privacy": { /* echoed back */ } } }
```

**Note on `show_watermark`:** this is a server-side setting that affects **new** uploads only. Existing photos already have or lack a watermark baked in — changing this setting doesn't retroactively modify them. Flutter UI should warn the user of this.

---

## 5.8 Photo Requests

When a profile has `gated_premium = true` or `blur_non_premium = true`, viewers see a "Request Photos" button. This sends a `PhotoRequest`.

### `POST /api/v1/profiles/{matriId}/photo-request`

**Request:**
```json
{ "message": "Hi, I'd love to see your photos. Looking forward to connecting!" }
```

- `message` optional, max 300 chars
- Cannot request from self (400)
- Cannot request from same gender (403 `GENDER_MISMATCH`)
- Cannot re-request if pending or approved exists (409 `ALREADY_EXISTS`)

**Response 201:**
```json
{ "success": true, "data": { "request_id": 89, "status": "pending", "expires_at": "2026-05-23T00:00:00Z" } }
```

### `GET /api/v1/photo-requests`

Returns all photo requests for the authenticated user.

**Response:**
```json
{
  "success": true,
  "data": {
    "received": [
      {
        "id": 89,
        "requester": { /* ProfileCardResource */ },
        "message": "Hi, I'd love to see...",
        "status": "pending",
        "created_at": "2026-04-22T10:00:00Z",
        "expires_at": "2026-05-23T00:00:00Z"
      }
    ],
    "sent": [ /* same shape, different key */ ]
  }
}
```

### `POST /api/v1/photo-requests/{photoRequest}/approve`

Grants requester access to full (non-blurred) photos for this viewer-target pair.

**Response:**
```json
{ "success": true, "data": { "approved": true, "requester_notified": true } }
```

**Implementation:** creates a `photo_access_grants` row (new table) keyed on `(grantor_profile_id, grantee_profile_id)`. `ProfileAccessService` checks this when serving photo URLs to determine `is_blurred` flag.

### `POST /api/v1/photo-requests/{photoRequest}/ignore`

Marks request as ignored. Requester is NOT notified (by design — don't encourage repeat pinging).

**Response:** `{"success": true, "data": {"ignored": true}}`

---

## 5.9 Absolute URL behaviour summary

Every endpoint in this doc returns photo URLs via `PhotoResource`, which uses `PhotoStorageService::getUrl($photo, $variant)`. Result:

- Local driver: `https://kudlamatrimony.com/storage/photos/p_1247.webp` (via `url()` helper)
- Cloudinary: `https://res.cloudinary.com/.../p_1247.webp` (Cloudinary's own URL builder)
- R2/S3: `https://cdn.kudlamatrimony.com/photos/p_1247.webp` (configured CDN domain)

Flutter `cached_network_image` works with all three without changes.

---

## 5.10 Orientation & EXIF

Server strips EXIF on processing (privacy — removes GPS coords baked into phone photos). Orientation is normalized by `ImageProcessingService` before resizing. Flutter can safely render without rotation metadata handling.

---

## 5.11 HEIC support

iPhone photos arrive as HEIC. Laravel's `Intervention\Image` v4 (already installed) supports HEIC via the `imagick` PHP extension. **Check Hostinger:** if imagick is disabled, fall back to accepting HEIC client-side only — Flutter converts to JPEG before upload using `flutter_image_compress`. Hostinger typically has imagick available; verify early.

---

## 5.12 Build Checklist

- [ ] `App\Http\Resources\V1\PhotoResource` with absolute URL resolution
- [ ] `App\Http\Controllers\Api\V1\PhotoController`:
  - [ ] `index()` → list all photos grouped
  - [ ] `upload()` → multipart, dispatches to ImageProcessingService
  - [ ] `setPrimary(ProfilePhoto $photo)` → atomic swap
  - [ ] `destroy(ProfilePhoto $photo)` → soft delete
  - [ ] `restore(ProfilePhoto $photo)`
  - [ ] `updatePrivacy()` → writes PhotoPrivacySetting
  - [ ] `listRequests()` / `sendRequest()` / `approveRequest()` / `ignoreRequest()`
- [ ] Migration: `photo_access_grants` table (`grantor_profile_id`, `grantee_profile_id`, `created_at`) — unique(grantor+grantee)
- [ ] `App\Services\PhotoAccessService::hasAccess($viewer, $target): bool` → checks grants + premium + privacy settings
- [ ] Scheduled job: `photos:hard-delete-archived` — runs daily, removes photos with `is_visible=false AND updated_at < now()-30days`
- [ ] Rate limits: `throttle:20,60` on upload endpoint
- [ ] Pest tests: upload + quota + variant generation + privacy gate + photo request lifecycle

**Acceptance:**
- Upload a 10 MB JPEG → WebP variants on disk/CDN + DB row + absolute URLs in response
- Upload 10th album photo → 422 with clear "9-photo limit" message
- Non-premium viewer fetches a `gated_premium = true` profile → photos have `is_blurred: true`
- Photo request approve → viewer refetches profile and sees `is_blurred: false`
