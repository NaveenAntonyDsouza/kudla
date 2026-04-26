<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ProfileCardResource;
use App\Http\Responses\ApiResponse;
use App\Models\Profile;
use App\Models\Shortlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Shortlist (a.k.a. "saved profiles" / "favorites") endpoints.
 *
 *   GET  /api/v1/shortlist                       paginated list of saved profiles
 *   POST /api/v1/profiles/{matriId}/shortlist    toggle (create/delete)
 *
 * The toggle endpoint is idempotent in semantic terms — POSTing twice
 * lands the profile in the opposite state each time, with the
 * authoritative new state in the response. Flutter renders the heart
 * icon directly from `is_shortlisted` rather than tracking it locally.
 *
 * Same-gender + self-shortlist are blocked with 422 for parity with
 * the existing web flow + InterestController gates.
 *
 * Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-09-shortlist-views.md
 */
class ShortlistController extends BaseApiController
{
    /** Default per-page for the list endpoint. Cap matches the rest of v1. */
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 50;

    /* ==================================================================
     |  GET /shortlist
     | ================================================================== */

    /**
     * Paginated list of profiles the viewer has shortlisted, latest first.
     *
     * @authenticated
     *
     * @group Shortlist
     *
     * @queryParam page integer Optional. Page number. Default 1.
     * @queryParam per_page integer Optional. 1-50. Default 20.
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": [{"matri_id": "AM000123", "full_name": "...", "is_shortlisted": true, ...}],
     *   "meta": {"page": 1, "per_page": 20, "total": 1, "last_page": 1}
     * }
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function index(Request $request): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return $this->profileRequired();
        }

        $perPage = $this->resolvePerPage($request);

        $paginator = $this->paginateShortlistedProfiles($viewer, $perPage);

        // Map manually so we can inject the viewer (ProfileCardResource needs
        // it to resolve is_shortlisted=true / interest_status / blocked).
        $items = collect($paginator->items())
            ->map(fn (Profile $p) => (new ProfileCardResource($p, viewer: $viewer))->resolve())
            ->all();

        return ApiResponse::ok($items, [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    /**
     * Build the paginated query of profiles the viewer has shortlisted,
     * latest first. Protected seam — tests override to return a
     * pre-built paginator with in-memory profiles, avoiding the need
     * for the full eager-load table ecosystem.
     */
    protected function paginateShortlistedProfiles(Profile $viewer, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Profile::query()
            ->with(['religiousInfo', 'educationDetail', 'locationInfo', 'primaryPhoto', 'user'])
            ->join('shortlists', 'shortlists.shortlisted_profile_id', '=', 'profiles.id')
            ->where('shortlists.profile_id', $viewer->id)
            ->orderByDesc('shortlists.created_at')
            ->select('profiles.*')
            ->paginate($perPage);
    }

    /**
     * Resolve the target profile for the toggle endpoint. Protected
     * seam — tests override to return an in-memory Profile and skip the
     * profiles table.
     */
    protected function findTargetByMatriId(string $matriId): ?Profile
    {
        return Profile::where('matri_id', $matriId)->first();
    }

    /* ==================================================================
     |  POST /profiles/{matriId}/shortlist
     | ================================================================== */

    /**
     * Toggle shortlist for a target profile. Idempotent against state —
     * each call flips, response carries the authoritative new state.
     *
     * @authenticated
     *
     * @group Shortlist
     *
     * @urlParam matriId string required Target's matri_id (uppercase alphanumeric).
     *
     * @response 200 scenario="success" {"success": true, "data": {"is_shortlisted": true, "shortlist_count": 5}}
     * @response 404 scenario="not-found" {"success": false, "error": {"code": "NOT_FOUND", "message": "Profile not available."}}
     * @response 422 scenario="invalid-target" {"success": false, "error": {"code": "INVALID_TARGET", "message": "..."}}
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function toggle(Request $request, string $matriId): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return $this->profileRequired();
        }

        $target = $this->findTargetByMatriId($matriId);
        if (! $target) {
            return ApiResponse::error('NOT_FOUND', 'Profile not available.', null, 404);
        }

        if ($viewer->id === $target->id) {
            return ApiResponse::error(
                'INVALID_TARGET',
                'You cannot shortlist your own profile.',
                null,
                422,
            );
        }

        if ($viewer->gender === $target->gender) {
            return ApiResponse::error(
                'INVALID_TARGET',
                'You can only shortlist profiles of the opposite gender.',
                null,
                422,
            );
        }

        $existing = Shortlist::where('profile_id', $viewer->id)
            ->where('shortlisted_profile_id', $target->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $isShortlisted = false;
        } else {
            Shortlist::create([
                'profile_id' => $viewer->id,
                'shortlisted_profile_id' => $target->id,
            ]);
            $isShortlisted = true;
        }

        return ApiResponse::ok([
            'is_shortlisted' => $isShortlisted,
            'shortlist_count' => Shortlist::where('profile_id', $viewer->id)->count(),
        ]);
    }

    /* ==================================================================
     |  Helpers
     | ================================================================== */

    private function resolvePerPage(Request $request): int
    {
        return max(1, min(self::MAX_PER_PAGE, (int) $request->query('per_page', self::DEFAULT_PER_PAGE)));
    }

    private function profileRequired(): JsonResponse
    {
        return ApiResponse::error(
            'PROFILE_REQUIRED',
            'Complete registration before using shortlist.',
            null,
            422,
        );
    }
}
