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

    // Public endpoints added in later steps:
    // - /site/settings                  (step 6)
    // - /reference/{list}               (step 7)
    // - /auth/register/step-1           (week 2)
    // - /auth/otp/*/send, /verify       (week 2)
    // - /auth/login/*                   (week 2)
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

        // Protected endpoints added in weeks 2–4
    });
});
