# API changes — September 2026 (photo privacy, photo requests, settings, notifications)

Made while the Flutter app was still at Week 1 (no screens consume these yet),
so they are contract changes, not breaking changes. **Build the app to this
document**; where the phase-2a step docs (`week-03 … step-07/08/10/11`,
`week-04 … step-08`) disagree, this wins — those are historical build logs.

The website had the same privacy bugs and was fixed first (commit `e4b16b2`);
the API now uses the same rule.

---

## 1. Photo privacy — one rule, and locked photos carry no image

`App\Support\PhotoVisibility` decides whether a viewer may see a member's
photos — for the website **and** the API. Per photo type (`profile`, `album`,
`family`), using that type's privacy level:

| Level | Visible to |
|---|---|
| `visible_to_all` | everyone (incl. guests) |
| `interest_accepted` | members with an accepted interest with them (either direction) |
| `hidden` | members whose photo request they **approved** |

The owner always sees their own photos.

### Photo object (`PhotoResource`) — 16 keys

New: **`lock_reason`**. When the viewer may not see the photo:

```json
{ "url": null, "thumbnail_url": null, "medium_url": null, "original_url": null,
  "is_blurred": true, "lock_reason": "hidden" }
```

`lock_reason` is `null` (visible) · `"hidden"` · `"after_acceptance"`.
**No URL of a locked photo is ever sent** — render a placeholder, not a blur
of the real image. (Before: real URLs were always sent and `is_blurred` only
*asked* the app to blur them.) Unknown privacy fails closed (`"hidden"`).

Card photos (`primary_photo` on profile cards) carry the same `lock_reason`.

### Profile `photos` block

```json
"photos": {
  "profile": [ …photo objects… ],
  "album":   [ … ],            // locked ones keep their slot, URLs null
  "family":  [ … ],
  "photo_privacy": { "profile": "hidden", "album": "interest_accepted", "family": "visible_to_all" },
  "photo_access": {
    "profile": "hidden",        // visible | hidden | after_acceptance | no_photo
    "album": "visible",         // visible | hidden | after_acceptance
    "family": "after_acceptance",
    "request_status": null,     // this viewer's request to them: pending | approved | ignored | null
    "can_request": true
  }
}
```

- `photo_privacy` — **own profile only** (null otherwise): the member's
  settings. Replaces `gated_premium` / `show_watermark` / `blur_non_premium`,
  which never existed in the database and always read `false`.
- `photo_access` — **someone else's profile only** (null on your own).
  `can_request` is true when something is hidden (or there's no photo) and no
  pending/approved request exists — exactly when
  `POST /profiles/{matriId}/photo-request` would succeed. Show the button
  from this flag.

UI mapping (same as the website):

| `photo_access.profile` | Show |
|---|---|
| `visible` | the photo |
| `hidden` | lock placeholder, "This photo is hidden", **Send view request** (if `can_request`; else "Request sent") |
| `after_acceptance` | lock placeholder, "Visible only after acceptance" |
| `no_photo` | silhouette, **Request photo** (if `can_request`) |

---

## 2. Photo requests

- Notifications name the other member by **Matri ID only**, never their full
  name (the API used to send the full name; the website never did).
- Two versions, chosen by whether the member asked has a photo:
  - has one (hidden) → *"AM100123 has requested to see your photos."* — `data.kind = "view"`
  - none yet → *"AM100123 would like you to add a photo."* — `data.kind = "upload"`
- `POST /photo-requests/{id}/approve` returns **422 `PHOTO_REQUIRED`** when the
  approving member has no photo — for an `upload` request, route them to the
  upload screen instead of showing Approve.
- When a member adds a photo that someone asked for, each requester who can
  now see it gets an in-app `photo_added` notification + email, and their
  request becomes `approved` automatically.
- Emails for all of this are sent server-side (model hooks) — the app does
  nothing extra.
- `photo_access_grants` rows are still written on approve, **as a record
  only**. Never use them for visibility — see `PhotoAccessService`.

---

## 3. `GET /site/settings` additions

| Path | Type | Meaning |
|---|---|---|
| `features.free_membership` | bool | Free Membership mode — everyone is premium. **Hide plans / paywall.** |
| `registration.caste_required` | bool | Caste/Community required for Hindu members (off on dating sites) |
| `registration.show_diocese` | bool | Show the Diocese field for Christians |
| `registration.id_prefix` | string | Now from the setting that actually generates member IDs (was the unused `.env` value) |
| `labels.gender_male` / `labels.gender_female` | string | "Groom"/"Bride" on matrimony sites, e.g. "A Man"/"A Woman" on dating sites |

Admin changes to any setting now clear the cached snapshot immediately
(previously up to 5 minutes stale).

---

## 4. Notifications — icons for every type + a `target`

Each notification now has **`target`**: `{ "screen", "id", "section" }` or
`null`. The server owns the type → screen mapping, so new types don't need an
app release. Stable screen keys:

| `target.screen` | `id` | Used by |
|---|---|---|
| `interest` | interest id | interest_received / accepted / declined (falls back to `interests`) |
| `profile` | profile id | photo_request_approved, photo_added, profile_view |
| `photo_requests` | — | photo_request |
| `my_photos` | — | photo_approved, photo_rejected |
| `my_profile` | — (`section` = part to complete, for nudges) | profile_changes_requested, profile nudges (`system` + `data.nudge_type`) |
| `my_documents` | — | id_proof_approved / rejected, document_approved / rejected |
| `membership` | — | membership_expiring / expired, plan_changed |
| *(null)* | | admin_broadcast, plain system messages |

`icon_type` now covers every type: `interest` · `profile` · `photo` ·
`verified` · `membership` · `bell`.

Push payloads already carry `notification_id` + `type`; on tap, fetch the
notification (or map `type` the same way) to get the target.

---

## Tests

`tests/Feature/Api/V1/PhotoResourceTest.php`,
`PhotoRequestControllerTest.php`, `AppPhotoAndSettingsContractTest.php`,
`tests/Feature/PhotoVisibilityTest.php`, `MemberEmailsTest.php`.
