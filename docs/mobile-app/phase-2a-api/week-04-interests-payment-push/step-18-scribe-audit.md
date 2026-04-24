# Step 18 — Scribe Completeness Audit + OpenAPI Publish

## Goal
Every `/api/v1/*` endpoint has:
- At least one `@response` block (happy path)
- At least one non-2xx `@response` (documented error case)
- Every query/url/body param documented via `@queryParam` / `@urlParam` / `@bodyParam`

This is the final gate on "UI-safe API". A Flutter dev reading the
OpenAPI spec should be able to write client code for any endpoint without
opening the PHP source.

## Prerequisites
- [ ] Step 17 (contract snapshots) complete

## Procedure

### 1. Write the audit script

`tests/Feature/Api/V1/ScribeCompletenessTest.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

/**
 * Fails if any /api/v1/* endpoint is under-documented in Scribe.
 * Run: ./vendor/bin/pest --filter=ScribeCompleteness
 */
it('every /api/v1/* endpoint has Scribe documentation', function () {
    $apiRoutes = collect(Route::getRoutes())
        ->filter(fn ($r) => str_starts_with($r->uri(), 'api/v1/'))
        // Exclude internal smoke-test endpoints from the audit
        ->reject(fn ($r) => in_array($r->uri(), ['api/v1/health', 'api/v1/auth/ping']));

    $issues = [];
    foreach ($apiRoutes as $route) {
        $action = $route->getAction();
        $controllerMethod = $action['controller'] ?? null;
        if (! $controllerMethod || ! str_contains($controllerMethod, '@')) {
            continue;  // closure routes — skip (there shouldn't be any)
        }

        [$class, $method] = explode('@', $controllerMethod);
        $refl = new ReflectionMethod($class, $method);
        $doc = $refl->getDocComment() ?: '';

        $path = $route->methods()[0] . ' ' . $route->uri();

        // 1. Must have at least one @response
        if (! preg_match('/@response\s+\d+/', $doc)) {
            $issues[] = "{$path}: missing @response block";
            continue;
        }

        // 2. Must document at least one error case (non-2xx status)
        preg_match_all('/@response\s+(\d+)/', $doc, $matches);
        $statuses = $matches[1] ?? [];
        $hasError = count(array_filter($statuses, fn ($s) => $s >= 400)) > 0;
        if (! $hasError) {
            $issues[] = "{$path}: no @response for error cases (4xx/5xx)";
        }

        // 3. Must document URL/query params
        $uri = $route->uri();
        preg_match_all('/\{(\w+)\}/', $uri, $urlMatches);
        foreach ($urlMatches[1] ?? [] as $param) {
            if (! preg_match('/@urlParam\s+' . preg_quote($param) . '\b/', $doc)) {
                $issues[] = "{$path}: @urlParam '{$param}' missing";
            }
        }
    }

    expect($issues)->toBeEmpty(
        "Scribe documentation incomplete:\n  - " . implode("\n  - ", $issues),
    );
});
```

### 2. Run it

```bash
./vendor/bin/pest --filter=ScribeCompleteness
```

Expected: first run fails loudly listing every under-documented endpoint.

### 3. Fix the failures

For each flagged endpoint, add the missing annotations. Common patterns:

```php
/**
 * @response 200 scenario="success" { "success": true, "data": {...} }
 * @response 422 scenario="validation" { "success": false, "error": { "code": "VALIDATION_FAILED", "message": "...", "fields": {...} } }
 * @response 401 scenario="unauthenticated" { "success": false, "error": { "code": "UNAUTHENTICATED", "message": "..." } }
 */
```

Iterate until test passes.

### 4. Regenerate Scribe docs

```bash
php artisan scribe:generate
```

### 5. Verify OpenAPI + Postman

- Open `public/docs.openapi.yaml` in [editor.swagger.io](https://editor.swagger.io) — no errors
- Import `public/docs.postman.json` into Postman — all endpoints green
- Browse `/docs` — every endpoint shows full error catalog

### 6. Commit

```bash
git add tests/Feature/Api/V1/ScribeCompletenessTest.php \
        app/Http/Controllers/Api/V1 \
        public/docs public/docs.openapi.yaml public/docs.postman.json
git commit -m "phase-2a wk-04: step-18 Scribe audit + OpenAPI publish (100% coverage)"
```

## Verification

- [ ] ScribeCompletenessTest passes
- [ ] OpenAPI spec validates at editor.swagger.io
- [ ] Postman import works
- [ ] Browsing `/docs` shows error codes for every endpoint

## Week 4 acceptance

With steps 15–18 green, Phase 2a is **UI-safe complete**:

- ~82 endpoints all meet the 8-point UI-safe checklist
- Bruno collection green (step-16)
- Contract snapshots passing (step-17)
- Scribe docs 100% complete (step-18)

Proceed to `week-04-acceptance.md` for the Phase 2a exit gate.
