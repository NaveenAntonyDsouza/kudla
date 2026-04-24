<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ProfileResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Single-profile endpoints (own + other).
 *
 *   me()            — GET /api/v1/profile/me              (step 4)
 *   show()          — GET /api/v1/profiles/{matriId}      (step 5)
 *   updateSection() — PUT /api/v1/profile/me/sections/... (step 6)
 *
 * The me() view is the simplest — no privacy gates, all sections
 * populated, contact always included. ProfileResource's shape contract
 * is locked by the 146-assertion ProfileResourceTest; this controller
 * is the authentication + eager-load wrapper around it.
 *
 * Design reference:
 *   - docs/mobile-app/phase-2a-api/week-03-profiles-photos-search/step-04-profile-me-endpoint.md
 *   - docs/mobile-app/design/04-profile-api.md §4.3
 */
class ProfileController extends BaseApiController
{
    /**
     * The 11 relations the full ProfileResource touches. Kept as a
     * constant so future endpoints (step 5) can reuse the same list
     * and stay in sync.
     *
     * `user.userMemberships` is nested — it lets is_premium resolve to
     * an accurate boolean (ProfileResource::isPremiumSafely returns
     * false when the relation isn't loaded).
     */
    public const PROFILE_EAGER_LOADS = [
        'user.userMemberships',
        'religiousInfo',
        'educationDetail',
        'familyDetail',
        'locationInfo',
        'contactInfo',
        'lifestyleInfo',
        'partnerPreference',
        'socialMediaLink',
        'photoPrivacySetting',
        'profilePhotos',
    ];

    /**
     * Return the authenticated user's own profile with all 9 sections,
     * contact populated, photos grouped by type.
     *
     * @authenticated
     *
     * @group Profile
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "profile": {
     *       "matri_id": "AM100042",
     *       "full_name": "Priya Rani",
     *       "gender": "female",
     *       "age": 29,
     *       "is_premium": true,
     *       "sections": {
     *         "primary": {}, "religious": {}, "education": {}, "family": {},
     *         "location": {}, "contact": {}, "hobbies": {}, "social": {}, "partner": {}
     *       },
     *       "photos": {"profile": [], "album": [], "family": [], "photo_privacy": null}
     *     }
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
     *   "error": {"code": "PROFILE_REQUIRED", "message": "Complete registration before viewing your profile."}
     * }
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile;  // cached relation access — test-friendly

        if (! $profile) {
            return ApiResponse::error(
                'PROFILE_REQUIRED',
                'Complete registration before viewing your profile.',
                null,
                422,
            );
        }

        // loadMissing skips relations that are already loaded — no-op in
        // tests (where we pre-set via setRelation) and a single batch of
        // eager-loads in production.
        $profile->loadMissing(self::PROFILE_EAGER_LOADS);

        return ApiResponse::ok([
            'profile' => (new ProfileResource(
                $profile,
                includeContact: true,   // own profile → contact always populated
                viewer: $profile,       // self-view → photo blur / viewer context
            ))->resolve(),
        ]);
    }
}
