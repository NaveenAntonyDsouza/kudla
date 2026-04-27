# UI-Safe API Checklist

**The bar every API endpoint must meet before it's "done".**

Introduced: April 24, 2026 (between Phase 2a Week 2 and Week 3).
Applies to: every new endpoint from Week 3 onwards. Existing Week 1+2
endpoints are retrofitted during the buffer week after Week 4.

---

## The 8-point checklist

An endpoint is only considered "done" when ALL of these are true:

### 1. Timestamps — always ISO 8601 strings

✅ **Do:**
```php
'created_at' => $model->created_at?->toIso8601String(),
'last_active_at' => $user->last_login_at?->toIso8601String(),
```

❌ **Don't:**
```php
'created_at' => $model->created_at,              // Carbon instance — Flutter crashes
'created_at' => $model->created_at->timestamp,   // int — inconsistent with others
'created_at' => $model->created_at->format(...), // non-ISO format
```

**Why:** Flutter's `DateTime.parse()` only handles ISO 8601. Any other shape
crashes at deserialization. `null` is fine — `DateTime.tryParse(null)` returns null.

### 2. Booleans — always real `bool`, never `"1"` / `"0"` strings

✅ **Do:**
```php
'is_active' => (bool) $user->is_active,
'is_primary' => (bool) $photo->is_primary,
'email_verified' => $user->email_verified_at !== null,
```

❌ **Don't:**
```php
'is_active' => $user->is_active,             // might bleed '1'/'0' string from DB
'is_active' => SiteSetting::getValue('x'),   // returns string '1'/'0' — coerce first
```

**Why:** Flutter's `bool.fromEnvironment('0')` returns `true` (non-empty string).
`if (json['is_active'])` passes for `"0"`. Silent bugs everywhere.

### 3. Arrays — always `[]` when empty, never `null` or missing

✅ **Do:**
```php
'photos' => $profile->profilePhotos->toArray(),        // always array
'languages_known' => $profile->languages_known ?? [],   // coerce null to []
'badges' => $this->computeBadges(),                     // even if no badges -> []
```

❌ **Don't:**
```php
'photos' => $profile->profilePhotos->count() > 0 ? $arr : null,  // sometimes null
// Omitting the key entirely when no photos exist
```

**Why:** Flutter does `response['photos'].map((p) => ...).toList()`. If `photos` is
null or missing, the map crashes. Always-present empty array = single code path.

### 4. Optional fields — always present with `null`, never omitted

✅ **Do:**
```php
'contact' => $canViewContact ? $contactData : null,
'match_score' => $score,  // can be null
```

❌ **Don't:**
```php
// Conditionally adding the key:
if ($canViewContact) {
    $payload['contact'] = $contactData;
}
```

**Why:** Flutter DTO classes need a stable shape. Missing vs. null are different
in Dart (`json['contact']` returns null for both, but typed DTOs need to know
which fields exist). Always include the key. Flutter code reads `user.contact?.phone`
which handles null cleanly.

### 5. Photo URLs — always absolute, always via `PhotoStorageService`

✅ **Do:**
```php
'thumbnail_url' => app(PhotoStorageService::class)->getUrl($photo, 'thumbnail'),
// Returns: https://kudlamatrimony.com/storage/photos/p_1247_thumb.webp
```

❌ **Don't:**
```php
'thumbnail_url' => '/storage/photos/...',    // relative — Flutter can't resolve
'thumbnail_url' => $photo->photo_url,        // bypasses driver logic (Cloudinary/R2/S3)
```

**Why:** Flutter runs on a mobile device, not on the server. `cached_network_image`
resolves against nothing, not against the API host. Relative URLs silently fail.

**Automated check:** the `PhotoUrlAbsoluteTest` in the contract snapshot suite
greps every response for `"url"` keys containing `/storage/` prefix and fails
if it finds any without `http(s)://`.

### 6. Error responses — every documented code has a Scribe `@response` block

✅ **Do:**
```php
/**
 * @response 200 scenario="success" { "success": true, "data": {...} }
 * @response 403 scenario="gender mismatch" {
 *   "success": false,
 *   "error": { "code": "GENDER_MISMATCH", "message": "..." }
 * }
 * @response 404 scenario="blocked or hidden" { "success": false, "error": {...} }
 */
```

❌ **Don't:** only document the happy path. Flutter has no way to know what
errors it needs to handle.

**Why:** Flutter writes a single `switch (apiException.code) { ... }` for every
feature. If an error code isn't in the docs, the UI falls through to generic
"Something went wrong" — which users hate. Every `return ApiResponse::error(...)`
in the controller needs a matching `@response` in the PHPDoc.

### 7. Pagination — identical meta shape everywhere

✅ **Do:**
```php
return ApiResponse::paginated($paginator, MyResource::class);
// Emits: meta: { page, per_page, total, last_page }
```

❌ **Don't:** hand-roll a custom meta object per endpoint with different field names
(`current_page` vs `page`, `per` vs `per_page`).

**Why:** Flutter has one `PaginatedList<T>` class that parses this meta block.
If endpoint A uses `page` and endpoint B uses `current_page`, Flutter needs two
parsers. Enforced via the contract snapshot test — any deviation fails loudly.

### 8. Pest coverage — happy + 2+ error paths per endpoint

✅ **Do:** for each endpoint, write at minimum:
- 1 test for the happy path with a realistic payload
- 1 test for validation failure (missing/bad input)
- 1 test for auth failure (missing/wrong token) if the endpoint is protected
- 1 test for business logic failure (e.g., gender mismatch, already exists, daily limit)

❌ **Don't:** rely only on curl smoke tests. Smoke tests drift silently; Pest
tests fail the build. Pest tests are the regression net.

**Why:** Flutter features will break in subtle ways if API behavior changes
between weeks. Pest + contract snapshots catch this before a Flutter dev
notices.

---

## Cross-cutting artifacts (delivered end of Week 4)

### A. Bruno test collection at `docs/bruno/kudla-api-v1/`

Flat `.bru` files per endpoint with request body, env vars, and a `tests {}`
block that asserts:
- `res.status` is expected value
- `res.body.success` is the expected boolean
- Envelope shape matches (`data` for success, `error.code` for error)

Runnable as:
```bash
bru run docs/bruno/kudla-api-v1 --env local
```

Green run = every endpoint works end-to-end. Flutter dev runs this before every
build session.

### B. Contract snapshot test at `tests/Feature/Api/V1/ApiContractSnapshotTest.php`

One Pest test that hits every endpoint, captures response shape (nested keys +
types, not values), and stores as a `.snap` file. Subsequent runs diff against
the snapshot — any drift fails loudly with a message like:

```
ApiContractSnapshot failed for GET /api/v1/profiles/{matriId}:
  - Expected key 'match_score' to be 'object', got 'integer'
  - Expected key 'contact.phone' of type 'string|null', got 'undefined'
```

Purpose: prevents "we accidentally renamed a field and Flutter silently broke"
regressions.

### C. OpenAPI spec completeness (Scribe audit)

A script that runs after `scribe:generate` and verifies:
- Every route under `/api/v1/*` has at least one `@response` block
- Every route has at least one non-2xx `@response` (error case documented)
- Every `@queryParam` / `@urlParam` / `@bodyParam` has a description

Fails the build if any endpoint is under-documented.

---

## Retrofit plan for Weeks 1 + 2 endpoints

Week 1 + 2 endpoints predate this bar. They currently pass curl smoke tests
but may not satisfy all 8 points. During the **buffer week after Week 4**,
each existing endpoint is audited + retrofitted:

- `/site/settings` — verify boolean coercion, add Pest tests
- `/reference/*` — Pest tests already exist; verify contract
- `/auth/register/step-*` — add full Pest tests (currently curl-only)
- `/auth/otp/*/{send,verify}` — add Pest + Bruno entries
- `/auth/login/password` — add Pest + Bruno
- `/auth/password/{forgot,reset}` — add Pest + Bruno
- `/auth/me` — add Pest
- `/auth/logout` — add Pest
- `/devices` — add Pest + Bruno

After retrofit: every endpoint in the entire API meets the same bar.

---

## TL;DR for every step file from Week 3 onwards

At the end of each step file, the "Verification" section must now include:

```
- [ ] Endpoint meets the 8-point UI-safe API checklist
- [ ] Pest test covers happy + at least 2 error paths
- [ ] Bruno .bru file added to docs/bruno/kudla-api-v1/{group}/
- [ ] Scribe @response blocks for every documented error code
- [ ] Contract snapshot captures response shape
```

When Flutter dev starts Phase 2b, they should be able to pick any endpoint from
the catalogue, read one `@response` block, know exactly what to expect.
