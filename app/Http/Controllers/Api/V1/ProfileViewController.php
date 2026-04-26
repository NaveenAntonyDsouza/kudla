<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ProfileCardResource;
use App\Http\Responses\ApiResponse;
use App\Models\Profile;
use App\Models\ProfileView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Profile-view list endpoints.
 *
 *   GET /api/v1/views?tab=viewed_by   profiles that viewed me (premium-gated)
 *   GET /api/v1/views?tab=i_viewed    profiles I viewed (always available)
 *
 * Premium gating model:
 *   - tab=viewed_by:
 *       Free user → returns total_count + empty viewers array. Flutter
 *       shows "Upgrade to see who viewed you" CTA.
 *       Premium user → paginated viewer profile cards, latest-first.
 *   - tab=i_viewed:
 *       Always available — privacy-symmetric (you see your own activity).
 *
 * Aggregation per viewer (e.g. "Anita viewed you 5 times") is NOT done
 * for V1; one row per ProfileView. The 24h dedupe window in
 * ProfileViewService::track keeps repeats limited.
 *
 * Reference: docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-09-shortlist-views.md
 */
class ProfileViewController extends BaseApiController
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 50;

    /**
     * Profile-views feed — "viewed_by" (who viewed me) or "i_viewed" (who I viewed). Premium-gated for full list.
     *
     * @authenticated
     *
     * @group Views
     *
     * @queryParam tab string Optional. "viewed_by" (default) or "i_viewed".
     * @queryParam page integer Optional. Default 1.
     * @queryParam per_page integer Optional. 1-50. Default 20.
     *
     * @response 200 scenario="viewed_by-free" {
     *   "success": true,
     *   "data": {"tab": "viewed_by", "is_premium": false, "total_count": 7, "viewers": []},
     *   "meta": {"page": 1, "per_page": 20, "total": 0, "last_page": 1}
     * }
     * @response 200 scenario="viewed_by-premium" {
     *   "success": true,
     *   "data": {"tab": "viewed_by", "is_premium": true, "total_count": 7, "viewers": [{"matri_id": "AM000123", ...}]},
     *   "meta": {"page": 1, "per_page": 20, "total": 7, "last_page": 1}
     * }
     * @response 200 scenario="i_viewed" {
     *   "success": true,
     *   "data": {"tab": "i_viewed", "is_premium": false, "viewed_profiles": [...]},
     *   "meta": {"page": 1, "per_page": 20, "total": 3, "last_page": 1}
     * }
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function index(Request $request): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return $this->profileRequired();
        }

        $tab = $request->query('tab') === 'i_viewed' ? 'i_viewed' : 'viewed_by';
        $perPage = $this->resolvePerPage($request);
        $isPremium = $this->resolvePremium($request->user());

        return $tab === 'viewed_by'
            ? $this->viewedBy($viewer, $perPage, $isPremium)
            : $this->iViewed($viewer, $perPage, $isPremium);
    }

    /**
     * Profiles that viewed the viewer. Premium-gated for the list itself;
     * the count is always exposed so free users see "12 people viewed
     * your profile".
     */
    private function viewedBy(Profile $viewer, int $perPage, bool $isPremium): JsonResponse
    {
        $totalCount = $this->countViewedBy($viewer);

        if (! $isPremium) {
            return ApiResponse::ok([
                'tab' => 'viewed_by',
                'is_premium' => false,
                'total_count' => $totalCount,
                'viewers' => [],
            ], [
                'page' => 1,
                'per_page' => $perPage,
                'total' => 0,
                'last_page' => 1,
            ]);
        }

        // Premium: paginated viewer profile cards, latest-first.
        $paginator = $this->paginateViewedBy($viewer, $perPage);

        $viewers = collect($paginator->items())
            ->map(fn (ProfileView $pv) => $pv->viewerProfile)
            ->filter()
            // Defensive against ProfileView rows whose viewer was deleted.
            ->map(fn (Profile $p) => (new ProfileCardResource($p, viewer: $viewer))->resolve())
            ->values()
            ->all();

        return ApiResponse::ok([
            'tab' => 'viewed_by',
            'is_premium' => true,
            'total_count' => $totalCount,
            'viewers' => $viewers,
        ], [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    /**
     * Profiles the viewer has viewed. Always available — your own
     * browsing history isn't behind a paywall.
     */
    private function iViewed(Profile $viewer, int $perPage, bool $isPremium): JsonResponse
    {
        $paginator = $this->paginateIViewed($viewer, $perPage);

        $viewedProfiles = collect($paginator->items())
            ->map(fn (ProfileView $pv) => $pv->viewedProfile)
            ->filter()
            ->map(fn (Profile $p) => (new ProfileCardResource($p, viewer: $viewer))->resolve())
            ->values()
            ->all();

        return ApiResponse::ok([
            'tab' => 'i_viewed',
            'is_premium' => $isPremium,
            'viewed_profiles' => $viewedProfiles,
        ], [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    /* ==================================================================
     |  Test seams — overridden in tests to skip eager-load tables.
     | ================================================================== */

    protected function countViewedBy(Profile $viewer): int
    {
        return ProfileView::where('viewed_profile_id', $viewer->id)->count();
    }

    protected function paginateViewedBy(Profile $viewer, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return ProfileView::query()
            ->where('viewed_profile_id', $viewer->id)
            ->with(['viewerProfile.religiousInfo', 'viewerProfile.educationDetail', 'viewerProfile.locationInfo', 'viewerProfile.primaryPhoto', 'viewerProfile.user'])
            ->orderByDesc('viewed_at')
            ->paginate($perPage);
    }

    protected function paginateIViewed(Profile $viewer, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return ProfileView::query()
            ->where('viewer_profile_id', $viewer->id)
            ->with(['viewedProfile.religiousInfo', 'viewedProfile.educationDetail', 'viewedProfile.locationInfo', 'viewedProfile.primaryPhoto', 'viewedProfile.user'])
            ->orderByDesc('viewed_at')
            ->paginate($perPage);
    }

    /* ==================================================================
     |  Helpers
     | ================================================================== */

    /**
     * isPremium check. User::isPremium() can throw in test environments
     * with missing user_memberships table — same defensive pattern as
     * ProfileCardResource. Treat any failure as "not premium".
     *
     * Protected (not private) so tests can override via subclass to
     * skip the user_memberships query without touching DB.
     */
    protected function resolvePremium($user): bool
    {
        try {
            return (bool) $user?->isPremium();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function resolvePerPage(Request $request): int
    {
        return max(1, min(self::MAX_PER_PAGE, (int) $request->query('per_page', self::DEFAULT_PER_PAGE)));
    }

    private function profileRequired(): JsonResponse
    {
        return ApiResponse::error(
            'PROFILE_REQUIRED',
            'Complete registration before viewing profile activity.',
            null,
            422,
        );
    }
}
