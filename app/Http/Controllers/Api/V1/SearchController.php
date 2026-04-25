<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ProfileCardResource;
use App\Http\Responses\ApiResponse;
use App\Models\Profile;
use App\Traits\ProfileQueryFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Partner-preference search — the main discovery surface.
 *
 *   GET /api/v1/search/partner
 *
 * Accepts 17+ query params (age_from, height_from, religion, caste,
 * education, diet, etc.), returns a paginated list of ProfileCardResource
 * matches. Filter logic + sort logic mirror web's SearchController to keep
 * behaviour identical between browser and Flutter.
 *
 * Auth: Sanctum. Viewer's own profile is needed because the base query
 * excludes self + opposite-gender / blocked / hidden / suspended via
 * ProfileQueryFilters::baseQuery().
 *
 * NOTE: The buildSearchQuery + applySortOrder logic below is copied
 * from App\Http\Controllers\SearchController (web). They use MySQL-
 * specific SQL (TIMESTAMPDIFF, CAST AS UNSIGNED, NOW() subqueries) so
 * they can't execute against the SQLite :memory: test DB. Tests cover
 * the controller wiring; real-query behaviour is verified by Bruno
 * smoke in step-16 against a migrated MySQL instance.
 *
 * TODO: extract buildSearchQuery + applySortOrder into a shared trait
 * (e.g. App\Traits\PerformsPartnerSearch) so web and API stop
 * duplicating ~125 lines. Scheduled for the Week 3 buffer / retrofit
 * pass, not this step.
 *
 * Design reference:
 *   docs/mobile-app/phase-2a-api/week-03-profiles-photos-search/step-12-search-partner-endpoint.md
 */
class SearchController extends BaseApiController
{
    use ProfileQueryFilters;

    /** Hard cap on page size to prevent abusive 1000-row dumps. */
    public const MAX_PER_PAGE = 50;

    /** Default page size when caller omits per_page. */
    public const DEFAULT_PER_PAGE = 20;

    /**
     * Partner search.
     *
     * @authenticated
     *
     * @group Search
     *
     * @queryParam page integer Page number (default 1).
     * @queryParam per_page integer Results per page (default 20, max 50).
     * @queryParam sort string One of: relevance (default), newest, recently_active, age_low, age_high.
     * @queryParam age_from integer Minimum age.
     * @queryParam age_to integer Maximum age.
     * @queryParam height_from integer Minimum height in cm.
     * @queryParam height_to integer Maximum height in cm.
     * @queryParam religion string[] Religion filter (multi-select, comma-separated).
     * @queryParam caste string[] Caste filter.
     * @queryParam denomination string[] Denomination filter (usually chained with religion).
     * @queryParam mother_tongue string[] Mother tongue filter.
     * @queryParam marital_status string[] Marital status filter.
     * @queryParam body_type string[] Body type filter.
     * @queryParam physical_status string[] Physical status filter.
     * @queryParam education string[] Education level filter.
     * @queryParam occupation string[] Occupation filter.
     * @queryParam annual_income string[] Income bracket filter.
     * @queryParam working_country string Single country filter (not array).
     * @queryParam native_country string Single country filter (not array).
     * @queryParam family_status string[] Family status filter.
     * @queryParam diet string[] Diet filter.
     * @queryParam smoking string[] Smoking filter.
     * @queryParam drinking string[] Drinking filter.
     * @queryParam with_photo boolean If true, only return profiles with a primary photo.
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": [{"matri_id": "AM100042", "full_name": "...", "age": 28}],
     *   "meta": {
     *     "page": 1,
     *     "per_page": 20,
     *     "total": 137,
     *     "last_page": 7,
     *     "applied_filters": {"religion": ["Hindu"], "age_from": 25}
     *   }
     * }
     *
     * @response 401 scenario="unauthenticated" {"success": false, "error": {"code": "UNAUTHENTICATED", "message": "..."}}
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     * @response 429 scenario="throttled" {"success": false, "error": {"code": "THROTTLED", "message": "..."}}
     */
    public function partner(Request $request): JsonResponse
    {
        $viewer = $request->user()->profile;
        if (! $viewer) {
            return ApiResponse::error(
                'PROFILE_REQUIRED',
                'Complete registration before searching for partners.',
                null,
                422,
            );
        }

        // per_page: explicit cap so a single request can't return 1000 rows.
        $perPage = min(
            max((int) $request->query('per_page', self::DEFAULT_PER_PAGE), 1),
            self::MAX_PER_PAGE,
        );

        $paginator = $this->executeQuery($request, $viewer, $perPage);

        // Echo the applied filters back so Flutter's "active filters" chip row
        // has a stable contract without re-parsing its own query string.
        $appliedFilters = array_filter($request->only([
            'age_from', 'age_to', 'height_from', 'height_to',
            'religion', 'caste', 'denomination', 'mother_tongue',
            'marital_status', 'body_type', 'physical_status',
            'education', 'occupation', 'annual_income',
            'working_country', 'native_country',
            'family_status', 'diet', 'smoking', 'drinking',
            'with_photo', 'sort',
        ]), fn ($v) => $v !== null && $v !== '' && $v !== []);

        return ApiResponse::paginated($paginator, ProfileCardResource::class, [
            'applied_filters' => $appliedFilters,
        ]);
    }

    /**
     * Build + run the paginated search query. Extracted as a `protected`
     * seam so tests can return a pre-built paginator without executing
     * the MySQL-only SQL against a SQLite :memory: test DB.
     */
    protected function executeQuery(
        Request $request,
        Profile $viewer,
        int $perPage,
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
        $query = $this->buildSearchQuery($request, $viewer);
        $this->applySortOrder($query, (string) $request->query('sort', 'relevance'));

        return $query->paginate($perPage);
    }

    /* ==================================================================
     |  Query builder — mirrored from web SearchController
     | ================================================================== */

    /**
     * Start from the 7-gate base query (self/gender/blocked/hidden/
     * suspended/visibility pre-filters all applied), then layer on the
     * request's filter params.
     *
     * Copied verbatim from App\Http\Controllers\SearchController::
     * buildSearchQuery for MVP. See class docblock TODO about
     * consolidating into a shared trait.
     */
    protected function buildSearchQuery(Request $request, Profile $profile): Builder
    {
        $query = $this->baseQuery($profile);

        // Age filter — MySQL-only SQL.
        $query->when($request->age_from, fn ($q, $v) => $q->whereRaw(
            'TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= ?',
            [(int) $v],
        ));
        $query->when($request->age_to, fn ($q, $v) => $q->whereRaw(
            'TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?',
            [(int) $v],
        ));

        // Height: compare by the cm prefix number of the stored string.
        $query->when($request->height_from, function ($q, $v) {
            $cm = (int) $v;
            if ($cm > 0) {
                $q->whereRaw('CAST(height AS UNSIGNED) >= ?', [$cm]);
            }
        });
        $query->when($request->height_to, function ($q, $v) {
            $cm = (int) $v;
            if ($cm > 0) {
                $q->whereRaw('CAST(height AS UNSIGNED) <= ?', [$cm]);
            }
        });

        // Direct profile field filters (multi-select — 'Any' is the
        // "no preference" sentinel that must be stripped).
        $query->when($request->marital_status, fn ($q, $v) => $q->whereIn(
            'marital_status',
            array_filter((array) $v, fn ($i) => $i !== 'Any'),
        ));
        $query->when($request->mother_tongue, fn ($q, $v) => $q->whereIn(
            'mother_tongue',
            array_filter((array) $v, fn ($i) => $i !== 'Any'),
        ));
        $query->when($request->body_type, fn ($q, $v) => $q->whereIn(
            'body_type',
            array_filter((array) $v, fn ($i) => $i !== 'Any'),
        ));
        $query->when($request->physical_status, fn ($q, $v) => $q->whereIn(
            'physical_status',
            array_filter((array) $v, fn ($i) => $i !== 'Any'),
        ));

        // Religion + cascading denomination / caste.
        $religions = array_filter((array) ($request->religion ?? []), fn ($i) => $i !== 'Any');
        if (! empty($religions)) {
            $query->whereHas('religiousInfo', function ($q) use ($request, $religions) {
                $q->whereIn('religion', $religions);

                $denominations = array_filter((array) ($request->denomination ?? []), fn ($i) => $i !== 'Any');
                if (! empty($denominations)) {
                    $q->whereIn('denomination', $denominations);
                }

                $castes = array_filter((array) ($request->caste ?? []), fn ($i) => $i !== 'Any');
                if (! empty($castes)) {
                    $q->whereIn('caste', $castes);
                }
            });
        }

        // Education detail joins.
        $education = array_filter((array) ($request->education ?? []), fn ($i) => $i !== 'Any');
        if (! empty($education)) {
            $query->whereHas('educationDetail', fn ($q) => $q->whereIn('highest_education', $education));
        }
        $occupation = array_filter((array) ($request->occupation ?? []), fn ($i) => $i !== 'Any');
        if (! empty($occupation)) {
            $query->whereHas('educationDetail', fn ($q) => $q->whereIn('occupation', $occupation));
        }
        $income = array_filter((array) ($request->annual_income ?? []), fn ($i) => $i !== 'Any');
        if (! empty($income)) {
            $query->whereHas('educationDetail', fn ($q) => $q->whereIn('annual_income', $income));
        }

        // Location filters.
        $query->when($request->working_country, fn ($q, $v) => $q->whereHas(
            'educationDetail',
            fn ($q2) => $q2->where('working_country', $v),
        ));
        $query->when($request->native_country, fn ($q, $v) => $q->whereHas(
            'locationInfo',
            fn ($q2) => $q2->where('native_country', $v),
        ));

        // Family status.
        $familyStatus = array_filter((array) ($request->family_status ?? []), fn ($i) => $i !== 'Any');
        if (! empty($familyStatus)) {
            $query->whereHas('familyDetail', fn ($q) => $q->whereIn('family_status', $familyStatus));
        }

        // Lifestyle filters.
        $query->when($request->diet, function ($q, $v) {
            $filtered = array_filter((array) $v, fn ($i) => $i !== 'Any');
            if (! empty($filtered)) {
                $q->whereHas('lifestyleInfo', fn ($q2) => $q2->whereIn('diet', $filtered));
            }
        });
        $query->when($request->smoking, function ($q, $v) {
            $filtered = array_filter((array) $v, fn ($i) => $i !== 'Any');
            if (! empty($filtered)) {
                $q->whereHas('lifestyleInfo', fn ($q2) => $q2->whereIn('smoking', $filtered));
            }
        });
        $query->when($request->drinking, function ($q, $v) {
            $filtered = array_filter((array) $v, fn ($i) => $i !== 'Any');
            if (! empty($filtered)) {
                $q->whereHas('lifestyleInfo', fn ($q2) => $q2->whereIn('drinking', $filtered));
            }
        });

        // Photo-required toggle.
        if ($request->boolean('with_photo')) {
            $query->whereHas('primaryPhoto');
        }

        return $query;
    }

    /**
     * Sort variants — also MySQL-specific. Copied verbatim from web
     * SearchController. Unknown/missing sort key falls through to the
     * "relevance" cascade (VIP → Featured → Premium → Recently Active →
     * Newest), so invalid input from Flutter never breaks the query.
     */
    protected function applySortOrder(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'newest' => $query->orderBy('profiles.created_at', 'desc'),

            'recently_active' => $query
                ->orderByRaw('(SELECT last_login_at FROM users WHERE users.id = profiles.user_id) IS NULL ASC')
                ->orderByRaw('(SELECT last_login_at FROM users WHERE users.id = profiles.user_id) DESC'),

            'age_low' => $query->orderByRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) ASC'),
            'age_high' => $query->orderByRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) DESC'),

            default => $query
                ->orderBy('profiles.is_vip', 'desc')
                ->orderBy('profiles.is_featured', 'desc')
                ->orderByRaw('EXISTS(SELECT 1 FROM user_memberships um JOIN membership_plans mp ON mp.id = um.plan_id WHERE um.user_id = profiles.user_id AND um.is_active = 1 AND (um.ends_at IS NULL OR um.ends_at > NOW()) AND mp.is_highlighted = 1) DESC')
                ->orderByRaw('EXISTS(SELECT 1 FROM user_memberships WHERE user_memberships.user_id = profiles.user_id AND user_memberships.is_active = 1 AND (user_memberships.ends_at IS NULL OR user_memberships.ends_at > NOW())) DESC')
                ->orderByRaw('(SELECT last_login_at FROM users WHERE users.id = profiles.user_id) IS NULL ASC')
                ->orderByRaw('(SELECT last_login_at FROM users WHERE users.id = profiles.user_id) DESC')
                ->orderBy('profiles.created_at', 'desc'),
        };
    }
}
