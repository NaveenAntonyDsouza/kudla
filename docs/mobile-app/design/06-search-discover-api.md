# 6. Search & Discover API

Covers: partner search (15+ filters), keyword search, matri-ID lookup, saved searches, Discover hub (3-level browsing), matches (my + mutual), match-score endpoint.

**Source:** `App\Http\Controllers\SearchController`, `DiscoverController`, `SavedSearchController`, `MatchController`, `App\Services\MatchingService`, `App\Services\DiscoverConfigService`, `App\Traits\ProfileQueryFilters`, `config/discover.php`.

---

## 6.1 Partner Search — `GET /api/v1/search`

The workhorse. 15+ filters + sort + pagination.

### Query parameters

All optional. When omitted, filter is not applied.

```
age_from=22
age_to=30
height_from_cm=150
height_to_cm=175
religions=Hindu,Christian          (comma-separated)
castes=Brahmin,Kshatriya
sub_castes=Saraswat
denominations=Catholic
dioceses=Mangalore
mother_tongues=Kannada,English
education_levels=Bachelor's,Master's
occupations=Software Professional,Teacher
income_range=5-10 LPA
working_countries=India,UAE
native_countries=India
native_states=Karnataka
native_districts=Dakshina Kannada
residing_countries=India
complexion=Fair,Wheatish
body_type=Slim,Average
marital_status=Never Married
physical_status=Normal
family_status=Middle Class
diet=Vegetarian
drinking=Never
smoking=Never
manglik=No
keywords=software bangalore        (full-text across bio + occupation + location)
has_photo=1                        (only profiles with approved primary photo)
verified_only=1                    (only verified profiles)
premium_only=1                     (only premium-badge profiles)
sort=relevance                     (relevance|newest|recently_active|age_asc|age_desc|height_asc|height_desc|match_score|profile_completion)
page=1
per_page=20                        (default 20, max 50)
```

### Server-side logic

1. Base query: `Profile::query()->visible()->notBlocked($viewer)->notIgnored($viewer)->oppositeGender($viewer)`
2. Apply all filters via existing `ProfileQueryFilters` trait
3. Apply sort:
   - `relevance` — default; Diamond first, then Premium, then recently-active, then newest. Uses ranking CASE expression (already in `SearchController::publicSearch` lines we saw)
   - `match_score` — joins `match_scores` table (only users with cached scores)
   - others — straight ORDER BY
4. Paginate

### Response
```json
{
  "success": true,
  "data": [
    /* ProfileCardResource[] */
  ],
  "meta": {
    "page": 1, "per_page": 20, "total": 137, "last_page": 7,
    "applied_filters": {
      "age_from": 22, "age_to": 30, "religions": ["Hindu"], "castes": ["Brahmin"]
      /* echoed back, useful for "active filters" pill bar */
    }
  }
}
```

---

## 6.2 Keyword search — `GET /api/v1/search/keyword`

Lightweight keyword-only variant. Used by the search bar on dashboard.

**Query:** `q=naveen bangalore engineer` (required, min 2 chars)

**Behaviour:** fuzzy search across `profiles.full_name`, `profiles.about_me`, `education_details.occupation`, `location_infos.native_district`, `location_infos.residing_city`. Uses `LIKE %...%` for v1 (Meilisearch later, tracked in Phase 4).

**Response:** same as partner search response shape, with `meta.query_term: "naveen bangalore engineer"`.

**Performance note:** `LIKE` scans are slow at scale. For v1 with <20K profiles, acceptable. At 50K+ profiles, switch to Meilisearch (config already flags this in `NEXT_SESSION_PLAN.md` Phase 4 #12).

---

## 6.3 Matri-ID lookup — `GET /api/v1/search/id/{matriId}`

Returns a single profile card if found and visible to viewer. Same gates as `GET /profiles/{matriId}` (see `04-profile-api.md §4.4`).

**Response (found):**
```json
{
  "success": true,
  "data": { /* ProfileCardResource */ }
}
```

**Response (not found or gated):** 404 `NOT_FOUND` with a generic message (don't leak why).

---

## 6.4 Saved searches

### `GET /api/v1/search/saved`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "name": "Hindu Brahmin Karnataka 25-30",
      "filters": { /* same shape as §6.1 query params */ },
      "alert_enabled": true,              // new matches notification
      "last_run_count": 23,
      "new_since_last_run": 2,
      "created_at": "2026-03-01T10:00:00Z"
    }
  ]
}
```

### `POST /api/v1/search/saved`

**Request:**
```json
{
  "name": "Hindu Brahmin Karnataka 25-30",
  "filters": { "age_from": 25, "age_to": 30, "religions": ["Hindu"], "castes": ["Brahmin"], "native_states": ["Karnataka"] },
  "alert_enabled": true
}
```

**Response 201:** echoed shape + server-assigned `id`.

**Limits:** max 10 saved searches per user.

### `DELETE /api/v1/search/saved/{savedSearch}`

Returns `{"success": true, "data": {"deleted": true}}`.

**Alert mechanism:** a scheduled job `search:run-alerts` (daily 8 AM) runs each saved search with `alert_enabled=true` for each user, computes new matches since last run, sends in-app + email notification. Stores `last_run_at` and `last_run_count`.

---

## 6.5 Match list — `GET /api/v1/matches/my`

"My Matches" — profiles matching authenticated user's Partner Preferences.

**Query params:**
```
sort=match_score                   (match_score|newest|recently_active — default match_score)
min_score=60                       (default 0)
page=1
per_page=20
```

**Response:** same shape as partner search, with each `ProfileCardResource` including `match_score`.

**Logic:** `MatchingService::findMatches(Profile $viewer)` — applies viewer's `PartnerPreference` filters as the base query, sorts by match score, returns paginated result. Uses cached scores where available, computes lazily for top-N in the page.

---

## 6.6 Mutual matches — `GET /api/v1/matches/mutual`

Profiles where viewer matches target's preferences AND target matches viewer's preferences. Strongest signal.

**Response:** same shape as §6.5, typically fewer results.

**Cache:** mutual matches are expensive to compute. Cache per-user for 6h. Invalidate when viewer or any mutual-candidate updates their PartnerPreference.

---

## 6.7 Discover Hub — `GET /api/v1/discover`

**Public, no auth required.** Returns the 13 category tiles shown on homepage "Discover Profiles" section.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "category": "nri-matrimony",
      "label": "NRI Matrimony",
      "description": "Profiles from abroad",
      "icon_url": "https://.../icons/nri.svg",
      "show_search": true,
      "subcategory_count": 24,
      "estimated_profile_count": 340
    },
    {
      "category": "catholic-matrimony",
      "label": "Catholic Matrimony",
      "description": "Catholic profiles",
      "icon_url": "https://.../icons/catholic.svg",
      "show_search": true,
      "subcategory_count": 12,
      "estimated_profile_count": 89
    },
    /* ...11 more... */
  ]
}
```

---

## 6.8 Discover Category — `GET /api/v1/discover/{category}`

**Public.** Returns subcategories for a category (e.g. for `nri-matrimony`, returns list of countries).

**Response:**
```json
{
  "success": true,
  "data": {
    "category": "nri-matrimony",
    "label": "NRI Matrimony",
    "subcategories": [
      { "slug": "usa", "label": "USA", "count": 45 },
      { "slug": "uae", "label": "UAE", "count": 78 },
      { "slug": "uk", "label": "UK", "count": 32 }
    ]
  }
}
```

**For categories with `direct_filter`** (e.g. `kannadiga-matrimony`): no subcategories, responds with immediate results by calling `discover/results` logic inline.

```json
{
  "success": true,
  "data": {
    "category": "kannadiga-matrimony",
    "label": "Kannadiga Matrimony",
    "direct_filter": { "mother_tongue": "Kannada" },
    "results": [ /* ProfileCardResource[] */ ],
    "meta": { /* pagination */ }
  }
}
```

---

## 6.9 Discover Results — `GET /api/v1/discover/{category}/{slug}`

**Public.** Returns paginated profiles for a specific subcategory.

**Query:** `page=1&per_page=20`

**Response:** same shape as partner search, with category/subcategory labels in meta.

**Public vs authenticated:** results are filtered to visible profiles. Anonymous viewers get fewer details (no contact, blurred photos if the profile has privacy set).

---

## 6.10 Match Score endpoint — `GET /api/v1/matches/score/{matriId}`

On-demand computation for a specific target. Rarely needed — `/profiles/{matriId}` already returns this. But useful for "run a quick compatibility check" action.

**Response:**
```json
{
  "success": true,
  "data": {
    "score": 82,
    "breakdown": {
      "religion": 15, "age": 12, /* ... */
    },
    "badge": "Excellent Match",
    "computed_at": "2026-04-23T14:32:11Z",
    "cached": true
  }
}
```

Rate-limited to 30/hour/user to prevent scoring abuse.

---

## 6.11 Filter value sources

Flutter needs to populate dropdown filters. Each comes from `/api/v1/reference/*` endpoints (see `09-engagement-api.md §9.9`).

| Filter | Reference endpoint |
|--------|-------------------|
| Religions | `/reference/religions` |
| Castes | `/reference/castes?religion=Hindu` |
| Denominations | `/reference/denominations?religion=Christian` |
| Dioceses | `/reference/dioceses?denomination=Catholic` |
| Education levels | `/reference/education-levels` |
| Occupations | `/reference/occupations` |
| Income ranges | `/reference/income-ranges` |
| Mother tongues | `/reference/mother-tongues` |
| Countries | `/reference/countries` |
| States | `/reference/states?country=India` |
| Districts | `/reference/districts?state=Karnataka` |
| Complexion | `/reference/complexion` |
| Body type | `/reference/body-type` |
| Marital status | `/reference/marital-status` |
| Family status | `/reference/family-status` |
| Diet | `/reference/diet` |
| Drinking / Smoking | `/reference/drinking`, `/reference/smoking` |

All cached server-side 1h, Flutter-side 24h.

---

## 6.12 Performance notes

- **Partner search with 15 filters** → WHERE clauses are well-indexed already for common fields. Add composite indexes if slow at scale: `profiles(gender, is_active, is_approved, is_hidden)`, `(religion, caste)`, `(native_state, native_district)`.
- **Keyword search** → `LIKE %term%` doesn't use indexes. Move to Meilisearch (Phase 4) at ≥50K profiles.
- **Match score** → O(1) per profile with cached table, O(15 fields read) per fresh compute. Cache aggressively.
- **Mutual matches** → O(N²) in worst case (check viewer matches each target AND each target matches viewer). Limit to top-500 candidates by some proxy (recent activity, profile completion) before bidirectional check.
- **Discover results** → public routes get heavy SEO traffic. Cache response at edge (Cloudflare) for 5 min.

---

## 6.13 Build Checklist

- [ ] `App\Http\Controllers\Api\V1\SearchController`:
  - [ ] `partner()` — applies 15+ filters
  - [ ] `keyword()` — fuzzy search
  - [ ] `byMatriId(string $matriId)` — single lookup
  - [ ] `savedList()` / `saveSearch()` / `deleteSaved()`
  - [ ] `myMatches()` / `mutualMatches()`
- [ ] `App\Http\Controllers\Api\V1\DiscoverController`:
  - [ ] `hub()` / `category(string $cat)` / `results(string $cat, string $slug)`
- [ ] `App\Http\Controllers\Api\V1\MatchController`:
  - [ ] `score(string $matriId)` — on-demand compute
- [ ] Reuse `ProfileQueryFilters` trait for partner filter application
- [ ] Scheduled job: `search:run-alerts` daily 8 AM for saved search notifications
- [ ] Cache keys:
  - [ ] `match_score:{viewer}:{target}` 24h
  - [ ] `mutual_matches:{user}` 6h
  - [ ] `discover:{category}:{slug}:page:{n}` 5m (public)
  - [ ] `reference:{list}:{filter}` 1h
- [ ] FormRequest: `SearchFiltersRequest` (validates filter shape)

**Acceptance:**
- Partner search with all 15 filters returns correct results in <400ms at 10K profiles
- Keyword search for "software engineer bangalore" returns relevant profiles
- Saved search alert fires when a new matching profile registers
- Discover hub returns 13 categories + subcategory counts cached
- Mutual matches endpoint returns subset of `/matches/my` plus reverse-direction test
