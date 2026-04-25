<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ProfileCardResource;
use App\Http\Responses\ApiResponse;
use App\Models\Profile;
use App\Services\MatchingService;
use App\Services\ProfileAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Match-aware endpoints powered by MatchingService.
 *
 *   GET /api/v1/matches/my                 paginated matches for viewer
 *   GET /api/v1/matches/mutual             paginated bidirectional matches
 *   GET /api/v1/matches/score/{matriId}    on-demand score + breakdown
 *
 * `my` and `mutual` walk MatchingService::getMatches /
 * getMutualMatches and serialize each Profile via ProfileCardResource.
 * Match score + badge ride along on each card via dynamic in-memory
 * attributes (MatchingService attaches them; ProfileCardResource
 * resolves them). Score endpoint caches the {score, breakdown, badge}
 * triple for 1 day so a tap on "Why is this a match?" doesn't
 * re-compute the 13-criterion breakdown each time.
 *
 * MatchingService gracefully returns an empty paginator when the
 * viewer has no PartnerPreference — `my` and `mutual` therefore work
 * without explicit prefs (Flutter shows total=0 and prompts the user
 * to set preferences via the existing /profile/me/partner endpoint).
 *
 * The score endpoint can't compute against null prefs (calculateScore
 * type-hints PartnerPreference), so it returns 422 PREFERENCES_REQUIRED
 * with a clear code Flutter can branch on.
 *
 * Same-gender / blocked / hidden / suspended targets are routed through
 * ProfileAccessService::check() for the same anti-enumeration mapping
 * used by step-5 / step-13 (404 NOT_FOUND for blocked/hidden/suspended,
 * 403 GENDER_MISMATCH for same-gender, etc.).
 *
 * Design reference:
 *   docs/mobile-app/phase-2a-api/week-03-profiles-photos-search/step-15-match-endpoints.md
 */
class MatchController extends BaseApiController
{
    /** Per-page max for /matches/my — bigger than carousel, smaller than search. */
    public const MAX_PER_PAGE = 50;

    /** Default per-page when caller omits ?per_page. */
    public const DEFAULT_PER_PAGE = 20;

    /** TTL for cached match scores (1 day — scores rarely change). */
    public const SCORE_CACHE_TTL_SECONDS = 86_400;

    public function __construct(
        private MatchingService $matches,
        private ProfileAccessService $access,
    ) {}

    /* ==================================================================
     |  GET /matches/my
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Matches
     *
     * @queryParam page integer Page number (default 1).
     * @queryParam per_page integer Results per page (default 20, max 50).
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": [{"matri_id": "AM100042", "match_score": 87, "match_badge": "great"}],
     *   "meta": {"page": 1, "per_page": 20, "total": 47, "last_page": 3}
     * }
     *
     * @response 200 scenario="no-preferences" {
     *   "success": true,
     *   "data": [],
     *   "meta": {"page": 1, "per_page": 20, "total": 0, "last_page": 1}
     * }
     *
     * @response 401 scenario="unauthenticated" {"success": false, "error": {"code": "UNAUTHENTICATED", "message": "..."}}
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function my(Request $request): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return ApiResponse::error(
                'PROFILE_REQUIRED',
                'Complete registration before browsing matches.',
                null,
                422,
            );
        }

        $perPage = $this->resolvePerPage($request);

        // MatchingService gracefully returns an empty paginator when the
        // viewer has no PartnerPreference — no need to short-circuit here.
        $paginator = $this->matches->getMatches($viewer, $perPage);

        return ApiResponse::paginated($paginator, ProfileCardResource::class);
    }

    /* ==================================================================
     |  GET /matches/mutual
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Matches
     *
     * @queryParam page integer Page number (default 1).
     * @queryParam per_page integer Results per page (default 20, max 50).
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": [{"matri_id": "AM100042", "match_score": 91, "match_badge": "great"}],
     *   "meta": {"page": 1, "per_page": 20, "total": 12, "last_page": 1}
     * }
     *
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function mutual(Request $request): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return ApiResponse::error(
                'PROFILE_REQUIRED',
                'Complete registration before browsing mutual matches.',
                null,
                422,
            );
        }

        $perPage = $this->resolvePerPage($request);

        $paginator = $this->matches->getMutualMatches($viewer, $perPage);

        return ApiResponse::paginated($paginator, ProfileCardResource::class);
    }

    /* ==================================================================
     |  GET /matches/score/{matriId}
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Matches
     *
     * @urlParam matriId string required The target's matri_id.
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "score": 87,
     *     "breakdown": [{"criterion": "religion", "label": "Religion", "weight": 15, "matched": true}],
     *     "badge": "great",
     *     "cached": false
     *   }
     * }
     *
     * @response 403 scenario="same-gender" {"success": false, "error": {"code": "GENDER_MISMATCH", "message": "..."}}
     * @response 404 scenario="not-found-or-restricted" {"success": false, "error": {"code": "NOT_FOUND", "message": "Profile not available."}}
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     * @response 422 scenario="no-preferences" {"success": false, "error": {"code": "PREFERENCES_REQUIRED", "message": "Set partner preferences before scoring matches."}}
     * @response 429 scenario="throttled" {"success": false, "error": {"code": "THROTTLED", "message": "..."}}
     */
    public function score(Request $request, string $matriId): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return ApiResponse::error(
                'PROFILE_REQUIRED',
                'Complete registration before scoring matches.',
                null,
                422,
            );
        }

        $target = $this->findTargetByMatriId(strtoupper($matriId));
        if (! $target) {
            return $this->notFound();
        }

        // Apply the full 7-gate matrix for consistent error mapping with
        // step-5 (view-other) and step-13 (matri_id lookup).
        $reason = $this->access->check($viewer, $target);
        if ($reason !== ProfileAccessService::REASON_OK
            && $reason !== ProfileAccessService::REASON_SELF) {
            return $this->mapGateReason($reason);
        }

        // calculateScore type-hints PartnerPreference (non-nullable), so
        // we must reject explicitly when the viewer hasn't set prefs.
        $prefs = $viewer->partnerPreference;
        if (! $prefs) {
            return ApiResponse::error(
                'PREFERENCES_REQUIRED',
                'Set partner preferences before scoring matches.',
                null,
                422,
            );
        }

        $cacheKey = "match_score:{$viewer->id}:{$target->id}";
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return ApiResponse::ok(array_merge($cached, ['cached' => true]));
        }

        $score = $this->matches->calculateScore($target, $prefs);
        Cache::put($cacheKey, $score, self::SCORE_CACHE_TTL_SECONDS);

        return ApiResponse::ok(array_merge($score, ['cached' => false]));
    }

    /* ==================================================================
     |  Helpers
     | ================================================================== */

    /** Clamp per_page to [1, MAX_PER_PAGE]. Default DEFAULT_PER_PAGE when omitted. */
    private function resolvePerPage(Request $request): int
    {
        return min(
            max((int) $request->query('per_page', self::DEFAULT_PER_PAGE), 1),
            self::MAX_PER_PAGE,
        );
    }

    /**
     * Find a profile by matri_id — protected seam so tests can stub
     * the lookup with a pre-built in-memory Profile.
     */
    protected function findTargetByMatriId(string $matriId): ?Profile
    {
        try {
            return Profile::where('matri_id', $matriId)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Single NOT_FOUND envelope used for truly-missing + anti-enumeration paths. */
    private function notFound(): JsonResponse
    {
        return ApiResponse::error(
            'NOT_FOUND',
            'Profile not available.',
            null,
            404,
        );
    }

    /**
     * Map a ProfileAccessService reason to an envelope error. Same
     * mapping ProfileController::accessError uses — consolidate when a
     * third place needs it.
     */
    private function mapGateReason(string $reason): JsonResponse
    {
        return match ($reason) {
            ProfileAccessService::REASON_SAME_GENDER => ApiResponse::error(
                'GENDER_MISMATCH',
                'Cannot score a same-gender profile.',
                null,
                403,
            ),
            ProfileAccessService::REASON_BLOCKED,
            ProfileAccessService::REASON_HIDDEN,
            ProfileAccessService::REASON_SUSPENDED => $this->notFound(),
            ProfileAccessService::REASON_VISIBILITY_PREMIUM => ApiResponse::error(
                'PREMIUM_REQUIRED',
                'This profile is visible to premium members only.',
                null,
                403,
            ),
            ProfileAccessService::REASON_VISIBILITY_MATCHES => ApiResponse::error(
                'LOW_MATCH_SCORE',
                'This profile is visible to high-match members only.',
                null,
                403,
            ),
            default => $this->notFound(),
        };
    }
}
