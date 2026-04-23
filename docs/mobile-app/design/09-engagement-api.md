# 9. Engagement API

Covers the supporting endpoints: notifications, shortlist, who-viewed-me, block, report, ignore, id-proof upload, success stories, contact form, static pages, reference data (cascading selects), site settings (theme).

Not the headline features, but without these the app has dead buttons.

---

## 9.1 Notifications

**Source:** `App\Http\Controllers\NotificationController`, `App\Models\Notification`.

### `GET /api/v1/notifications`

Paginated list.

**Query:** `page=1&per_page=20&filter=all`  (filter: `all|unread`)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 5421,
      "type": "interest.received",
      "title": "Priya sent you an interest",
      "body": "Tap to view their profile",
      "data": {
        "interest_id": 89,
        "sender_matri_id": "AM100087",
        "deep_link": "/interests/89"
      },
      "is_read": false,
      "created_at": "2026-04-23T14:00:00Z",
      "icon_type": "interest"        // UI renders icon per type
    }
  ],
  "meta": {
    "page": 1, "per_page": 20, "total": 47, "last_page": 3,
    "unread_count": 5
  }
}
```

### Notification types (catalogue)

| `type` | Triggered by | Deep link |
|--------|--------------|-----------|
| `interest.received` | Someone sends interest | `/interests/{id}` |
| `interest.accepted` | Your interest accepted | `/interests/{id}` |
| `interest.declined` | Your interest declined | `/interests/{id}` |
| `interest.expired` | Pending interest 30d old | `/interests/{id}` |
| `interest.reply` | New reply in accepted thread | `/interests/{id}` |
| `photo_request.received` | Photo request | `/photo-requests` |
| `photo_request.approved` | Your photo request approved | `/profiles/{matri_id}` |
| `profile.viewed` | Someone viewed your profile | `/views` (premium only) |
| `profile.shortlisted` | Someone shortlisted you | `/profile/me` |
| `match.new` | Daily/weekly match suggestion | `/matches/my` |
| `membership.expiring` | Membership expires in 3 days | `/membership` |
| `membership.expired` | Membership expired | `/membership` |
| `profile.approved` | Admin approved your profile | `/dashboard` |
| `profile.rejected` | Admin rejected your profile | `/dashboard` |
| `id_proof.verified` | ID verified | `/settings` |
| `id_proof.rejected` | ID rejected | `/settings` |
| `admin.broadcast` | Admin announcement | `data.deep_link` (custom) |
| `saved_search.new_matches` | New matches in saved search | `/search?saved_search_id={id}` |

### `POST /api/v1/notifications/{notification}/read`

Marks single as read. Returns updated notification.

### `POST /api/v1/notifications/read-all`

Marks all user notifications as read.

**Response:** `{"success": true, "data": {"marked_read": 47}}`

### `GET /api/v1/notifications/unread-count`

Lightweight — just the number. Called from app shell to badge the bell icon.

**Response:** `{"success": true, "data": {"unread_count": 5}}`

**Polling:** Flutter polls this every 30s when app is foregrounded. Or, more efficient: piggyback on periodic `/dashboard` calls. Either works.

---

## 9.2 Shortlist

**Source:** `App\Http\Controllers\ShortlistController`.

### `GET /api/v1/shortlist`

**Response:**
```json
{
  "success": true,
  "data": [ /* ProfileCardResource[] */ ],
  "meta": { "total": 23, "page": 1, "per_page": 20 }
}
```

### `POST /api/v1/profiles/{matriId}/shortlist`

Toggles. Body empty.

**Response:**
```json
{ "success": true, "data": { "is_shortlisted": true, "shortlist_count": 24 } }
```

Rules: not self, not same gender, not blocked.

---

## 9.3 Who Viewed My Profile

**Source:** `App\Http\Controllers\ProfileViewController`.

### `GET /api/v1/views`

**Query:** `tab=viewed_by`  (`viewed_by` | `i_viewed`)

**Response (viewed_by):**
```json
{
  "success": true,
  "data": {
    "total_count": 87,               // always shown
    "is_premium": true,              // else list below is empty and total_count is only thing shown
    "viewers": [
      {
        "profile": { /* ProfileCardResource */ },
        "viewed_at": "2026-04-23T13:00:00Z",
        "view_count": 3               // how many times they viewed you
      }
    ]
  },
  "meta": { "page": 1, "per_page": 20, "total": 42 }
}
```

**Free users:** `viewers` array is empty; only `total_count` is shown. UI prompts to upgrade.

**i_viewed:**
```json
{
  "success": true,
  "data": {
    "viewed_profiles": [
      { "profile": { /* card */ }, "viewed_at": "2026-04-23T10:00:00Z" }
    ]
  },
  "meta": { /* pagination */ }
}
```

Free and premium both see their own outgoing history.

---

## 9.4 Block / Unblock

### `GET /api/v1/blocked`

**Response:**
```json
{
  "success": true,
  "data": [ /* ProfileCardResource[] with `blocked_at` timestamp */ ],
  "meta": { /* pagination */ }
}
```

### `POST /api/v1/profiles/{matriId}/block`

**Request:** optional `reason` field (free text, max 500 chars).

**Response:** `{"success": true, "data": {"blocked": true}}`

**Side effects:** any pending interests between them auto-cancelled. Shortlist entries removed. Profile views hidden.

### `POST /api/v1/profiles/{matriId}/unblock`

**Response:** `{"success": true, "data": {"blocked": false}}`

---

## 9.5 Report profile

**Source:** `App\Http\Controllers\ReportController`.

### `POST /api/v1/profiles/{matriId}/report`

**Request:**
```json
{
  "reason": "fake_profile",        // enum: see below
  "description": "Photos are stock images, profile claims to be 28 but looks 45"
}
```

**Reasons (enum):**
- `inappropriate_content`
- `fake_profile`
- `harassment_or_abuse`
- `scam_or_fraud`
- `underage`
- `already_married`
- `offensive_messages`
- `other`

**Response 201:**
```json
{
  "success": true,
  "data": {
    "report_id": 412,
    "status": "pending",
    "message": "Our team will review within 48 hours."
  }
}
```

**Rules:** cannot report self. Cannot submit duplicate pending report for same target.

---

## 9.6 Ignore / Un-ignore

**Source:** `App\Http\Controllers\IgnoredProfileController`.

### `GET /api/v1/ignored`

**Response:**
```json
{
  "success": true,
  "data": [ /* ProfileCardResource[] */ ],
  "meta": { /* pagination */ }
}
```

### `POST /api/v1/profiles/{matriId}/ignore-toggle`

Toggles ignored status. Ignored profiles are hidden from search/discover/matches.

**Response:** `{"success": true, "data": {"is_ignored": true}}`

---

## 9.7 ID Proof

**Source:** `App\Http\Controllers\IdProofController`.

### `GET /api/v1/id-proof`

**Response:**
```json
{
  "success": true,
  "data": {
    "id_proof": {
      "id": 123,
      "document_type": "Aadhaar Card",
      "document_number": "XXXX-XXXX-1234",           // masked
      "front_url": "https://.../id-proofs/123_front.jpg",   // absolute
      "back_url": "https://.../id-proofs/123_back.jpg",
      "verification_status": "pending",              // pending | verified | rejected
      "rejection_reason": null,
      "submitted_at": "2026-04-20T10:00:00Z",
      "verified_at": null
    },
    "accepted_types": ["Passport", "Voter ID", "Aadhaar Card", "Driving License", "PAN Card"]
  }
}
```

**Null id_proof** if never uploaded: `data.id_proof: null`.

### `POST /api/v1/id-proof`

**Content-Type:** `multipart/form-data`

**Fields:**
- `document_type` (required, enum)
- `document_number` (required, string, per-type format validation)
- `front` (required, file, JPG/PNG/PDF, max 5 MB)
- `back` (optional, same)

**Response 201:** same shape as GET, with verification_status=`pending`.

**Side effect:** deletes any existing id_proof record first (one active at a time).

### `DELETE /api/v1/id-proof/{idProof}`

User can delete their own submission. Can't delete admin's `verified` ones (deters spam).

---

## 9.8 Settings

**Source:** `App\Http\Controllers\SettingsController`.

### `GET /api/v1/settings`

**Response:**
```json
{
  "success": true,
  "data": {
    "visibility": {
      "show_profile_to": "all",           // all | premium | matches
      "only_same_religion": false,
      "only_same_denomination": false,
      "only_same_mother_tongue": false,
      "is_hidden": false
    },
    "alerts": {
      "email_interest": true,
      "email_accepted": true,
      "email_declined": false,
      "email_views": true,
      "email_promotions": false,
      "push_interest": true,
      "push_accepted": true,
      "push_declined": false,
      "push_views": false,
      "push_promotions": false
    },
    "auth": {
      "has_password": true,
      "biometric_enrolled": false         // client-side state, server just reflects
    },
    "account": {
      "email": "naveen@example.com",
      "phone": "9876543210",
      "email_verified": true,
      "phone_verified": true
    }
  }
}
```

### `PUT /api/v1/settings/visibility`

**Request:**
```json
{
  "show_profile_to": "premium",
  "only_same_religion": true,
  "only_same_denomination": false,
  "only_same_mother_tongue": false
}
```

### `PUT /api/v1/settings/alerts`

**Request:** subset of keys from `alerts` object above. Only included keys are updated (PATCH-style).

```json
{ "email_interest": false, "push_views": true }
```

### `PUT /api/v1/settings/password`

**Request:**
```json
{
  "current_password": "old-secret",
  "new_password": "new-secret",
  "new_password_confirmation": "new-secret"
}
```

**Response:** `{"success": true, "data": {"password_changed": true, "tokens_revoked_count": 2}}`

**Side effect:** revokes all other tokens (force re-login on other devices). Keeps current token alive.

### `POST /api/v1/settings/hide`

Temporarily hides profile from search/discover. Existing accepted interests remain active.

**Response:** `{"success": true, "data": {"is_hidden": true}}`

### `POST /api/v1/settings/unhide`

**Response:** `{"success": true, "data": {"is_hidden": false}}`

### `POST /api/v1/settings/delete`

**Request:**
```json
{
  "password": "secret123",            // confirm
  "reason": "found_partner",          // enum: found_partner | poor_experience | not_interested | other
  "feedback": "Optional feedback text"
}
```

**Response:** `{"success": true, "data": {"deleted": true, "logged_out": true}}`

**Soft-delete behaviour:**
- `profiles.is_active = false, is_hidden = true, deletion_reason = ...`
- All personal access tokens revoked (user is logged out everywhere)
- Grace period: 30 days, user can re-login within window to reactivate (sends magic link on attempt)
- After 30 days: scheduled job hard-deletes profile + PII (subject to GDPR retention rules if we expand internationally)

---

## 9.9 Reference Data (cascading selects)

**Source:** `App\Services\ReferenceDataService`.

### `GET /api/v1/reference/{list}`

`list` path param is one of: `religions`, `castes`, `sub-castes`, `denominations`, `dioceses`, `occupations`, `education-levels`, `mother-tongues`, `countries`, `states`, `districts`, `communities`, `income-ranges`, `complexion`, `body-type`, `marital-status`, `family-status`, `diet`, `drinking`, `smoking`, `physical-status`, `blood-group`, `manglik`, `rashi`, `nakshatra`, `residency-status`, `how-did-you-hear`, `created-by`.

### Cascading query params

| List | Filters |
|------|---------|
| `castes` | `?religion=Hindu` |
| `sub-castes` | `?caste=Brahmin` |
| `denominations` | `?religion=Christian` |
| `dioceses` | `?denomination=Catholic` |
| `states` | `?country=India` |
| `districts` | `?state=Karnataka` |

### Response (grouped list)
```json
{
  "success": true,
  "data": [
    { "slug": "brahmin", "label": "Brahmin" },
    { "slug": "kshatriya", "label": "Kshatriya" },
    /* ... */
  ]
}
```

### Response (simple list)
```json
{
  "success": true,
  "data": ["Tall", "Average", "Short"]
}
```

ReferenceDataService distinguishes grouped vs simple lists internally — API returns whichever shape fits.

**Cache:** 1h server-side, 24h Flutter-side.

### `GET /api/v1/site/settings` — Site config for client

Flutter fetches this on app launch + on each foreground resume (cheap, can be 304'd). Used for:
- Theming (primary color, fonts)
- Feature toggles (email_otp_login_enabled, horoscope_enabled, etc.)
- Branding (site name, logo URL)
- Privacy policy URL
- Contact info
- Razorpay public key
- FCM sender ID (redundant with Firebase config but useful)

**Response:**
```json
{
  "success": true,
  "data": {
    "site": {
      "name": "Kudla Matrimony",
      "tagline": "Find Your Perfect Match",
      "logo_url": "https://.../branding/logo.png",
      "favicon_url": "https://.../branding/favicon.png",
      "support_email": "support@kudlamatrimony.com",
      "support_phone": "+91-824-1234567",
      "support_whatsapp": "+91-9876543210",
      "address": "Mangalore, Karnataka, India"
    },
    "theme": {
      "primary_color": "#dc2626",
      "secondary_color": "#fbbf24",
      "heading_font": "Playfair Display",
      "body_font": "Inter"
    },
    "features": {
      "email_otp_login_enabled": false,
      "mobile_otp_login_enabled": true,
      "email_verification_required": true,
      "phone_verification_required": false,
      "horoscope_enabled": true,
      "realtime_chat_enabled": false,
      "auto_approve_profiles": true
    },
    "registration": {
      "min_age": 18,
      "password_min_length": 6,
      "password_max_length": 14,
      "id_prefix": "AM"
    },
    "membership": {
      "razorpay_key": "rzp_live_abcxyz",
      "currency": "INR"
    },
    "app": {
      "minimum_supported_version": "1.0.0",
      "latest_version": "1.2.0",
      "force_upgrade_below": "1.0.0",    // Flutter shows update-required screen
      "play_store_url": "...",
      "app_store_url": "..."
    },
    "social_links": {
      "facebook": "...", "instagram": "...", "youtube": "...", "linkedin": "..."
    },
    "policies": {
      "privacy_policy_url": "https://.../privacy-policy",
      "terms_url": "https://.../terms-condition",
      "refund_policy_url": "https://.../refund-policy"
    }
  }
}
```

**ETag/cache:** server sends `ETag` header. Flutter sends `If-None-Match` on subsequent calls → 304 if unchanged.

---

## 9.10 Success Stories

### `GET /api/v1/success-stories`

**Public.** Paginated feed of approved testimonials.

**Query:** `page=1&per_page=10`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 42,
      "couple_names": "Ramesh & Priya",
      "story": "We met through Kudla Matrimony in 2024...",
      "wedding_date": "2024-12-15",
      "photo_url": "https://.../success-stories/42.jpg",
      "created_at": "2025-01-10T10:00:00Z"
    }
  ],
  "meta": { /* pagination */ }
}
```

### `POST /api/v1/success-stories` — Submit

**Auth required.**

**Request:**
```json
{
  "couple_names": "Ramesh & Priya",
  "story": "We met through...",
  "wedding_date": "2024-12-15",
  "photo": "<multipart file, optional>"
}
```

**Response 201:**
```json
{
  "success": true,
  "data": {
    "story_id": 42,
    "status": "pending",
    "message": "Thanks! We'll review and publish soon."
  }
}
```

Admin reviews in Filament panel before publishing.

---

## 9.11 Contact Form

### `POST /api/v1/contact`

**No auth required** — anonymous inquiries allowed.

**Request:**
```json
{
  "name": "John Visitor",
  "email": "john@example.com",
  "phone": "9876543210",
  "subject": "Inquiry about premium plan",
  "message": "Hi, I'd like to know..."
}
```

**Response 201:**
```json
{ "success": true, "data": { "message": "Thanks! We'll reply within 24 hours." } }
```

**Side effects:**
- Writes to `contact_submissions` table
- Sends email to site support address
- Admin replies from Filament panel (canned responses system)

---

## 9.12 Static Pages

### `GET /api/v1/static-pages/{slug}`

**Public.** Returns DB-backed static page content (for in-app rendering of privacy policy, terms, etc.).

**Slugs:** `privacy-policy`, `terms-condition`, `about-us`, `refund-policy`, `child-safety`, `report-misuse`, `add-with-us`, plus any custom admin-created pages.

**Response:**
```json
{
  "success": true,
  "data": {
    "slug": "privacy-policy",
    "title": "Privacy Policy",
    "content_html": "<h1>Privacy Policy</h1>...",     // HTML (rendered via flutter_html)
    "meta_description": "...",
    "updated_at": "2026-04-15T10:00:00Z"
  }
}
```

**Flutter render:** `flutter_html` package parses the HTML. Variables like `{{ app_name }}` are substituted server-side before return.

---

## 9.13 Build Checklist

- [ ] 10 new API controllers under `Api\V1\`: Notification, Shortlist, ProfileView, Block, Report, IgnoredProfile, IdProof, Settings, ReferenceData, SuccessStory, Contact, StaticPage
- [ ] Resource classes: NotificationResource, ReferenceListResource, SiteSettingsResource, StaticPageResource, SuccessStoryResource, IdProofResource
- [ ] `App\Http\Controllers\Api\V1\ReferenceDataController::siteSettings()` assembles §9.9 shape from `site_settings` table
- [ ] ETag support on `/site/settings` for efficient revalidation
- [ ] Contact form rate limit: 5 submissions/hour/IP
- [ ] Pest tests per endpoint (one happy + one failure each)

**Acceptance:**
- Full settings screen data loads in one call
- Cascading dropdowns (religion → caste → sub-caste) work
- Site settings endpoint returns valid theme colors that Flutter applies
- Static pages render correctly in Flutter via flutter_html
