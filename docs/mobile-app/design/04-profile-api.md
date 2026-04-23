# 4. Profile API

Covers: viewing own profile, viewing another profile, editing all 9 sections, preview JSON, share card, privacy gates.

**Source:** `App\Http\Controllers\ProfileController`, `App\Models\Profile` + related (ReligiousInfo, EducationDetail, FamilyDetail, LocationInfo, ContactInfo, LifestyleInfo, PartnerPreference, SocialMediaLink, PhotoPrivacySetting).

---

## 4.1 Profile identity

- **Public ID:** `matri_id` (string like `AM100042`) — used in URLs and shareable links.
- **Internal ID:** `id` (integer, DB auto-increment) — never exposed externally.

All API endpoints accept `matri_id`, never the integer id. This matches web URLs and makes linking shareable.

---

## 4.2 `GET /api/v1/dashboard`

Dashboard data in one call. Matches the web dashboard's 7 sections.

**Response:**
```json
{
  "success": true,
  "data": {
    "cta": {
      "show_profile_completion": true,
      "profile_completion_pct": 78,
      "show_photo_upload": false,
      "show_verify_email": true,
      "show_verify_phone": false,
      "show_upgrade": true
    },
    "stats": {
      "interests_received": 12,
      "interests_sent": 5,
      "profile_views_total": 87,
      "shortlisted_count": 23,
      "unread_notifications": 4
    },
    "recommended_matches": [ /* array of ProfileCardResource */ ],
    "mutual_matches": [ /* array */ ],
    "recent_views": [ /* array */ ],
    "newly_joined": [ /* array */ ],
    "discover_teasers": [
      { "category": "nri-matrimony", "label": "NRI Matrimony", "count": 234 },
      { "category": "catholic-matrimony", "label": "Catholic", "count": 89 }
    ]
  }
}
```

Each profile list is capped at 10 — Flutter shows a horizontal scroll with "See all →" linking to the full list screen.

**ProfileCardResource shape (used everywhere for profile lists):**
```json
{
  "matri_id": "AM100087",
  "full_name": "Priya S.",
  "age": 26,
  "height_cm": 160,
  "height_label": "5' 3\"",
  "religion": "Hindu",
  "caste": "Brahmin",
  "native_state": "Karnataka",
  "occupation": "Software Engineer",
  "education_short": "B.E.",
  "primary_photo": {
    "thumbnail_url": "https://.../thumb.webp",
    "medium_url": "https://.../medium.webp",
    "is_blurred": false       // true if viewer is non-premium AND profile.photo_privacy.blur_non_premium
  },
  "badges": ["verified", "premium"],   // subset of: ["verified","premium","vip","featured","new"]
  "last_active_label": "2 hours ago",
  "match_score": 82,           // null if we didn't compute; see §4.9
  "is_shortlisted": false,
  "interest_status": null      // null | "sent" | "received" | "accepted" | "declined"
}
```

---

## 4.3 `GET /api/v1/profile/me`

Returns the full profile of the authenticated user with all 9 sections.

**Response:**
```json
{
  "success": true,
  "data": {
    "profile": {
      "matri_id": "AM100042",
      "full_name": "Naveen D'Souza",
      "gender": "Male",
      "date_of_birth": "1995-04-12",
      "age": 30,
      "marital_status": "Never Married",
      "profile_completion_pct": 78,
      "is_approved": true,
      "is_hidden": false,
      "is_verified": true,
      "is_premium": true,
      "is_vip": false,
      "is_featured": false,
      "suspension_status": "active",
      "created_at": "2025-08-01T10:00:00Z",
      "last_active_at": "2026-04-23T14:15:00Z",
      "sections": {
        "primary": { /* full ProfileResource section */ },
        "religious": { /* ... */ },
        "education": { /* ... */ },
        "family": { /* ... */ },
        "location": { /* ... */ },
        "contact": { /* ... */ },
        "hobbies": { /* ... */ },
        "social": { /* ... */ },
        "partner": { /* ... */ }
      },
      "photos": {
        "profile": [ /* PhotoResource */ ],
        "album": [ /* up to 9 */ ],
        "family": [ /* up to 3 */ ],
        "photo_privacy": {
          "gated_premium": false,
          "show_watermark": true,
          "blur_non_premium": false
        }
      }
    }
  }
}
```

### Section payloads

**primary:**
```json
{
  "height_cm": 172,
  "height_label": "5' 8\"",
  "weight_kg": 68,
  "complexion": "Wheatish",
  "body_type": "Average",
  "blood_group": "O+",
  "mother_tongue": "Kannada",
  "languages_known": ["Kannada", "English"],
  "physical_status": "Normal",
  "about_me": "..."
}
```

**religious:**
```json
{
  "religion": "Hindu", "caste": "Brahmin", "sub_caste": "Saraswat",
  "gotra": "Bharadwaj", "nakshatra": "Rohini", "rashi": "Taurus", "manglik": "No",
  "denomination": null, "diocese": null, "diocese_name": null, "parish_name_place": null,
  "time_of_birth": "06:30", "place_of_birth": "Mangalore",
  "muslim_sect": null, "muslim_community": null, "religious_observance": null,
  "jain_sect": null, "other_religion_name": null,
  "jathakam_url": "https://.../jathakam.pdf"       // absolute, null if none
}
```

**education:**
```json
{
  "education_level": "Bachelor's",
  "educational_qualification": "B.E.",
  "educational_qualification_detail": "BE in CS, NITK 2018",
  "occupation": "Software Professional",
  "occupation_detail": "Senior SWE",
  "employer_name": "Infosys",
  "working_country": "India",
  "income_range": "5-10 LPA",
  "company_description": "..."
}
```

**family:**
```json
{
  "family_status": "Middle Class",
  "father_name": "Ramesh D'Souza", "father_occupation": "Retired Teacher",
  "mother_name": "Sheela D'Souza", "mother_occupation": "Homemaker",
  "brothers_total": 1, "brothers_married": 1,
  "sisters_total": 0, "sisters_married": 0,
  "family_origin": "Mangalore",
  "asset_details": "...",
  "about_family": "..."
}
```

**location:**
```json
{
  "native_country": "India", "native_state": "Karnataka", "native_district": "Dakshina Kannada",
  "pin_zip_code": "575001",
  "residing_country": "India", "residing_state": "Karnataka", "residing_city": "Bangalore",
  "residency_status": "Citizen",
  "is_outstation": false, "outstation_from": null, "outstation_to": null, "outstation_reason": null
}
```

**contact:** (gated — see §4.6)
```json
{
  "phone": "9876543210",           // own profile only
  "whatsapp_number": "9876543210",
  "primary_phone": "9876543210",
  "secondary_phone": null,
  "residential_phone": null,
  "email": "naveen@example.com",
  "alternate_email": null,
  "contact_person": "Uncle Joe",
  "contact_relationship": "Uncle",
  "preferred_call_time": "Evening",
  "communication_address": "...",
  "pincode": "575001"
}
```

**hobbies:**
```json
{
  "hobbies": ["Reading", "Trekking"],
  "favorite_music": [], "preferred_books": [], "preferred_movies": [],
  "sports": [], "favorite_cuisine": []
}
```

**social:**
```json
{
  "facebook_url": "...", "instagram_url": "...", "linkedin_url": "...",
  "youtube_url": null, "website_url": "..."
}
```

**partner:**
```json
{
  "age_from": 22, "age_to": 30,
  "height_from_cm": 150, "height_to_cm": 170,
  "complexion": ["Fair", "Wheatish"],
  "body_type": ["Slim", "Average"],
  "marital_status": ["Never Married"],
  "physical_status": ["Normal"],
  "religions": ["Hindu"], "castes": ["Brahmin"], "sub_castes": [], "denominations": [],
  "mother_tongues": ["Kannada", "English"],
  "education_levels": ["Bachelor's", "Master's"],
  "occupations": ["Software Professional", "Teacher"],
  "income_range": "5-10 LPA",
  "working_countries": ["India", "UAE"],
  "native_states": ["Karnataka"],
  "family_status": ["Middle Class"],
  "diet": ["Vegetarian"],
  "drinking": ["Never"], "smoking": ["Never"],
  "manglik": "Doesn't matter",
  "about_partner": "..."
}
```

---

## 4.4 `GET /api/v1/profiles/{matriId}` — View another profile

Returns the same shape as `/profile/me` but with **privacy gates applied**:

### Gates

1. **Gender check:** if `auth.profile.gender === target.gender` → 403 `GENDER_MISMATCH`.
2. **Blocked:** if viewer has blocked target OR target has blocked viewer → 404 `NOT_FOUND` (don't reveal the block).
3. **Hidden:** if target `is_hidden = true` AND viewer didn't have a pre-existing interest → 404.
4. **Visibility setting:** target's `show_profile_to`:
   - `"all"` → visible
   - `"premium"` → visible only if viewer `is_premium = true`
   - `"matches"` → visible only if match score ≥ 70 (configurable)
5. **Contact section:** redacted unless `viewer.is_premium = true` **AND** there's an `accepted` interest between them. Otherwise `contact` is `null` in the response.
6. **Photo blur:** per-photo `is_blurred` field is `true` if `target.photo_privacy.blur_non_premium = true` and viewer is not premium.
7. **Suspension:** if `target.suspension_status != 'active'` → 404.

**Fire side-effect:** record a `ProfileView` (`App\Services\ProfileViewService::track($viewer, $target)`). Deduped to once per viewer-target per 24h.

**Response:**
```json
{
  "success": true,
  "data": {
    "profile": { /* same shape as /profile/me but with gated sections */ },
    "contact": null,                            // or {...} if premium + accepted interest
    "match_score": {
      "score": 82,
      "breakdown": {
        "religion": 15,
        "age": 12,
        "denomination": 0,
        "mother_tongue": 10,
        "education": 8,
        "occupation": 7,
        "height": 6,
        "native_location": 8,
        "working_location": 5,
        "marital_status": 5,
        "diet": 2,
        "family_status": 2,
        "horoscope": 0
      },
      "badge": "Excellent Match"
    },
    "interest_status": "sent",                  // null|sent|received|accepted|declined|expired
    "is_shortlisted": false,
    "is_blocked": false,
    "photo_request_status": null                // null|pending|approved|ignored
  }
}
```

---

## 4.5 `PUT /api/v1/profile/me/{section}`

Updates one of 9 sections. `section` is in the path: `primary | religious | education | family | location | contact | hobbies | social | partner`.

**Request:** same shape as the section object in `GET /profile/me` (partial allowed — only included fields are updated).

**Validation:** section-specific FormRequest classes (`UpdatePrimarySectionRequest`, `UpdateReligiousSectionRequest`, …).

**Response:**
```json
{
  "success": true,
  "data": {
    "section": "primary",
    "updated_fields": ["about_me", "weight_kg"],
    "profile_completion_pct": 80
  }
}
```

**Recomputes:** `profile_completion_pct` after every update via `App\Services\ProfileCompletionService::recalculate($profile)`.

---

## 4.6 Contact privacy gate

Per `MembershipPlan.view_contacts_limit` + `daily_contact_views`, premium users have a budget. Current system (web) enforces this on the contact page view. Mirror here:

- First time viewer sees contact for a target: decrement `daily_contacts_viewed_today` counter (per-viewer-day in a new `daily_contact_views` table or in Redis)
- If counter exceeded → 403 `DAILY_LIMIT_REACHED`
- Subsequent views of same target don't count (cached per viewer-target pair)

**Deferred to Phase 3:** this fine-grained tracking. v1 just requires `is_premium + accepted interest`. The counter is a "nice to have" for plan differentiation.

---

## 4.7 `GET /api/v1/profiles/{matriId}/preview` — Share card

Returns a minimal, printable-style representation suitable for generating a share card image client-side.

**Response:**
```json
{
  "success": true,
  "data": {
    "matri_id": "AM100042",
    "full_name": "Naveen D'Souza",
    "age": 30,
    "height_label": "5' 8\"",
    "religion": "Hindu",
    "caste": "Brahmin",
    "native_state": "Karnataka",
    "residing_city": "Bangalore",
    "education_short": "B.E.",
    "occupation": "Software Engineer",
    "primary_photo_url": "https://.../medium.webp",
    "share_url": "https://kudlamatrimony.com/profile/AM100042",
    "qr_code_svg": "<svg>...</svg>",
    "site_branding": {
      "site_name": "Kudla Matrimony",
      "primary_color": "#dc2626",
      "logo_url": "https://.../logo.png"
    }
  }
}
```

Flutter uses this to render a card image locally and share via WhatsApp intent (see `15-flutter-polish-launch §15.6`).

---

## 4.8 Jathakam upload

Split from registration step 2 (which was multipart in web). Separate endpoint:

### `POST /api/v1/profile/me/jathakam`

**Content-Type:** `multipart/form-data`
**Field:** `jathakam` (file, PDF or image, max 5 MB)

**Response:**
```json
{
  "success": true,
  "data": {
    "jathakam_url": "https://.../jathakam_ABC123.pdf"
  }
}
```

### `DELETE /api/v1/profile/me/jathakam`

Removes the uploaded file and clears `religious_infos.jathakam_upload_url`.

---

## 4.9 Match score computation

Called from `GET /profiles/{matriId}` for the viewed profile. **Not** called on list endpoints (too expensive).

List endpoints (`GET /matches/my`, `/search`, `/dashboard`) include `match_score` **only if cached**. Cache key: `match_score:{viewer_profile_id}:{target_profile_id}`, TTL 24h. Compute on-demand when `/profiles/{matriId}` is hit, and a nightly job warms caches for top-N candidates per user.

**`App\Services\MatchingService::calculateScore(Profile $candidate, PartnerPreference $prefs)`** already exists. API layer just calls it.

---

## 4.10 Build Checklist

- [ ] `App\Http\Resources\V1\ProfileResource` — full profile with all 9 sections
- [ ] `App\Http\Resources\V1\ProfileCardResource` — lightweight list shape
- [ ] `App\Http\Resources\V1\DashboardResource` — assembles §4.2 shape
- [ ] `App\Http\Controllers\Api\V1\ProfileController`:
  - [ ] `dashboard()` → DashboardResource
  - [ ] `me()` → own ProfileResource
  - [ ] `show(string $matriId)` → gated ProfileResource
  - [ ] `updateSection(string $section)` → dispatches to section-specific request
  - [ ] `preview(string $matriId)` → share card
  - [ ] `uploadJathakam()` / `deleteJathakam()`
- [ ] `App\Services\ProfileAccessService::canView(Profile $viewer, Profile $target)` — encapsulates all 7 gates from §4.4
- [ ] `App\Services\ProfileViewService::track($viewer, $target)` — existing or new, 24h dedupe
- [ ] FormRequests for each section update (9 classes)
- [ ] Pest tests: each gate in §4.4 has a test, both positive and negative

**Acceptance:**
- Viewing a same-gender profile returns 403
- Viewing a blocked user returns 404
- Viewing with a non-premium account hides contact section AND blurs photos if target has `blur_non_premium`
- Match score appears on individual profile view, not in list views (unless cached)
- Each of the 9 section updates persists correctly and returns updated completion %
