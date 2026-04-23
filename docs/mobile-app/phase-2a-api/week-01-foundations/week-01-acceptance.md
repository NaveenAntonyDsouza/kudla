# Week 1 Acceptance Checkpoint

Before starting Week 2, verify everything below. If anything is red, fix it now — Week 2 depends on this foundation.

---

## Structural checklist

- [ ] Branch `phase-2-mobile` created and used for all week-1 commits
- [ ] 8 commits on branch, one per step, with standard message format `phase-2a wk-01: step-NN [title]`
- [ ] No merges to `main` yet (Phase 2a ships as one unit)

## Code checklist

- [ ] `composer show laravel/sanctum` returns a version
- [ ] `config/sanctum.php` exists with expiration configured
- [ ] `App\Models\User` uses `HasApiTokens` trait
- [ ] `routes/api.php` has `/v1/` prefix group
- [ ] `App\Http\Responses\ApiResponse` exists with ok/error/paginated/created methods
- [ ] `App\Exceptions\ApiExceptionHandler` exists and handles 7+ exception types
- [ ] `bootstrap/app.php` wires the exception handler
- [ ] `App\Http\Middleware\ForceJsonResponse` registered on api middleware group
- [ ] `App\Http\Controllers\Api\V1\BaseApiController` exists as abstract base
- [ ] `App\Http\Controllers\Api\V1\SiteSettingsController` returns envelope-shaped settings
- [ ] `App\Http\Controllers\Api\V1\ReferenceDataController` serves 28 list types
- [ ] Scribe config at `config/scribe.php`
- [ ] `public/docs/index.html` generated and serves real content

## Runtime checklist

Run each and confirm output:

### Health endpoint
```bash
curl -s http://localhost:8000/api/v1/health | jq
```
✓ Returns `{"success":true,"data":{"status":"ok","version":"v1"}}`

### Site settings
```bash
curl -s http://localhost:8000/api/v1/site/settings | jq '.data | keys'
```
✓ Returns 8 keys: `app`, `features`, `membership`, `policies`, `registration`, `site`, `social_links`, `theme`

### Reference data
```bash
curl -s http://localhost:8000/api/v1/reference/religions | jq
```
✓ Returns `{"success":true,"data":[{"slug":"...","label":"..."}]}` or flat array

### Cascading
```bash
curl -s "http://localhost:8000/api/v1/reference/castes?religion=Hindu" | jq
```
✓ Returns non-empty array of caste objects

### 404 envelope
```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8000/api/v1/nonexistent
```
✓ Returns `404`

```bash
curl -s http://localhost:8000/api/v1/nonexistent | jq
```
✓ Returns `{"success":false,"error":{"code":"NOT_FOUND","message":"..."}}`

### 401 envelope (on protected route)
```bash
curl -s http://localhost:8000/api/v1/auth/ping | jq
```
✓ Returns `{"success":false,"error":{"code":"UNAUTHENTICATED","message":"..."}}`

### Bearer auth works
```bash
# Create token via tinker, then:
TOKEN="<your-token>"
curl -s -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/v1/auth/ping | jq
```
✓ Returns `{"success":true,"data":{"user_id":N,"message":"authenticated"}}`

## Test checklist

```bash
./vendor/bin/pest --filter="EnvelopeShape|SiteSettings|ReferenceData"
```
✓ All tests pass — minimum 8 assertions

```bash
./vendor/bin/pest --coverage
```
✓ Coverage report runs (target coverage will grow through phase; week 1 baseline is OK at ~20%)

## Docs checklist

- [ ] Browse to `http://localhost:8000/docs` — see Scribe docs with 2 endpoints
- [ ] `public/docs.openapi.yaml` validates at https://editor.swagger.io/
- [ ] `public/docs.postman.json` imports into Postman correctly

## Deploy dry-run

Not deploying yet (Week 2 depends on these foundations), but validate:

```bash
# Check composer.json has all new deps
composer validate

# Check tests still green with prod-like config
APP_ENV=staging php artisan config:cache
./vendor/bin/pest
php artisan config:clear
```

✓ All of the above without errors

## Go/No-Go for Week 2

If ALL checks above are green:
- ✅ **GO** — proceed to [phase-2a-api/week-02-auth-registration/README.md](../week-02-auth-registration/README.md)

If any red:
- 🛑 **NO-GO** — fix and re-verify. Common blockers:
  - Scribe docs not rendering → likely asset path issue
  - Exception handler returning HTML → middleware registration order in `bootstrap/app.php`
  - Tests failing on CI but passing locally → env differences, check `.env.testing`

Ping Claude with the blocker if stuck > 30 min.

---

**Week 1 complete ✅ → Next: [Week 2 — Auth & Registration](../week-02-auth-registration/README.md)**
