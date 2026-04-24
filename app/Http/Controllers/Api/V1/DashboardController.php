<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\DashboardResource;
use App\Http\Responses\ApiResponse;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Flutter dashboard — one endpoint, one call per app launch.
 *
 * Returns a pre-assembled payload with 7 sections (CTA, stats, 4 carousels,
 * discover teasers). All assembly logic lives in `DashboardService`; this
 * controller's job is just auth + profile resolution + shape pass-through.
 *
 * Design reference:
 *   - docs/mobile-app/phase-2a-api/week-03-profiles-photos-search/step-03-dashboard-endpoint.md
 *   - docs/mobile-app/design/04-profile-api.md §4.2
 */
class DashboardController extends BaseApiController
{
    public function __construct(private DashboardService $dashboard) {}

    /**
     * Show the dashboard payload.
     *
     * @authenticated
     *
     * @group Profile
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "cta": {
     *       "show_profile_completion": true,
     *       "profile_completion_pct": 42,
     *       "show_photo_upload": true,
     *       "show_verify_email": false,
     *       "show_verify_phone": false,
     *       "show_upgrade": true
     *     },
     *     "stats": {
     *       "interests_received": 3,
     *       "interests_sent": 1,
     *       "profile_views_total": 57,
     *       "shortlisted_count": 12,
     *       "unread_notifications": 2
     *     },
     *     "recommended_matches": [],
     *     "mutual_matches": [],
     *     "recent_views": [],
     *     "newly_joined": [],
     *     "discover_teasers": [
     *       {"category": "nri-matrimony", "label": "NRI Matrimony", "count": null}
     *     ]
     *   }
     * }
     *
     * @response 401 scenario="unauthenticated" {
     *   "success": false,
     *   "error": {"code": "UNAUTHENTICATED", "message": "Unauthenticated."}
     * }
     *
     * @response 422 scenario="no-profile" {
     *   "success": false,
     *   "error": {"code": "PROFILE_REQUIRED", "message": "Complete registration before loading the dashboard."}
     * }
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        // Eager-load the handful of relations the dashboard touches, so the
        // service doesn't trigger lazy-load queries during payload build.
        $profile = $user->profile()->with(['profilePhotos', 'user.userMemberships'])->first();

        if (! $profile) {
            return ApiResponse::error(
                'PROFILE_REQUIRED',
                'Complete registration before loading the dashboard.',
                null,
                422,
            );
        }

        $payload = $this->dashboard->buildPayload($user, $profile);

        return ApiResponse::ok((new DashboardResource($payload))->resolve());
    }
}
