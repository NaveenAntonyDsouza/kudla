# Step 6 — First Real Endpoint: `GET /api/v1/site/settings`

## Goal
Build the first real, production-meaningful endpoint. The Flutter app calls this on every launch to get theme colors, feature toggles, branding, support contact info. Sets the pattern for every future endpoint.

## Prerequisites
- [ ] [step-05 — ForceJsonResponse](step-05-force-json-middleware.md) complete
- [ ] Understanding of `SiteSetting` model (`app/Models/SiteSetting.php`)
- [ ] Design reference: [`design/09-engagement-api.md §9.9`](../../design/09-engagement-api.md)

## Procedure

### 1. Create controller directory + base class

```bash
mkdir -p app/Http/Controllers/Api/V1
mkdir -p app/Http/Resources/V1
```

Create `app/Http/Controllers/Api/V1/BaseApiController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

/**
 * Base for all API v1 controllers.
 *
 * Not strictly required (controllers can extend App\Http\Controllers\Controller
 * directly), but gives us a single place to add API-wide helpers later
 * (e.g., resolveProfile, requireAdmin, etc.)
 */
abstract class BaseApiController extends Controller
{
    //
}
```

### 2. Create the SiteSettings controller

Create `app/Http/Controllers/Api/V1/SiteSettingsController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SiteSettingsController extends BaseApiController
{
    /**
     * GET /api/v1/site/settings
     *
     * Public endpoint. Flutter app calls on every launch + on each resume
     * after 1h idle. Cached 5 min server-side.
     */
    public function show(): JsonResponse
    {
        $data = Cache::remember('api:v1:site-settings', now()->addMinutes(5), function () {
            return [
                'site' => [
                    'name' => SiteSetting::getValue('site_name', config('app.name')),
                    'tagline' => SiteSetting::getValue('tagline', ''),
                    'logo_url' => self::absUrl(SiteSetting::getValue('logo_url')),
                    'favicon_url' => self::absUrl(SiteSetting::getValue('favicon_url')),
                    'support_email' => SiteSetting::getValue('support_email', ''),
                    'support_phone' => SiteSetting::getValue('support_phone', ''),
                    'support_whatsapp' => SiteSetting::getValue('support_whatsapp', ''),
                    'address' => SiteSetting::getValue('address', ''),
                ],
                'theme' => [
                    'primary_color' => SiteSetting::getValue('primary_color', '#dc2626'),
                    'secondary_color' => SiteSetting::getValue('secondary_color', '#fbbf24'),
                    'heading_font' => SiteSetting::getValue('heading_font', 'Playfair Display'),
                    'body_font' => SiteSetting::getValue('body_font', 'Inter'),
                ],
                'features' => [
                    'email_otp_login_enabled' => SiteSetting::getValue('email_otp_login_enabled', '0') === '1',
                    'mobile_otp_login_enabled' => SiteSetting::getValue('mobile_otp_login_enabled', '1') === '1',
                    'email_verification_required' => SiteSetting::getValue('email_verification_enabled', '1') === '1',
                    'phone_verification_required' => SiteSetting::getValue('phone_verification_enabled', '0') === '1',
                    'horoscope_enabled' => SiteSetting::getValue('horoscope_enabled', '0') === '1',
                    'realtime_chat_enabled' => false,  // v1 = polling
                    'auto_approve_profiles' => SiteSetting::getValue('auto_approve_profiles', '1') === '1',
                ],
                'registration' => [
                    'min_age' => (int) config('matrimony.registration_min_age', 18),
                    'password_min_length' => (int) config('matrimony.password_min_length', 6),
                    'password_max_length' => (int) config('matrimony.password_max_length', 14),
                    'id_prefix' => config('matrimony.id_prefix', 'AM'),
                ],
                'membership' => [
                    'razorpay_key' => SiteSetting::getValue('razorpay_key_id', ''),
                    'currency' => 'INR',
                ],
                'app' => [
                    'minimum_supported_version' => SiteSetting::getValue('app_min_version', '1.0.0'),
                    'latest_version' => SiteSetting::getValue('app_latest_version', '1.0.0'),
                    'force_upgrade_below' => SiteSetting::getValue('app_force_upgrade_below', '1.0.0'),
                    'play_store_url' => SiteSetting::getValue('play_store_url', ''),
                    'app_store_url' => SiteSetting::getValue('app_store_url', ''),
                ],
                'social_links' => [
                    'facebook' => SiteSetting::getValue('facebook_url', ''),
                    'instagram' => SiteSetting::getValue('instagram_url', ''),
                    'youtube' => SiteSetting::getValue('youtube_url', ''),
                    'linkedin' => SiteSetting::getValue('linkedin_url', ''),
                ],
                'policies' => [
                    'privacy_policy_url' => url('/privacy-policy'),
                    'terms_url' => url('/terms-condition'),
                    'refund_policy_url' => url('/refund-policy'),
                    'child_safety_url' => url('/child-safety'),
                ],
            ];
        });

        return ApiResponse::ok($data);
    }

    /**
     * Resolve a storage path or full URL into an absolute URL.
     */
    private static function absUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        // Stored as 'branding/logo.png' or '/storage/branding/logo.png'
        $path = ltrim($path, '/');
        if (! str_starts_with($path, 'storage/')) {
            $path = 'storage/' . $path;
        }
        return url($path);
    }
}
```

### 3. Register route

Open `routes/api.php`. Replace the public section:

```php
// ── Public (no auth) ────────────────────────────────────────
Route::get('/health', fn () => ApiResponse::ok(['status' => 'ok', 'version' => 'v1']));

Route::get('/site/settings', [
    \App\Http\Controllers\Api\V1\SiteSettingsController::class,
    'show',
]);
```

### 4. Test with curl

```bash
curl -H "Accept: application/json" http://localhost:8000/api/v1/site/settings | jq
```

Expected: envelope-shaped JSON with 9 top-level keys under `data` (site, theme, features, registration, membership, app, social_links, policies).

### 5. Write a Pest feature test

Create `tests/Feature/Api/V1/SiteSettingsTest.php`:

```php
<?php

use App\Models\SiteSetting;
use function Pest\Laravel\getJson;

beforeEach(function () {
    cache()->forget('api:v1:site-settings');
    SiteSetting::set('site_name', 'Test Matrimony');
    SiteSetting::set('primary_color', '#ff0000');
    SiteSetting::set('support_email', 'test@example.com');
});

it('returns site settings with correct envelope', function () {
    $response = getJson('/api/v1/site/settings');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'site' => ['name', 'tagline', 'logo_url', 'support_email'],
                'theme' => ['primary_color', 'secondary_color'],
                'features' => ['email_otp_login_enabled', 'realtime_chat_enabled'],
                'registration' => ['min_age', 'password_min_length'],
                'membership' => ['razorpay_key', 'currency'],
                'app' => ['minimum_supported_version'],
                'social_links',
                'policies',
            ],
        ])
        ->assertJson([
            'success' => true,
            'data' => [
                'site' => ['name' => 'Test Matrimony'],
                'theme' => ['primary_color' => '#ff0000'],
            ],
        ]);
});

it('always returns realtime_chat_enabled as false in v1', function () {
    SiteSetting::set('realtime_chat_enabled', '1');  // try to enable via DB

    $response = getJson('/api/v1/site/settings');

    $response->assertJson([
        'data' => ['features' => ['realtime_chat_enabled' => false]],
    ]);
});
```

> **Note:** if `SiteSetting::set()` doesn't exist on your model, use `SiteSetting::updateOrCreate(['key' => 'site_name'], ['value' => 'Test Matrimony'])` or similar — check `app/Models/SiteSetting.php` for the actual API.

### 6. Run tests

```bash
./vendor/bin/pest --filter=SiteSettings
```

## Verification

- [ ] `curl /api/v1/site/settings` returns envelope JSON with all 8 sections
- [ ] Pest tests pass
- [ ] Response cached — second call is faster (< 50ms)
- [ ] Cache clears when settings change (manual: edit a setting in admin panel, the 5 min cache will expire; for instant change add `Cache::forget` calls in site settings save logic later)

## Common issues

| Issue | Fix |
|-------|-----|
| `SiteSetting::getValue` not defined | Check actual API in `app/Models/SiteSetting.php`. May be `SiteSetting::where('key', 'x')->value('value')` |
| `logo_url` comes back as relative path | Check `absUrl()` helper logic. Test with both full URL and path-only values |
| Cache not clearing | `php artisan cache:clear` manually; or lower TTL during dev |
| Empty response | Look at `SiteSetting` factory or seeded data — may need to run `php artisan db:seed` |

## Commit

```bash
git add app/Http/Controllers/Api/V1/BaseApiController.php \
        app/Http/Controllers/Api/V1/SiteSettingsController.php \
        routes/api.php \
        tests/Feature/Api/V1/SiteSettingsTest.php
git commit -m "phase-2a wk-01: step-06 GET /api/v1/site/settings endpoint"
```

## Next step
→ [step-07-reference-data-endpoints.md](step-07-reference-data-endpoints.md)
