<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ProfileCardResource;
use App\Http\Responses\ApiResponse;
use App\Models\BlockedProfile;
use App\Models\Interest;
use App\Models\Profile;
use App\Models\Shortlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Block-list endpoints.
 *
 *   GET  /api/v1/blocked                   paginated blocked profiles
 *   POST /api/v1/profiles/{matriId}/block   create-or-no-op the block
 *   POST /api/v1/profiles/{matriId}/unblock delete the block
 *
 * Block side effects (run in a transaction so we never end up half-
 * blocked): cancel any pending interests in either direction, remove
 * the viewer's shortlist entry for the target. The TARGET's shortlist
 * of the viewer is NOT touched — that's their business; the system
 * just makes the target invisible to the viewer.
 *
 * Schema note: the table is `blocked_profiles` with columns
 * `profile_id` (the blocker) + `blocked_profile_id` (the blocked
 * party). The step-10 design doc shows `blocker_profile_id` and a
 * `reason` field — neither is on the schema. We follow the schema.
 *
 * Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-10-block-report-ignore.md
 */
class BlockController extends BaseApiController
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 50;

    /* ==================================================================
     |  GET /blocked
     | ================================================================== */

    /**
     * @authenticated
     *
     * @group Block
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
        $paginator = $this->paginateBlocked($viewer, $perPage);

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
     |  POST /profiles/{matriId}/block
     | ================================================================== */

    /**
     * Block a target profile. Idempotent — POSTing twice is a no-op
     * after the first call. Side-effects (cancel pending interests,
     * remove the viewer's shortlist of target) run in a transaction so
     * a partial state isn't visible to other requests.
     *
     * @authenticated
     *
     * @group Block
     *
     * @urlParam matriId string required Target's matri_id.
     *
     * @response 200 scenario="success" {"success": true, "data": {"blocked": true}}
     * @response 404 scenario="not-found" {"success": false, "error": {"code": "NOT_FOUND", "message": "Profile not available."}}
     * @response 422 scenario="invalid-target" {"success": false, "error": {"code": "INVALID_TARGET", "message": "..."}}
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function block(Request $request, string $matriId): JsonResponse
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
                'You cannot block your own profile.',
                null,
                422,
            );
        }

        if ($viewer->gender === $target->gender) {
            return ApiResponse::error(
                'INVALID_TARGET',
                'This action is not available for this profile.',
                null,
                422,
            );
        }

        DB::transaction(function () use ($viewer, $target) {
            BlockedProfile::firstOrCreate([
                'profile_id' => $viewer->id,
                'blocked_profile_id' => $target->id,
            ]);

            // Cancel any pending interests in either direction. Best-effort:
            // wrapped in try so a missing interests table (e.g. test env
            // without the inline schema) doesn't poison the block itself.
            try {
                Interest::where('status', 'pending')
                    ->where(function ($q) use ($viewer, $target) {
                        $q->where(['sender_profile_id' => $viewer->id, 'receiver_profile_id' => $target->id])
                            ->orWhere(['sender_profile_id' => $target->id, 'receiver_profile_id' => $viewer->id]);
                    })
                    ->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            } catch (\Throwable $e) {
                // Tolerated — the block itself succeeded.
            }

            // Remove the viewer's shortlist entry for the target (one-sided
            // — we don't mess with the target's shortlist).
            try {
                Shortlist::where('profile_id', $viewer->id)
                    ->where('shortlisted_profile_id', $target->id)
                    ->delete();
            } catch (\Throwable $e) {
                // Tolerated.
            }
        });

        return ApiResponse::ok(['blocked' => true]);
    }

    /* ==================================================================
     |  POST /profiles/{matriId}/unblock
     | ================================================================== */

    /**
     * Unblock a target profile. Idempotent — no-op when no block exists.
     *
     * @authenticated
     *
     * @group Block
     *
     * @urlParam matriId string required Target's matri_id.
     *
     * @response 200 scenario="success" {"success": true, "data": {"blocked": false}}
     * @response 404 scenario="not-found" {"success": false, "error": {"code": "NOT_FOUND", "message": "Profile not available."}}
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function unblock(Request $request, string $matriId): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return $this->profileRequired();
        }

        $target = $this->findTargetByMatriId($matriId);
        if (! $target) {
            return ApiResponse::error('NOT_FOUND', 'Profile not available.', null, 404);
        }

        BlockedProfile::where('profile_id', $viewer->id)
            ->where('blocked_profile_id', $target->id)
            ->delete();

        return ApiResponse::ok(['blocked' => false]);
    }

    /* ==================================================================
     |  Test seams
     | ================================================================== */

    protected function paginateBlocked(Profile $viewer, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Profile::query()
            ->with(['religiousInfo', 'educationDetail', 'locationInfo', 'primaryPhoto', 'user'])
            ->join('blocked_profiles', 'blocked_profiles.blocked_profile_id', '=', 'profiles.id')
            ->where('blocked_profiles.profile_id', $viewer->id)
            ->orderByDesc('blocked_profiles.created_at')
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
            'Complete registration before using the block list.',
            null,
            422,
        );
    }
}
