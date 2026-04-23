# Step 8 — Install Scribe for API Documentation

## Goal
Install Scribe to auto-generate API documentation (OpenAPI 3.1, Postman collection, human-readable HTML) from Laravel code. Every future endpoint we add will be documented automatically — zero manual doc maintenance.

## Prerequisites
- [ ] [step-07 — Reference data endpoints](step-07-reference-data-endpoints.md) complete
- [ ] You have 2 endpoints working (`/site/settings` + `/reference/{list}`) — Scribe needs real endpoints to document

## Procedure

### 1. Install Scribe

```bash
composer require --dev knuckleswtf/scribe
```

### 2. Publish config

```bash
php artisan vendor:publish --tag=scribe-config
```

Creates `config/scribe.php`.

### 3. Configure for our API

Open `config/scribe.php` and make these edits:

```php
// Title shown on docs page
'title' => 'MatrimonyTheme API v1',

// Description shown on docs homepage
'description' => 'REST API for MatrimonyTheme mobile app. Base URL: /api/v1',

// Only document /api/v1/* routes
'routes' => [
    [
        'match' => [
            'prefixes' => ['api/v1/*'],
            'domains' => ['*'],
        ],
        'include' => [],
        'exclude' => [],
        'apply' => [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'response_calls' => [
                'methods' => ['GET'],
                'config' => [
                    'app.env' => 'documentation',  // avoid side effects
                ],
            ],
        ],
    ],
],

// Use static HTML + OpenAPI spec output
'type' => 'static',

'static' => [
    'output_path' => 'public/docs',
],

// Auth config — Bearer token on protected routes
'auth' => [
    'enabled' => true,
    'default' => false,  // individual controllers/routes declare auth needs
    'in' => 'bearer',
    'name' => 'Authorization',
    'use_value' => env('SCRIBE_AUTH_TOKEN'),
    'placeholder' => '{YOUR_BEARER_TOKEN}',
    'extra_info' => 'Obtain a token via `POST /api/v1/auth/login/*` endpoints.',
],

// Also output OpenAPI + Postman
'postman' => [
    'enabled' => true,
    'overrides' => ['info.version' => '1.0.0'],
],
'openapi' => [
    'enabled' => true,
    'overrides' => ['info.version' => '1.0.0'],
],
```

### 4. Annotate existing endpoints

Scribe reads PHPDoc blocks + FormRequest validation rules. Let's annotate the two endpoints we have.

Open `app/Http/Controllers/Api/V1/SiteSettingsController.php` and update the `show()` method's PHPDoc:

```php
/**
 * Get site settings
 *
 * Returns site configuration: branding, theme colors, feature toggles,
 * registration rules, Razorpay key, support contact, policy URLs, and
 * mobile app version gates. The Flutter app fetches this on every
 * launch and applies theme + feature toggles.
 *
 * Cached server-side for 5 minutes.
 *
 * @unauthenticated
 *
 * @group Configuration
 *
 * @response 200 scenario="success" {
 *   "success": true,
 *   "data": {
 *     "site": {
 *       "name": "Kudla Matrimony",
 *       "tagline": "Find Your Perfect Match",
 *       "logo_url": "https://kudlamatrimony.com/storage/branding/logo.png",
 *       "support_email": "support@kudlamatrimony.com",
 *       "support_phone": "+91-824-1234567"
 *     },
 *     "theme": { "primary_color": "#dc2626" },
 *     "features": { "realtime_chat_enabled": false }
 *   }
 * }
 */
public function show(): JsonResponse
{
    // ... existing body
}
```

Similarly, annotate `ReferenceDataController::show()`:

```php
/**
 * Get reference list (dropdown data)
 *
 * Returns reference data for dropdowns (religions, castes, occupations, etc.).
 * Some lists cascade — e.g., `castes` requires `?religion=Hindu`.
 *
 * Cached server-side for 1 hour.
 *
 * @unauthenticated
 *
 * @group Configuration
 *
 * @urlParam list string required The list name. Example: religions
 * @queryParam religion string Required for castes/denominations. Example: Hindu
 * @queryParam caste string Required for sub-castes. Example: Brahmin
 * @queryParam country string Required for states. Example: India
 * @queryParam state string Required for districts. Example: Karnataka
 *
 * @response 200 scenario="success" {
 *   "success": true,
 *   "data": [
 *     { "slug": "hindu", "label": "Hindu" },
 *     { "slug": "christian", "label": "Christian" }
 *   ]
 * }
 *
 * @response 404 scenario="unknown list" {
 *   "success": false,
 *   "error": {
 *     "code": "NOT_FOUND",
 *     "message": "Reference list 'foo' does not exist."
 *   }
 * }
 */
public function show(Request $request, string $list, ReferenceDataService $refs): JsonResponse
{
    // ... existing body
}
```

### 5. Generate docs

```bash
php artisan scribe:generate
```

Output:
- `public/docs/` — HTML docs
- `public/docs.openapi.yaml` — OpenAPI spec
- `public/docs.postman.json` — Postman collection

### 6. View docs

In browser: `http://localhost:8000/docs`

You should see a two-endpoint doc with "Configuration" group, "Try it out" buttons, etc.

### 7. Add docs gate in production

Create middleware to protect `/docs` in production behind admin auth.

Create `app/Http/Middleware/ProtectApiDocsInProd.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectApiDocsInProd
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            abort_if(
                ! $request->user() || ! $request->user()->hasRole('Super Admin'),
                404,  // return 404, not 403 — don't reveal existence
            );
        }
        return $next($request);
    }
}
```

Then in `routes/web.php`, protect the docs:

```php
// Scribe docs (auto-generated API reference)
Route::get('/docs', fn () => response()->file(public_path('docs/index.html')))
    ->middleware(\App\Http\Middleware\ProtectApiDocsInProd::class);

Route::get('/docs.openapi.yaml', fn () => response()->file(public_path('docs.openapi.yaml'))
    ->header('Content-Type', 'text/yaml'))
    ->middleware(\App\Http\Middleware\ProtectApiDocsInProd::class);
```

> **Note:** by default Scribe serves `/docs` via its own routes. If that conflicts with the above, check `config/scribe.php`'s `'laravel.add_routes' => false` setting.

### 8. Add to deploy flow

Edit `deploy-build.ps1` (or create a post-deploy hook):

Add a step that runs `php artisan scribe:generate` after composer install on the server. This keeps `/docs` current on every deploy.

For now, document the TODO:
```
# After Week 4 acceptance:
# - Update deploy-build.ps1 to run `php artisan scribe:generate` post-migration
```

## Verification

- [ ] `php artisan scribe:generate` completes without errors
- [ ] `public/docs/index.html` exists
- [ ] `public/docs.openapi.yaml` exists
- [ ] `public/docs.postman.json` exists
- [ ] Browse to `/docs` locally — see 2 endpoints with the "Configuration" group
- [ ] OpenAPI spec validates (paste into https://editor.swagger.io/ — no errors)
- [ ] Postman collection imports correctly (open in Postman — 2 endpoints visible)

## Common issues

| Issue | Fix |
|-------|-----|
| `scribe:generate` fails with "Could not connect to API" | Scribe tries to make real HTTP calls to get example responses. Add `'response_calls.config.app.env' => 'documentation'` and manually write `@response` blocks instead |
| Docs page 404 | Check `routes/web.php` has the docs route OR check `config/scribe.php` `'laravel.add_routes'` = true |
| OpenAPI spec missing some endpoints | Confirm `routes.prefixes` matches `api/v1/*` not just `api/*` |
| Docs look broken / CSS missing | Run `php artisan storage:link`; or check `public/docs/` has the full asset tree |

## Commit

```bash
git add composer.json composer.lock config/scribe.php app/Http/Controllers/Api/V1/ routes/web.php app/Http/Middleware/ProtectApiDocsInProd.php public/docs/
git commit -m "phase-2a wk-01: step-08 Scribe API documentation"
```

> **⚠️ .gitignore check:** `public/docs/` is regenerated on every deploy — you may want to add `public/docs/` to `.gitignore` and regenerate on deploy instead of committing. Decide based on your deploy-build.ps1 workflow.

## Next step
→ [week-01-acceptance.md](week-01-acceptance.md) — end-of-week checkpoint
