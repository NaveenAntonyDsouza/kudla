<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ProfileCardResource;
use App\Http\Responses\ApiResponse;
use App\Models\IgnoredProfile;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ignored-list endpoints — the soft cousin of block.
 *
 *   GET  /api/v1/ignored                            paginated ignored profiles
 *   POST /api/v1/profiles/{matriId}/ignore-toggle    toggle ignored state
 *
 * Ignore differs from block:
 *   - Block hides a profile bidirectionally + cancels pending interests.
 *   - Ignore hides a profile from search results / dashboards but does
 *     NOT cancel interests or notify either party. It's the "shhh, not
 *     interested but no big deal" version.
 *
 * No same-gender / self guards beyond the obvious — ignoring yourself
 * doesn't make sense (we still 422 it). Ignoring a same-gender profile
 * is harmless (they don't see you anyway) so we don't block it.
 *
 * Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-10-block-report-ignore.md
 */
class IgnoredProfileController extends BaseApiController
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 50;

    /* ==================================================================
     |  GET /ignored
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Ignored
     *
     * @queryParam page integer Optional. Default 1.
     * @queryParam per_page integer Optional. 1-50. Default 20.
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": [{"matri_id": "AM000201", ...}],
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
        $paginator = $this->paginateIgnored($viewer, $perPage);

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

    /* ==================================================================
     |  POST /profiles/{matriId}/ignore-toggle
     | ================================================================== */

    /**
     * Toggle ignore for a target profile. Each call flips the state;
     * response carries the authoritative new state.
     *
     * @authenticated
     *
     * @group Ignored
     *
     * @urlParam matriId string required Target's matri_id.
     *
     * @response 200 scenario="success" {"success": true, "data": {"is_ignored": true}}
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
                'You cannot ignore your own profile.',
                null,
                422,
            );
        }

        $existing = IgnoredProfile::where('profile_id', $viewer->id)
            ->where('ignored_profile_id', $target->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return ApiResponse::ok(['is_ignored' => false]);
        }

        IgnoredProfile::create([
            'profile_id' => $viewer->id,
            'ignored_profile_id' => $target->id,
        ]);

        return ApiResponse::ok(['is_ignored' => true]);
    }

    /* ==================================================================
     |  Test seams
     | ================================================================== */

    protected function paginateIgnored(Profile $viewer, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Profile::query()
            ->with(['religiousInfo', 'educationDetail', 'locationInfo', 'primaryPhoto', 'user'])
            ->join('ignored_profiles', 'ignored_profiles.ignored_profile_id', '=', 'profiles.id')
            ->where('ignored_profiles.profile_id', $viewer->id)
            ->orderByDesc('ignored_profiles.created_at')
            ->select('profiles.*')
            ->paginate($perPage);
    }

    protected function findTargetByMatriId(string $matriId): ?Profile
    {
        return Profile::where('matri_id', $matriId)->first();
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
            'Complete registration before using the ignored list.',
            null,
            422,
        );
    }
}
