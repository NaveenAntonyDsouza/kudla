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

        // Protected endpoints added in rest of weeks 2–4
    });
});
