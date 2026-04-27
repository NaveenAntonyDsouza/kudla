# Step 5 — `ForceJsonResponse` Middleware

## Goal
Ensure every `/api/*` request receives JSON responses even if the client forgot to send `Accept: application/json`. Without this, Laravel may return Blade views for errors (e.g., 419 CSRF error page) when the Accept header isn't set.

## Prerequisites
- [ ] [step-04 — API exception handler](step-04-api-exception-handler.md) complete

## Procedure

### 1. Create middleware

Create file: `app/Http/Middleware/ForceJsonResponse.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Prepend Accept: application/json on all /api/* requests.
     *
     * This guarantees Laravel's error handling produces JSON even if the
     * client forgot the header.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');
        return $next($request);
    }
}
```

### 2. Register on the api middleware group

Open `bootstrap/app.php` and update the `->withMiddleware(...)` block:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(prepend: [
        \App\Http\Middleware\ForceJsonResponse::class,
    ]);
})
```

### 3. Clear config

```bash
php artisan config:clear
php artisan route:clear
```

### 4. Test

```bash
# Send request WITHOUT Accept header
curl -i http://localhost:8000/api/v1/health

# Expected: Content-Type: application/json (not text/html)
# Body: envelope JSON
```

Also try a bad URL:

```bash
curl -i http://localhost:8000/api/v1/no-such-endpoint

# Expected: JSON 404 envelope, not Laravel's HTML error page
```

### 5. Add Pest test

Append to `tests/Feature/Api/V1/EnvelopeShapeTest.php`:

```php
it('returns JSON even when Accept header omitted', function () {
    $response = $this->get('/api/v1/health');  // no Accept header

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJson(['success' => true]);
});
```

Run:

```bash
./vendor/bin/pest --filter=EnvelopeShape
```

## Verification

- [ ] `curl` without `Accept` header still returns JSON
- [ ] Contract test passes
- [ ] `php artisan route:list --path=api` shows `App\Http\Middleware\ForceJsonResponse` on api group

## Common issues

| Issue | Fix |
|-------|-----|
| Still getting HTML | Middleware registration order — `prepend:` puts it first. If using `append:`, the exception handler fires before and may render HTML. Use `prepend:` |
| 419 CSRF token mismatch | Sanctum excludes `/api/*` from CSRF by default. If you see this, check `config/sanctum.php` has `'api'` in `stateful` is NOT set for our prefix — mobile app is stateless, doesn't need CSRF |

## Commit

```bash
git add app/Http/Middleware/ForceJsonResponse.php bootstrap/app.php tests/Feature/Api/V1/EnvelopeShapeTest.php
git commit -m "phase-2a wk-01: step-05 ForceJsonResponse middleware"
```

## Next step
→ [step-06-site-settings-endpoint.md](step-06-site-settings-endpoint.md)
