<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ProfileCardResource;
use App\Http\Responses\ApiResponse;
use App\Models\Profile;
use App\Services\DiscoverConfigService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Discover — category-based browsing surface.
 *
 *   GET /api/v1/discover                         hub (lists all categories)
 *   GET /api/v1/discover/{category}              subcategory list OR direct results
 *   GET /api/v1/discover/{category}/{slug}       paginated results for a subcategory
 *
 * All 3 endpoints are PUBLIC — no auth required. Mirrors the web's
 * public /discover routes. Trade-off: a viewer's blocked profiles are
 * not filtered out. Acceptable for MVP since the discover surface is
 * browsing-oriented; Flutter re-fetches personalised state (shortlist,
 * interest status) when the user taps into a specific profile.
 *
 * Driven by config/discover.php. 13 top-level categories defined there.
 * Each is one of three patterns:
 *   1. subcategories_source — method name on DiscoverConfigService
 *      (e.g. 'nriCountries', 'catholicDenominations')
 *   2. subcategories — literal array of [{label, slug, filter}]
 *   3. direct_filter — no subcategories, skips straight to results
 *
 * Subcategory & filter resolution is inlined here (~25 lines) rather
 * than extracted into DiscoverConfigService, to keep the shared service
 * untouched for this step. Consolidation is a Week-3 buffer task.
 *
 * Design reference:
 *   docs/mobile-app/phase-2a-api/week-03-profiles-photos-search/step-14-discover-endpoints.md
 */
class DiscoverController extends BaseApiController
{
    /** Hub response cached for 5 minutes — config is semi-static. */
    public const HUB_CACHE_TTL_SECONDS = 300;

    /** Per-page for paginated results on direct_filter + subcategory pages. */
    public const RESULTS_PER_PAGE = 20;

    public function __construct(private DiscoverConfigService $discover) {}

    /* ==================================================================
     |  GET /discover — hub (list all categories)
     | ================================================================== */

    /**
     * @unauthenticated
     *
     * @group Discover
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": [
     *     {"category": "nri-matrimony", "label": "NRI Matrimony", "show_search": true, "has_subcategories": true, "has_direct_filter": false},
     *     {"category": "kannadiga-matrimony", "label": "Kannadiga Matrimony", "show_search": false, "has_subcategories": false, "has_direct_filter": true}
     *   ]
     * }
     */
    public function hub(): JsonResponse
    {
        $data = Cache::remember('api:v1:discover:hub', self::HUB_CACHE_TTL_SECONDS, function () {
            return $this->buildHubData();
        });

        return ApiResponse::ok($data);
    }

    /**
     * Extracted so tests can call it directly (cache bypass) and
     * production can cache via the closure above.
     */
    protected function buildHubData(): array
    {
        $categories = config('discover', []);
        if (! is_array($categories)) {
            return [];
        }

        return collect($categories)
            ->map(fn ($cfg, $key) => [
                'category' => (string) $key,
                'label' => (string) ($cfg['label'] ?? $key),
                'show_search' => (bool) ($cfg['show_search'] ?? true),
                'has_subcategories' => ! empty($cfg['subcategories_source'])
                    || ! empty($cfg['subcategories']),
                'has_direct_filter' => ! empty($cfg['direct_filter']),
            ])
            ->values()
            ->all();
    }

    /* ==================================================================
     |  GET /discover/{category}
     | ================================================================== */

    /**
     * Polymorphic response:
     *   - For direct_filter categories → paginated cards + meta
     *   - For subcategory-based categories → subcategory list
     *
     * Flutter uses the hub's has_subcategories / has_direct_filter
     * discriminators to predict which shape to expect.
     *
     * @unauthenticated
     *
     * @group Discover
     *
     * @urlParam category string required e.g. nri-matrimony, kannadiga-matrimony.
     *
     * @response 200 scenario="subcategories" {
     *   "success": true,
     *   "data": {"category": "nri-matrimony", "label": "NRI Matrimony",
     *            "subcategories": [{"label": "USA Profiles", "slug": "usa", "filter": {"residing_country": "USA"}}]}
     * }
     *
     * @response 200 scenario="direct-results" {
     *   "success": true,
     *   "data": [{"matri_id": "AM100042"}],
     *   "meta": {"page": 1, "per_page": 20, "total": 137, "last_page": 7, "category": "kannadiga-matrimony", "label": "Kannadiga Matrimony", "direct_filter": {"mother_tongue": "Kannada"}}
     * }
     *
     * @response 404 scenario="unknown-category" {"success": false, "error": {"code": "NOT_FOUND", "message": "Category not found."}}
     */
    public function category(string $category): JsonResponse
    {
        $config = config("discover.{$category}");
        if (! $config) {
            return ApiResponse::error(
                'NOT_FOUND',
                'Category not found.',
                null,
                404,
            );
        }

        // direct_filter category — skip subcategory page, return results
        // directly (matches web's pattern).
        if (! empty($config['direct_filter'])) {
            $paginator = $this->executePaginated(
                $this->buildGuestQuery($config['direct_filter']),
                self::RESULTS_PER_PAGE,
            );

            return ApiResponse::paginated(
                $paginator,
                ProfileCardResource::class,
                [
                    'category' => $category,
                    'label' => (string) $config['label'],
                    'direct_filter' => $config['direct_filter'],
                ],
            );
        }

        // subcategory-based — return the list of available subcategories.
        return ApiResponse::ok([
            'category' => $category,
            'label' => (string) $config['label'],
            'show_search' => (bool) ($config['show_search'] ?? true),
            'subcategories' => $this->resolveSubcategoriesFor($category),
        ]);
    }

    /* ==================================================================
     |  GET /discover/{category}/{slug}
     | ================================================================== */

    /**
     * Paginated results for a specific subcategory slug.
     *
     * @unauthenticated
     *
     * @group Discover
     *
     * @urlParam category string required Category key.
     * @urlParam slug string required Subcategory slug (e.g. "usa", "hindu-brahmin").
     *
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": [{"matri_id": "AM100042"}],
     *   "meta": {"page": 1, "per_page": 20, "total": 47, "last_page": 3, "category": "nri-matrimony", "slug": "usa", "filter": {"residing_country": "USA"}}
     * }
     *
     * @response 404 scenario="unknown-category" {"success": false, "error": {"code": "NOT_FOUND", "message": "Category not found."}}
     * @response 404 scenario="unknown-slug" {"success": false, "error": {"code": "NOT_FOUND", "message": "Subcategory not found."}}
     */
    public function results(string $category, string $slug): JsonResponse
    {
        // Category must exist + have subcategories (not direct_filter).
        $config = config("discover.{$category}");
        if (! $config) {
            return ApiResponse::error(
                'NOT_FOUND',
                'Category not found.',
                null,
                404,
            );
        }

        $filter = $this->resolveFilterFor($category, $slug);
        if ($filter === null) {
            return ApiResponse::error(
                'NOT_FOUND',
                'Subcategory not found.',
                null,
                404,
            );
        }

        $paginator = $this->executePaginated(
            $this->buildGuestQuery($filter),
            self::RESULTS_PER_PAGE,
        );

        return ApiResponse::paginated(
            $paginator,
            ProfileCardResource::class,
            [
                'category' => $category,
                'slug' => $slug,
                'filter' => $filter,
            ],
        );
    }

    /* ==================================================================
     |  Helpers — subcategory + filter resolution
     | ================================================================== */

    /**
     * Return the subcategory list for a category:
     *   - If the config has `subcategories_source` → call that method on
     *     DiscoverConfigService (dynamic list like nriCountries).
     *   - Else if it has a literal `subcategories` array → return that.
     *   - Else (direct_filter or unknown) → empty array.
     *
     * Each entry is {label, slug, filter} per the service convention.
     */
    protected function resolveSubcategoriesFor(string $category): array
    {
        $config = config("discover.{$category}");
        if (! $config) {
            return [];
        }

        // Pattern A: dynamic list from service method.
        $source = $config['subcategories_source'] ?? null;
        if ($source && method_exists($this->discover, $source)) {
            try {
                $result = $this->discover->{$source}();

                return is_array($result) ? $result : [];
            } catch (\Throwable $e) {
                // Reference-data lookup failure (e.g. allCastes hits Community
                // model / DB) — degrade to empty list rather than 500.
                return [];
            }
        }

        // Pattern B: literal array.
        if (! empty($config['subcategories']) && is_array($config['subcategories'])) {
            return $config['subcategories'];
        }

        return [];
    }

    /**
     * Given a category + slug, return the matching subcategory's filter
     * array, or null if no match. Slug-matching is exact.
     */
    protected function resolveFilterFor(string $category, string $slug): ?array
    {
        $subs = $this->resolveSubcategoriesFor($category);

        foreach ($subs as $sub) {
            if (($sub['slug'] ?? null) === $slug) {
                return $sub['filter'] ?? [];
            }
        }

        return null;
    }

    /* ==================================================================
     |  Query building
     | ================================================================== */

    /**
     * Build the public (guest) Profile query with the given discover
     * filter applied. Mirrors the web DiscoverController's guest query
     * shape: active + approved + not hidden + filter columns mapped by
     * category convention.
     *
     * NOTE: does NOT apply per-viewer privacy (blocked, hidden-from-me,
     * visibility prefs). Matches web's public browsing path. Optional-
     * auth retrofit (use baseQuery when a token is provided) is a
     * Week-3 buffer task.
     */
    protected function buildGuestQuery(array $filters): Builder
    {
        $query = Profile::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('is_hidden', false)->orWhereNull('is_hidden'))
            // approved() scope — same one the web uses.
            ->where('is_approved', true)
            ->with(['primaryPhoto', 'religiousInfo', 'educationDetail', 'locationInfo', 'user.userMemberships']);

        // Map each filter key to the right column / relation. Mirrors the
        // web DiscoverController's filter mapping. Unknown keys are
        // silently ignored so config changes can't break the endpoint.
        foreach ($filters as $key => $value) {
            match ($key) {
                'religion',
                'denomination',
                'caste',
                'sub_caste',
                'diocese',
                'muslim_sect',
                'muslim_community',
                'jain_sect',
                'nakshatra',
                'manglik' => $query->whereHas(
                    'religiousInfo',
                    fn (Builder $q) => $q->where($key, $value),
                ),

                'occupation',
                'education_level' => $query->whereHas(
                    'educationDetail',
                    fn (Builder $q) => $q->where($key, $value),
                ),

                'native_country',
                'native_state',
                'native_district',
                'residing_country' => $query->whereHas(
                    'locationInfo',
                    fn (Builder $q) => $q->where($key, $value),
                ),

                'mother_tongue',
                'marital_status',
                'gender' => $query->where($key, $value),

                default => null,  // unknown filter key — silently skip
            };
        }

        return $query;
    }

    /**
     * Protected seam — tests stub this with a pre-built paginator to
     * avoid running the query against SQLite :memory: (which lacks
     * the profile + related tables).
     */
    protected function executePaginated(Builder $query, int $perPage): LengthAwarePaginator
    {
        return $query->paginate($perPage);
    }
}
