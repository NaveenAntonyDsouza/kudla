# 1. API Foundations

**Goal:** stand up the scaffolding that every subsequent endpoint will sit on. Do this once, get it right.

---

## 1.1 Package Installation

```
composer require laravel/sanctum
php artisan install:api          # publishes routes/api.php, config/sanctum.php
                                 # adds HasApiTokens trait guidance
php artisan migrate              # creates personal_access_tokens table
```

What `install:api` does in Laravel 11+/13:
- Creates `routes/api.php` with `api` prefix and `EnsureFrontendRequestsAreStateful` + `throttle:api` + `SubstituteBindings` middleware
- Registers the `api` middleware group in `bootstrap/app.php` (`->withRouting(api: ...)`)
- Publishes `config/sanctum.php`

---

## 1.2 Folder & File Layout

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/                    ← all mobile API controllers go here
│   │           ├── AuthController.php
│   │           ├── RegistrationController.php
│   │           ├── OnboardingController.php
│   │           ├── ProfileController.php
│   │           ├── PhotoController.php
│   │           ├── SearchController.php
│   │           ├── DiscoverController.php
│   │           ├── InterestController.php
│   │           ├── MembershipController.php
│   │           ├── NotificationController.php
│   │           ├── ShortlistController.php
│   │           ├── BlockController.php
│   │           ├── ReportController.php
│   │           ├── IgnoredProfileController.php
│   │           ├── ProfileViewController.php
│   │           ├── IdProofController.php
│   │           ├── SettingsController.php
│   │           ├── ReferenceDataController.php
│   │           ├── DeviceController.php       ← FCM token registration
│   │           └── SuccessStoryController.php
│   ├── Resources/
│   │   └── V1/                        ← Eloquent API Resources (JSON transformers)
│   │       ├── ProfileResource.php
│   │       ├── ProfileCardResource.php        ← lightweight for lists
│   │       ├── PhotoResource.php
│   │       ├── InterestResource.php
│   │       ├── NotificationResource.php
│   │       ├── MembershipPlanResource.php
│   │       ├── UserMembershipResource.php
│   │       └── ReferenceListResource.php
│   ├── Requests/
│   │   └── Api/
│   │       └── V1/                    ← FormRequest validation
│   │           ├── RegisterStep1Request.php   ← reuse existing where possible
│   │           └── ...
│   └── Middleware/
│       └── ForceJsonResponse.php      ← ensures Accept: application/json
└── Services/                          ← UNCHANGED — API controllers call these
```

**Rule:** API controllers never touch models directly except for route-model binding. All business logic goes through `App\Services\*`. This is the "thin controller + existing services" pattern (option C from planning).

---

## 1.3 `routes/api.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1 as Api;

Route::prefix('v1')->group(function () {

    // ── Public (no auth) ────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('/register/step-1',        [Api\RegistrationController::class, 'step1']);
        Route::post('/otp/phone/send',         [Api\AuthController::class, 'sendPhoneOtp'])->middleware('throttle:5,1');
        Route::post('/otp/phone/verify',       [Api\AuthController::class, 'verifyPhoneOtp'])->middleware('throttle:10,1');
        Route::post('/otp/email/send',         [Api\AuthController::class, 'sendEmailOtp'])->middleware('throttle:5,1');
        Route::post('/otp/email/verify',       [Api\AuthController::class, 'verifyEmailOtp'])->middleware('throttle:10,1');
        Route::post('/login/password',         [Api\AuthController::class, 'loginPassword']);
        Route::post('/login/phone-otp',        [Api\AuthController::class, 'loginPhoneOtp']);
        Route::post('/login/email-otp',        [Api\AuthController::class, 'loginEmailOtp']);
        Route::post('/password/forgot',        [Api\AuthController::class, 'forgotPassword']);
        Route::post('/password/reset',         [Api\AuthController::class, 'resetPassword']);
    });

    Route::get('/reference/{list}',            [Api\ReferenceDataController::class, 'show']);  // religions, castes, etc.
    Route::get('/membership/plans',            [Api\MembershipController::class, 'plans']);    // pricing page, no auth
    Route::get('/site/settings',               [Api\ReferenceDataController::class, 'siteSettings']); // theme colors, site name, etc.
    Route::get('/success-stories',             [Api\SuccessStoryController::class, 'index']);
    Route::get('/static-pages/{slug}',         [Api\ReferenceDataController::class, 'staticPage']);
    Route::get('/discover',                    [Api\DiscoverController::class, 'hub']);
    Route::get('/discover/{category}',         [Api\DiscoverController::class, 'category']);
    Route::get('/discover/{category}/{slug}',  [Api\DiscoverController::class, 'results']);

    // ── Auth required ───────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Token lifecycle
        Route::post('/auth/logout',            [Api\AuthController::class, 'logout']);
        Route::get('/auth/me',                 [Api\AuthController::class, 'me']);
        Route::post('/devices',                [Api\DeviceController::class, 'register']);   // FCM token
        Route::delete('/devices/{device}',     [Api\DeviceController::class, 'revoke']);

        // Registration steps 2–5 (require auth, same as web)
        Route::post('/register/step-2',        [Api\RegistrationController::class, 'step2']);
        Route::post('/register/step-3',        [Api\RegistrationController::class, 'step3']);
        Route::post('/register/step-4',        [Api\RegistrationController::class, 'step4']);
        Route::post('/register/step-5',        [Api\RegistrationController::class, 'step5']);

        // Onboarding
        Route::prefix('onboarding')->group(function () {
            Route::post('/step-1',             [Api\OnboardingController::class, 'step1']);
            Route::post('/step-2',             [Api\OnboardingController::class, 'step2']);
            Route::post('/partner-preferences',[Api\OnboardingController::class, 'partnerPrefs']);
            Route::post('/lifestyle',          [Api\OnboardingController::class, 'lifestyle']);
            Route::post('/finish',             [Api\OnboardingController::class, 'finish']);
        });

        // Dashboard + profile
        Route::get('/dashboard',               [Api\ProfileController::class, 'dashboard']);
        Route::get('/profile/me',              [Api\ProfileController::class, 'me']);
        Route::put('/profile/me/{section}',    [Api\ProfileController::class, 'updateSection'])
            ->whereIn('section', ['primary','religious','education','family','location','contact','hobbies','social','partner']);
        Route::get('/profiles/{matriId}',      [Api\ProfileController::class, 'show']);

        // Photos
        Route::get('/photos',                  [Api\PhotoController::class, 'index']);
        Route::post('/photos',                 [Api\PhotoController::class, 'upload']);
        Route::post('/photos/{photo}/primary', [Api\PhotoController::class, 'setPrimary']);
        Route::delete('/photos/{photo}',       [Api\PhotoController::class, 'destroy']);
        Route::post('/photos/privacy',         [Api\PhotoController::class, 'updatePrivacy']);

        // Photo requests
        Route::get('/photo-requests',          [Api\PhotoController::class, 'listRequests']);
        Route::post('/profiles/{matriId}/photo-request', [Api\PhotoController::class, 'sendRequest']);
        Route::post('/photo-requests/{photoRequest}/approve', [Api\PhotoController::class, 'approveRequest']);
        Route::post('/photo-requests/{photoRequest}/ignore',  [Api\PhotoController::class, 'ignoreRequest']);

        // Search + discover
        Route::get('/search',                  [Api\SearchController::class, 'partner']);
        Route::get('/search/keyword',          [Api\SearchController::class, 'keyword']);
        Route::get('/search/id/{matriId}',     [Api\SearchController::class, 'byMatriId']);
        Route::get('/search/saved',            [Api\SearchController::class, 'savedList']);
        Route::post('/search/saved',           [Api\SearchController::class, 'saveSearch']);
        Route::delete('/search/saved/{savedSearch}', [Api\SearchController::class, 'deleteSaved']);
        Route::get('/matches/my',              [Api\SearchController::class, 'myMatches']);
        Route::get('/matches/mutual',          [Api\SearchController::class, 'mutualMatches']);

        // Interests + chat
        Route::get('/interests',               [Api\InterestController::class, 'index']);
        Route::get('/interests/{interest}',    [Api\InterestController::class, 'show']);
        Route::post('/profiles/{matriId}/interest', [Api\InterestController::class, 'send']);
        Route::post('/interests/{interest}/accept',  [Api\InterestController::class, 'accept']);
        Route::post('/interests/{interest}/decline', [Api\InterestController::class, 'decline']);
        Route::post('/interests/{interest}/cancel',  [Api\InterestController::class, 'cancel']);
        Route::post('/interests/{interest}/star',    [Api\InterestController::class, 'star']);
        Route::post('/interests/{interest}/trash',   [Api\InterestController::class, 'trash']);
        Route::post('/interests/{interest}/messages',[Api\InterestController::class, 'reply']);
        Route::get('/interests/{interest}/messages/since/{messageId?}', [Api\InterestController::class, 'since']);  // polling

        // Membership + payments
        Route::get('/membership/me',           [Api\MembershipController::class, 'mine']);
        Route::post('/membership/coupon/validate', [Api\MembershipController::class, 'validateCoupon']);
        Route::post('/membership/order',       [Api\MembershipController::class, 'createOrder']);
        Route::post('/membership/verify',      [Api\MembershipController::class, 'verifyPayment']);
        Route::get('/membership/history',      [Api\MembershipController::class, 'history']);

        // Shortlist / views / blocks / reports / ignores
        Route::get('/shortlist',               [Api\ShortlistController::class, 'index']);
        Route::post('/profiles/{matriId}/shortlist',   [Api\ShortlistController::class, 'toggle']);
        Route::get('/views',                   [Api\ProfileViewController::class, 'index']);
        Route::get('/blocked',                 [Api\BlockController::class, 'index']);
        Route::post('/profiles/{matriId}/block',       [Api\BlockController::class, 'block']);
        Route::post('/profiles/{matriId}/unblock',     [Api\BlockController::class, 'unblock']);
        Route::post('/profiles/{matriId}/report',      [Api\ReportController::class, 'store']);
        Route::get('/ignored',                 [Api\IgnoredProfileController::class, 'index']);
        Route::post('/profiles/{matriId}/ignore-toggle', [Api\IgnoredProfileController::class, 'toggle']);

        // ID proof
        Route::get('/id-proof',                [Api\IdProofController::class, 'show']);
        Route::post('/id-proof',               [Api\IdProofController::class, 'store']);
        Route::delete('/id-proof/{idProof}',   [Api\IdProofController::class, 'destroy']);

        // Notifications
        Route::get('/notifications',           [Api\NotificationController::class, 'index']);
        Route::post('/notifications/{notification}/read', [Api\NotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [Api\NotificationController::class, 'markAllRead']);
        Route::get('/notifications/unread-count', [Api\NotificationController::class, 'unreadCount']);

        // Settings
        Route::get('/settings',                [Api\SettingsController::class, 'index']);
        Route::put('/settings/visibility',     [Api\SettingsController::class, 'visibility']);
        Route::put('/settings/alerts',         [Api\SettingsController::class, 'alerts']);
        Route::put('/settings/password',       [Api\SettingsController::class, 'password']);
        Route::post('/settings/hide',          [Api\SettingsController::class, 'hide']);
        Route::post('/settings/unhide',        [Api\SettingsController::class, 'unhide']);
        Route::post('/settings/delete',        [Api\SettingsController::class, 'delete']);

        // Engagement
        Route::post('/success-stories',        [Api\SuccessStoryController::class, 'store']);
    });

    // ── Razorpay webhook (no auth, signature-verified) ─────────
    Route::post('/webhooks/razorpay',          [Api\MembershipController::class, 'webhook']);
});
```

Total: ~80 endpoints. Each is one method on a thin controller.

---

## 1.4 Response Envelope

**Every API response uses this shape.** Pin it in a base class — don't let individual controllers drift.

### Success

```json
{
  "success": true,
  "data": { ... },
  "meta": {                     // optional, only on paginated lists
    "page": 1,
    "per_page": 20,
    "total": 137,
    "last_page": 7
  }
}
```

### Error

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_FAILED",        // stable, never-change string code
    "message": "Please check the fields below.",   // user-safe short message
    "fields": {                          // only for validation errors
      "phone": ["Phone must be 10 digits."],
      "password": ["Password must be at least 6 characters."]
    }
  }
}
```

### Error codes (exhaustive list — add here, never deviate)

| Code | HTTP | When |
|------|------|------|
| `VALIDATION_FAILED` | 422 | FormRequest validation fails |
| `UNAUTHENTICATED` | 401 | No token / expired token |
| `UNAUTHORIZED` | 403 | Token valid but action forbidden (e.g. free user hitting premium endpoint) |
| `NOT_FOUND` | 404 | Resource missing |
| `GENDER_MISMATCH` | 403 | Cannot interact with same-gender profile |
| `SELF_ACTION` | 403 | Cannot block/report/interest self |
| `DAILY_LIMIT_REACHED` | 429 | Interest daily cap |
| `ALREADY_EXISTS` | 409 | Duplicate interest, duplicate shortlist toggle, etc. |
| `OTP_INVALID` | 422 | Wrong OTP |
| `OTP_EXPIRED` | 422 | OTP past expiry window |
| `OTP_COOLDOWN` | 429 | Resend too soon |
| `PROFILE_INCOMPLETE` | 403 | Action needs completed onboarding |
| `PROFILE_SUSPENDED` | 403 | Suspended/banned user |
| `PAYMENT_FAILED` | 400 | Razorpay signature verification failed |
| `COUPON_INVALID` | 400 | Bad/expired/exhausted coupon |
| `THROTTLED` | 429 | Rate limit hit |
| `SERVER_ERROR` | 500 | Uncaught exception |

### Implementation

Create `App\Http\Responses\ApiResponse` with static helpers:
- `ApiResponse::ok($data, $meta = null)`
- `ApiResponse::error(string $code, string $message, ?array $fields = null, int $status = 400)`
- `ApiResponse::paginated(LengthAwarePaginator $p, string $resourceClass)`

Register a handler in `bootstrap/app.php` (Laravel 11+ style):
```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (Throwable $e, Request $request) {
        if (! $request->is('api/*')) return null;       // let web handle web
        return ApiExceptionHandler::render($e);          // maps every exception to envelope
    });
})
```

`ApiExceptionHandler` maps: `ValidationException` → `VALIDATION_FAILED` / `AuthenticationException` → `UNAUTHENTICATED` / `AuthorizationException` → `UNAUTHORIZED` / `ModelNotFoundException` → `NOT_FOUND` / `ThrottleRequestsException` → `THROTTLED` / fallback → `SERVER_ERROR`.

---

## 1.5 Absolute URLs for Images

**Problem:** `PhotoStorageService::getUrl()` for the local driver returns `/storage/photos/…` — relative. Flutter cannot resolve that against the API base URL (they're often different hosts in dev).

**Fix (one-liner per Resource class):**

```php
// In App\Http\Resources\V1\PhotoResource
public function toArray($request): array {
    return [
        'id'           => $this->id,
        'url'          => Str::startsWith($this->photo_url, ['http://', 'https://'])
                            ? $this->photo_url
                            : url($this->photo_url),
        'thumbnail_url'=> /* same */,
        'medium_url'   => /* same */,
        'is_primary'   => (bool) $this->is_primary,
        'photo_type'   => $this->photo_type,
    ];
}
```

Or do it once in a Resource trait `ResolvesAbsoluteUrls` used by every Resource that touches photos.

**Env config:** `APP_URL=https://kudlamatrimony.com` must be correct in `.env`. Laravel's `url()` helper uses this.

---

## 1.6 Rate Limits

Default `throttle:api` is 60/min/IP. Keep it. Add tighter limits on sensitive endpoints:

| Endpoint | Limit | Why |
|----------|-------|-----|
| `POST /auth/otp/*/send` | 5/min/IP | SMS cost, anti-spam |
| `POST /auth/otp/*/verify` | 10/min/IP | Brute force protection |
| `POST /auth/login/*` | 10/min/IP | Credential stuffing |
| `POST /photos` | 20/hour/user | Abuse prevention |
| `POST /profiles/*/interest` | 60/hour/user | Business limit already enforced per-day; this is infra cover |
| Everything else | 60/min/user | Default |

Implement per-user throttling with `throttle:60,1,userId` style keys on auth'd routes.

---

## 1.7 CORS

Mobile app doesn't need CORS (native HTTP, no browser), but `config/cors.php` still applies if web apps call the API. Keep default:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => [env('APP_URL'), 'http://localhost:*'],
```

---

## 1.8 Middleware

**`ForceJsonResponse`** — prepends `Accept: application/json` on all `/api/*` requests so Laravel doesn't serve a Blade 404 by accident.

```php
public function handle($request, Closure $next) {
    $request->headers->set('Accept', 'application/json');
    return $next($request);
}
```

Register on the `api` group in `bootstrap/app.php`.

---

## 1.9 Versioning Discipline

- **Never break v1.** If a payload needs a field removed or renamed, add v2 and keep v1 alive for 6+ months
- **Adding is fine.** New optional fields in response → v1 stays compatible (Flutter client tolerates unknown keys via `json_serializable` default config)
- **New endpoints** live under the same prefix (`/api/v1/new-thing`)
- **Deprecation** via response header: `X-API-Deprecated: v1 retires 2027-01-01` — Flutter reads this and nudges user to upgrade app
- The Play Store store-listing "minimum supported version" field enforces floor — tied to oldest v1 client we support

---

## 1.10 Build Checklist

- [ ] `composer require laravel/sanctum`
- [ ] `php artisan install:api`
- [ ] `php artisan migrate` (personal_access_tokens)
- [ ] Add `HasApiTokens` trait to `App\Models\User`
- [ ] Create `App\Http\Controllers\Api\V1\` directory
- [ ] Create `App\Http\Resources\V1\` directory
- [ ] Create `App\Http\Responses\ApiResponse` helper class
- [ ] Create `App\Exceptions\ApiExceptionHandler`
- [ ] Register exception handler in `bootstrap/app.php`
- [ ] Create `App\Http\Middleware\ForceJsonResponse`
- [ ] Register middleware on api group
- [ ] Write `routes/api.php` with all 80 routes (stubbed controllers OK)
- [ ] Smoke test: `curl -H "Accept: application/json" https://localhost/api/v1/membership/plans` → `{"success":true,"data":[...]}`
- [ ] Write one Pest/PHPUnit integration test for envelope shape — never let it drift

**Acceptance:** hitting any `/api/v1/*` route returns either `success:true` with data OR `success:false` with a code from §1.4 — no Blade, no HTML, no stack traces in prod.
