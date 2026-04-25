<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
| All routes are prefixed with /api/v1/ (the /api/ prefix is applied
| automatically by Laravel; we add /v1/ here for versioning).
|
| Design reference: docs/mobile-app/design/01-api-foundations.md
|
| Route groups grow through Phase 2a weeks 2–4. This skeleton establishes
| the /v1/ prefix + public vs auth separation + a working health + ping.
*/

Route::prefix('v1')->group(function () {

    // ── Public (no auth) ────────────────────────────────────────
    Route::get('/health', fn () => ApiResponse::ok([
        'status' => 'ok',
        'version' => 'v1',
    ]));

    // Site configuration (step 6) — theme, branding, feature toggles,
    // Razorpay key, app version gates. Flutter calls on every launch.
    Route::get('/site/settings', [\App\Http\Controllers\Api\V1\SiteSettingsController::class, 'show']);

    // Reference dropdown data (step 7) — castes, occupations, countries, etc.
    // Supports ?flat=1 (flatten grouped) and ?options=1 (key-value pairs).
    Route::get('/reference', [\App\Http\Controllers\Api\V1\ReferenceDataController::class, 'index']);
    Route::get('/reference/{list}', [\App\Http\Controllers\Api\V1\ReferenceDataController::class, 'show'])
        ->where('list', '[a-z-]+');

    // Auth — registration (week 2 step 6+). Step 1 creates the account
    // and returns a Sanctum token that authenticates steps 2-5.
    Route::post('/auth/register/step-1', [\App\Http\Controllers\Api\V1\RegistrationController::class, 'step1']);

    // Phone OTP (week 2 step 8) — 3 purposes: register|login|reset.
    // Rate limits prevent SMS spam + OTP brute force.
    Route::post('/auth/otp/phone/send', [\App\Http\Controllers\Api\V1\AuthController::class, 'sendPhoneOtp'])
        ->middleware('throttle:5,1');   // 5 sends per minute per IP
    Route::post('/auth/otp/phone/verify', [\App\Http\Controllers\Api\V1\AuthController::class, 'verifyPhoneOtp'])
        ->middleware('throttle:10,1');  // 10 verifies per minute per IP

    // Email OTP (week 2 step 9) — mirror of phone OTP.
    Route::post('/auth/otp/email/send', [\App\Http\Controllers\Api\V1\AuthController::class, 'sendEmailOtp'])
        ->middleware('throttle:5,1');
    Route::post('/auth/otp/email/verify', [\App\Http\Controllers\Api\V1\AuthController::class, 'verifyEmailOtp'])
        ->middleware('throttle:10,1');

    // Login — email + password (week 2 step 10). Primary login flow.
    // Login via phone / email OTP is handled by the purpose=login branch of
    // /auth/otp/phone/verify and /auth/otp/email/verify (steps 8/9) — no
    // separate endpoints needed.
    Route::post('/auth/login/password', [\App\Http\Controllers\Api\V1\AuthController::class, 'loginPassword'])
        ->middleware('throttle:10,1');

    // Forgot + reset password (week 2 step 13). Uses Laravel's Password
    // broker — same reset-link tokens the web app uses.
    Route::post('/auth/password/forgot', [\App\Http\Controllers\Api\V1\AuthController::class, 'forgotPassword'])
        ->middleware('throttle:5,1');
    Route::post('/auth/password/reset', [\App\Http\Controllers\Api\V1\AuthController::class, 'resetPassword'])
        ->middleware('throttle:5,1');
    // - /auth/password/forgot|reset     (week 2)
    // - /membership/plans               (week 4)
    // - /success-stories                (week 4)
    // - /static-pages/{slug}            (week 4)
    // - /discover, /discover/{cat}[/{slug}]  (week 3)
    // - /webhooks/razorpay              (week 4)
    // - /contact                        (week 4)

    // ── Auth required ───────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/ping', fn (Request $request) => ApiResponse::ok([
            'user_id' => $request->user()->id,
            'message' => 'authenticated',
        ]));

        // Registration steps 2–5 (the Sanctum token returned from step-1
        // authenticates the caller through the rest of registration).
        Route::post('/auth/register/step-2', [\App\Http\Controllers\Api\V1\RegistrationController::class, 'step2']);
        Route::post('/auth/register/step-3', [\App\Http\Controllers\Api\V1\RegistrationController::class, 'step3']);
        Route::post('/auth/register/step-4', [\App\Http\Controllers\Api\V1\RegistrationController::class, 'step4']);
        Route::post('/auth/register/step-5', [\App\Http\Controllers\Api\V1\RegistrationController::class, 'step5']);

        // /me + /logout (week 2 step 14). /me validates stored token on app launch.
        Route::get('/auth/me', [\App\Http\Controllers\Api\V1\AuthController::class, 'me']);
        Route::post('/auth/logout', [\App\Http\Controllers\Api\V1\AuthController::class, 'logout']);

        // FCM device registration (week 2 step 15). Called after each login
        // + on Firebase's onTokenRefresh. Idempotent on fcm_token.
        Route::post('/devices', [\App\Http\Controllers\Api\V1\DeviceController::class, 'register']);
        Route::delete('/devices/{device}', [\App\Http\Controllers\Api\V1\DeviceController::class, 'revoke']);

        // Dashboard (week 3 step 3). Single-call home screen — CTA, stats,
        // 4 carousels, discover teasers. Flutter calls on every app launch.
        Route::get('/dashboard', [\App\Http\Controllers\Api\V1\DashboardController::class, 'show']);

        // Own profile (week 3 step 4). Full profile with all 9 sections,
        // contact populated (self-view). Used by the profile screen.
        Route::get('/profile/me', [\App\Http\Controllers\Api\V1\ProfileController::class, 'me']);

        // View another profile by matri_id (week 3 step 5). Applies all
        // 7 ProfileAccessService gates, tracks a 24h-deduped ProfileView,
        // returns viewer-context (match score, interest status, etc.).
        // matri_id is alphanumeric uppercase (AM###### convention).
        Route::get('/profiles/{matriId}', [\App\Http\Controllers\Api\V1\ProfileController::class, 'show'])
            ->where('matriId', '[A-Z0-9]+');

        // Update a single profile section (week 3 step 6). Nine allowed
        // section names — the whereIn locks the router so bad section
        // names 404 before the controller even runs. Throttled to
        // 30 edits/min per authenticated user.
        Route::put('/profile/me/{section}', [\App\Http\Controllers\Api\V1\ProfileController::class, 'updateSection'])
            ->whereIn('section', [
                'primary', 'religious', 'education', 'family', 'location',
                'contact', 'hobbies', 'social', 'partner',
            ])
            ->middleware('throttle:30,1');

        // Partner search (week 3 step 12). 17+ query-param filters,
        // 5 sort modes, paginated (default 20, max 50). Throttled to
        // 60/min/user — search is chatty during typing / filter-chip
        // toggling but shouldn't exceed 1/sec sustained.
        Route::get('/search/partner', [\App\Http\Controllers\Api\V1\SearchController::class, 'partner'])
            ->middleware('throttle:60,1');

        // Photo-request lifecycle (week 3 step 11). send is throttled
        // (20/min) to prevent spam; list/approve/ignore are unthrottled.
        // Send uses matri_id in the URL so Flutter can open a profile
        // view and kick off a request without needing the numeric profile id.
        Route::get('/photo-requests', [\App\Http\Controllers\Api\V1\PhotoRequestController::class, 'index']);
        Route::post('/profiles/{matriId}/photo-request', [\App\Http\Controllers\Api\V1\PhotoRequestController::class, 'send'])
            ->where('matriId', '[A-Z0-9]+')
            ->middleware('throttle:20,1');
        Route::post('/photo-requests/{photoRequest}/approve', [\App\Http\Controllers\Api\V1\PhotoRequestController::class, 'approve']);
        Route::post('/photo-requests/{photoRequest}/ignore', [\App\Http\Controllers\Api\V1\PhotoRequestController::class, 'ignore']);

        // Photo CRUD (week 3 step 9). Upload is throttled to 20/hour per
        // user to prevent storage abuse; other routes unthrottled. DELETE
        // soft-archives with a 30-day undo window; /permanent hard-deletes
        // + wipes all 4 storage variants.
        Route::get('/photos', [\App\Http\Controllers\Api\V1\PhotoController::class, 'index']);
        Route::post('/photos', [\App\Http\Controllers\Api\V1\PhotoController::class, 'upload'])
            ->middleware('throttle:20,60');
        // Photo privacy toggle (week 3 step 10). Registered BEFORE the
        // /{photo}/... routes so "/privacy" isn't captured as a photo id.
        Route::post('/photos/privacy', [\App\Http\Controllers\Api\V1\PhotoController::class, 'updatePrivacy']);
        Route::post('/photos/{photo}/primary', [\App\Http\Controllers\Api\V1\PhotoController::class, 'setPrimary']);
        Route::post('/photos/{photo}/restore', [\App\Http\Controllers\Api\V1\PhotoController::class, 'restore']);
        Route::delete('/photos/{photo}/permanent', [\App\Http\Controllers\Api\V1\PhotoController::class, 'deletePermanent']);
        Route::delete('/photos/{photo}', [\App\Http\Controllers\Api\V1\PhotoController::class, 'destroy']);

        // Protected endpoints added in rest of weeks 2–4
    });
});
