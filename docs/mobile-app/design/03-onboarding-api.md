# 3. Onboarding API

Covers the **optional** post-registration onboarding steps that raise profile completion %. Users land here after registration if `onboarding_completed = true` but `profile_completion_pct < 80` (configurable threshold). Can also be reached manually from dashboard's "Complete your profile" CTA.

**Distinct from registration:** registration's 5 steps are required (create account → verify → dashboard). Onboarding's 4 steps are **voluntary** — user can skip to dashboard any time.

**Source:** `App\Http\Controllers\OnboardingController` (416 lines).

---

## 3.1 Flow overview

```
Dashboard → "Complete your profile" banner
              ↓
      Step 1: Personal + Professional + Family
              ↓
      Step 2: Location + Contact
              ↓
      Step 3: Partner Preferences
              ↓
      Step 4: Lifestyle + Social
              ↓
      Finish (or skip any step) → back to Dashboard
```

All 4 steps require `auth:sanctum`. All are idempotent — user can re-edit any step later via `PUT /profile/me/{section}`.

---

## 3.2 `POST /api/v1/onboarding/step-1` — Personal + Professional + Family

**Request:**
```json
{
  "personal": {
    "weight_kg": 65,
    "blood_group": "O+",
    "mother_tongue": "Kannada",
    "languages_known": ["Kannada", "English", "Tulu"],
    "about_me": "Easygoing, family-oriented..."
  },
  "professional": {
    "educational_qualification_detail": "BE in Computer Science, NITK 2018",
    "occupation_detail": "Senior Software Engineer",
    "employer_name": "Infosys",
    "company_description": "Bangalore office, payments team"
  },
  "family": {
    "father_name": "Ramesh D'Souza",
    "father_occupation": "Retired Teacher",
    "mother_name": "Sheela D'Souza",
    "mother_occupation": "Homemaker",
    "brothers_total": 1,
    "brothers_married": 1,
    "sisters_total": 0,
    "sisters_married": 0,
    "family_origin": "Mangalore",
    "about_family": "Nuclear family with joint-family values..."
  }
}
```

**Validation:** all fields optional except they must pass per-field type/length. `weight_kg` 30–200. `blood_group` in allowed list. `about_me` max 2000 chars.

**Persists to:**
- `profiles` — weight_kg, blood_group, mother_tongue, about_me
- `lifestyle_infos` — languages_known (JSON)
- `education_details` — educational_qualification_detail, occupation_detail, employer_name, company_description
- `family_details` — all family fields

**Response:**
```json
{
  "success": true,
  "data": {
    "profile_completion_pct": 62,
    "next_step": "onboarding.step-2"
  }
}
```

---

## 3.3 `POST /api/v1/onboarding/step-2` — Location + Contact

**Request:**
```json
{
  "location": {
    "residing_country": "India",
    "residing_state": "Karnataka",
    "residing_city": "Mangalore",
    "residency_status": "Citizen",
    "is_outstation": false,
    "outstation_from": null,
    "outstation_to": null,
    "outstation_reason": null
  },
  "contact": {
    "residential_phone": "0824-1234567",
    "secondary_phone": "9876543211",
    "preferred_call_time": "Evening",
    "alternate_email": "naveen.alt@example.com",
    "reference_name": "Uncle Joe",
    "reference_phone": "9988776655",
    "reference_relation": "Maternal Uncle",
    "present_address_same_as_communication": true,
    "present_address": null,
    "permanent_address_same_as_communication": false,
    "permanent_address": "Old House, Kadri Hills, Mangalore 575002"
  }
}
```

**Persists to:** `location_infos` + `contact_infos`.

**Response:** `{"profile_completion_pct": 74, "next_step": "onboarding.partner-preferences"}`.

---

## 3.4 `POST /api/v1/onboarding/partner-preferences`

**Request:**
```json
{
  "age_from": 22,
  "age_to": 30,
  "height_from_cm": 150,
  "height_to_cm": 170,
  "complexion": ["Fair", "Wheatish"],
  "body_type": ["Slim", "Average"],
  "marital_status": ["Never Married"],
  "physical_status": ["Normal"],
  "religions": ["Hindu"],
  "castes": ["Brahmin", "Kshatriya"],
  "sub_castes": [],
  "denominations": [],
  "mother_tongues": ["Kannada", "English"],
  "education_levels": ["Bachelor's", "Master's"],
  "occupations": ["Software Professional", "Teacher"],
  "income_range": "5-10 LPA",
  "working_countries": ["India", "UAE"],
  "native_states": ["Karnataka"],
  "family_status": ["Middle Class", "Upper Middle Class"],
  "diet": ["Vegetarian", "Eggetarian"],
  "drinking": ["Never"],
  "smoking": ["Never"],
  "manglik": "Doesn't matter",
  "about_partner": "Looking for a caring, family-oriented partner..."
}
```

**Validation:** array fields max length (e.g. `religions` max 10). Numeric ranges sane. Arrays can be empty (no filter).

**Persists to:** `partner_preferences` (one row per profile, upsert on `profile_id`).

**Response:** `{"profile_completion_pct": 85, "next_step": "onboarding.lifestyle"}`.

---

## 3.5 `POST /api/v1/onboarding/lifestyle` — Lifestyle + Social

**Request:**
```json
{
  "lifestyle": {
    "diet": "Vegetarian",
    "drinking": "Never",
    "smoking": "Never",
    "cultural_background": "Tuluva Brahmin, Mangalore",
    "hobbies": ["Reading", "Trekking", "Cooking"],
    "favorite_music": ["Carnatic", "A.R. Rahman"],
    "preferred_books": ["Malayattoor Ramakrishnan", "Yuval Harari"],
    "preferred_movies": ["KGF", "Kantara"],
    "sports": ["Cricket", "Chess"],
    "favorite_cuisine": ["South Indian", "Italian"]
  },
  "social": {
    "facebook_url": "https://facebook.com/naveen",
    "instagram_url": "https://instagram.com/naveen",
    "linkedin_url": "https://linkedin.com/in/naveen",
    "youtube_url": null,
    "website_url": "https://naveen.dev"
  }
}
```

**Persists to:** `lifestyle_infos` + `social_media_links`.

**Response:** `{"profile_completion_pct": 92, "next_step": "dashboard", "onboarding_finished": true}`.

---

## 3.6 `POST /api/v1/onboarding/finish` — Skip remaining

User can bail out of onboarding at any step. This endpoint does nothing (no-op); it's there so Flutter has a symmetric "I'm done" action without implying failure.

**Request:** empty body.

**Response:** `{"success": true, "data": {"next_step": "dashboard"}}`.

---

## 3.7 Reference Data (cascading selects)

Flutter needs the same dropdown data the web uses. These endpoints back cascading selects in registration AND onboarding screens.

See `09-engagement-api.md §9.9` for full endpoint list. Summary:

| Endpoint | Returns |
|----------|---------|
| `GET /api/v1/reference/religions` | `[{ "slug": "hindu", "label": "Hindu" }, ...]` |
| `GET /api/v1/reference/castes?religion=hindu` | `[{ "slug": "brahmin", "label": "Brahmin" }, ...]` |
| `GET /api/v1/reference/sub-castes?caste=brahmin` | |
| `GET /api/v1/reference/denominations?religion=christian` | |
| `GET /api/v1/reference/dioceses?denomination=catholic` | |
| `GET /api/v1/reference/occupations` | |
| `GET /api/v1/reference/education-levels` | |
| `GET /api/v1/reference/mother-tongues` | |
| `GET /api/v1/reference/countries` | |
| `GET /api/v1/reference/states?country=India` | |
| `GET /api/v1/reference/districts?state=Karnataka` | |
| `GET /api/v1/reference/communities` | (combined religion+caste pairs for homepage browse) |

All backed by `App\Services\ReferenceDataService` (DB override + config fallback).

**Caching:** Flutter caches each response for 24h (Hive) — these change rarely. Server-side also cached 1h (Laravel cache).

---

## 3.8 Skip logic

Onboarding is skippable at the step level:
- User navigates away from step 2 mid-fill → no partial save (screen state lost)
- User taps "Skip" on a step → goes to next step without writing data
- Flutter calls `POST /onboarding/finish` when "Do this later" is tapped → returns `next_step: "dashboard"`

There's no server-side "skipped" flag. `profile_completion_pct` is the single source of truth for how filled-in a profile is.

---

## 3.9 Build Checklist

- [ ] `App\Http\Controllers\Api\V1\OnboardingController` with 5 methods (step1, step2, partnerPrefs, lifestyle, finish)
- [ ] Reuse web's `OnboardingController` persistence logic by extracting into `App\Services\OnboardingService`
- [ ] FormRequests for each step with JSON-friendly validation messages
- [ ] `App\Services\ProfileCompletionService::recalculate($profile)` called after each step (already exists)
- [ ] `ReferenceDataController` with the 12 endpoints in §3.7
- [ ] Pest tests: happy path for each step + completion-pct increment assertion

**Acceptance:** after register-step-5 + 4 onboarding calls, `profile_completion_pct ≥ 90` and dashboard loads cleanly.
