<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Traits\ProfileQueryFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class DiscoverController extends Controller
{
    use ProfileQueryFilters;

    /** Old slug => new slug for communities that were renamed (Oct 2026 caste-list update). */
    private const RENAMED_SLUGS = [
        'gowda-tulu' => 'gowda-tulu-arebhashe',
        // Kannada Dating, 11 Oct 2026 (communities:kannada-update)
        'tulu-gowda' => 'gowda-tulu-arebhashe',
        'devadiga' => 'devadiga-moily',
        'kunchitiga' => 'vokkaliga',
        'smartha-brahmin' => 'brahmin',
        'madhwa-brahmin' => 'brahmin',
        'havyaka-brahmin' => 'brahmin',
        'shivalli-brahmin' => 'brahmin',
        'kota-brahmin' => 'brahmin',
        'sthanika-brahmin' => 'brahmin',
        'hoysala-karnataka-brahmin' => 'brahmin',
        'deshastha-brahmin' => 'brahmin',
    ];

    /** Its caste pages repeat Community Matrimony's (see canonicalUrl()). */
    private const DUPLICATE_OF_COMMUNITY = 'hindu-matrimony';

    /**
     * Level 1: Category hub — grid of all browsing categories.
     */
    public function hub()
    {
        $categories = collect(config('discover'))->map(fn($cat, $slug) => [
            'label' => $cat['label'],
            'slug' => $slug,
            'has_subcategories' => !isset($cat['direct_filter']),
        ]);
        $seo = \App\Support\DiscoverSeo::forHub();

        return view('discover.hub', compact('categories', 'seo'));
    }

    /**
     * Level 2: Subcategory list for a category.
     * If category has no subcategories (direct_filter), shows results directly.
     */
    public function category(string $category)
    {
        $config = config("discover.{$category}");
        if (!$config) {
            abort(404, 'Category not found.');
        }

        // Direct filter categories (e.g., Kannadiga) skip to results
        if (isset($config['direct_filter'])) {
            return $this->showResults($category, null, $config['label'], $config['direct_filter']);
        }

        $subcategories = $this->resolveSubcategories($config);
        $showSearch = $config['show_search'] ?? false;

        // Other directories (cross-links to other categories)
        $otherCategories = collect(config('discover'))
            ->except($category)
            ->map(fn($cat, $slug) => ['label' => $cat['label'], 'slug' => $slug])
            ->values()
            ->take(6);
        $seo = \App\Support\DiscoverSeo::forCategory($category, $config);

        return view('discover.category', compact(
            'category', 'config', 'subcategories', 'showSearch', 'otherCategories', 'seo'
        ));
    }

    /**
     * Level 3: Profile results filtered by category + subcategory.
     */
    public function results(string $category, string $slug)
    {
        $config = config("discover.{$category}");
        if (!$config) {
            abort(404, 'Category not found.');
        }

        $subcategories = $this->resolveSubcategories($config);
        $subcategory = collect($subcategories)->firstWhere('slug', $slug);

        if (!$subcategory) {
            // A community that was renamed keeps its old address working
            if (($renamed = self::RENAMED_SLUGS[$slug] ?? null) && collect($subcategories)->firstWhere('slug', $renamed)) {
                return redirect()->route('discover.results', [$category, $renamed], 301);
            }
            // "Other / Not Listed" is not a community, so it has no browse page (Oct 2026)
            if (in_array($slug, array_map([\Illuminate\Support\Str::class, 'slug'], \App\Models\ReligiousInfo::OTHER_CASTE_VALUES), true)) {
                return redirect()->route('discover.category', $category, 301);
            }
            abort(404, 'Subcategory not found.');
        }

        return $this->showResults($category, $slug, $subcategory['label'], $subcategory['filter']);
    }

    /**
     * Shared results logic for both direct-filter categories and subcategory results.
     * Works for both logged-in and guest users.
     */
    private function showResults(string $category, ?string $slug, string $title, array $filter)
    {
        $profile = auth()->check() ? auth()->user()->profile : null;
        $config = config("discover.{$category}");

        // Logged-in: use baseQuery (gender filter, blocked, visibility prefs)
        // Guest: simple public query (active profiles only)
        if ($profile) {
            $query = $this->baseQuery($profile);
        } else {
            $query = Profile::where('is_active', true)
                ->approved()
                ->where(fn($q) => $q->where('is_hidden', false)->orWhereNull('is_hidden'))
                ->with(['primaryPhoto', 'religiousInfo', 'educationDetail', 'locationInfo']);
        }

        self::applyFilters($query, $filter);

        $sort = request()->get('sort', 'newest');
        $query = $this->applySortOrder($query, $sort);
        $results = $query->paginate(20)->withQueryString();
        $currentSort = $sort;
        $seo = \App\Support\DiscoverSeo::forResults($category, $title);
        $canonicalUrl = self::canonicalUrl($category, $slug);

        return view('discover.results', compact(
            'category', 'slug', 'title', 'config', 'results', 'currentSort', 'seo', 'canonicalUrl'
        ));
    }

    /**
     * Hindu > Billava lists the same people as Community > Billava, so Google is
     * told the community page is the one to show (no duplicate pages competing).
     */
    public static function canonicalUrl(string $category, ?string $slug): string
    {
        if ($slug === null) {
            return route('discover.category', $category);
        }
        if ($category === self::DUPLICATE_OF_COMMUNITY
            && collect(app(\App\Services\DiscoverConfigService::class)->allCastes())->contains('slug', $slug)) {
            return route('discover.results', ['community-matrimony', $slug]);
        }

        return route('discover.results', [$category, $slug]);
    }

    /**
     * Apply sort order to query.
     * Uses subqueries instead of JOINs to avoid ambiguous column issues with baseQuery.
     */
    private function applySortOrder(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'recently_active' => $query->completedFirst()
                ->orderByRaw('(SELECT last_login_at FROM users WHERE users.id = profiles.user_id) IS NULL ASC')
                ->orderByRaw('(SELECT last_login_at FROM users WHERE users.id = profiles.user_id) DESC'),

            'age_low' => $query->completedFirst()
                ->orderByRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) ASC'),

            'age_high' => $query->completedFirst()
                ->orderByRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) DESC'),

            // Default: paid boosts → finished → with photo → recently active → newest
            // (Profile::scopeDefaultOrder — one definition for every listing)
            default => $query->defaultOrder(),
        };
    }

    /**
     * Resolve subcategories — handles three forms:
     *   1. literal array in 'subcategories' key
     *   2. method name string in 'subcategories_source' key (resolved via DiscoverConfigService)
     *   3. legacy callable in 'subcategories' key (kept for backwards compatibility)
     */
    /** The Discover filters (religion, caste, country...) on a profile query. */
    public static function applyFilters(Builder $query, array $filter): Builder
    {
        foreach ($filter as $type => $value) {
            match ($type) {
                'religion' => $query->whereHas('religiousInfo', fn($q) => $q->where('religion', $value)),
                'denomination' => $query->whereHas('religiousInfo', fn($q) => $q->where('denomination', $value)),
                'caste' => $query->whereHas('religiousInfo', fn($q) => $q->where('caste', $value)),
                'diocese' => $query->whereHas('religiousInfo', fn($q) => $q->where('diocese', $value)),
                'muslim_sect' => $query->whereHas('religiousInfo', fn($q) => $q->where('muslim_sect', $value)),
                'muslim_community' => $query->whereHas('religiousInfo', fn($q) => $q->where('muslim_community', $value)),
                'jain_sect' => $query->whereHas('religiousInfo', fn($q) => $q->where('jain_sect', $value)),
                'marital_status' => $query->where('marital_status', $value),
                'mother_tongue' => $query->where('mother_tongue', $value),
                'occupation' => $query->whereHas('educationDetail', fn($q) => $q->where('occupation', $value)),
                'native_state' => $query->whereHas('locationInfo', fn($q) => $q->where('native_state', $value)),
                'native_district' => $query->whereHas('locationInfo', fn($q) => $q->where('native_district', $value)),
                'residing_country' => $query->whereHas('locationInfo', fn($q) => $q->where('residing_country', $value)),
                default => null,
            };
        }

        return $query;
    }

    /**
     * Every Discover page that has at least one public profile (for the
     * sitemap, Oct 2026): Google was crawling hundreds of empty ones
     * ("No profiles found") and filing them as soft 404s.
     *
     * @return array<int, string> URLs
     */
    public static function populatedPageUrls(): array
    {
        $urls = [];
        $self = app(self::class);
        foreach (config('discover', []) as $category => $config) {
            if ($category === self::DUPLICATE_OF_COMMUNITY) {
                continue;
            }
            foreach ($self->resolveSubcategories($config) as $sub) {
                $count = self::applyFilters(
                    Profile::where('is_active', true)->approved()->where(fn ($q) => $q->where('is_hidden', false)->orWhereNull('is_hidden')),
                    $sub['filter'] ?? []
                )->count();
                if ($count > 0) {
                    $urls[] = route('discover.results', [$category, $sub['slug']]);
                }
            }
        }

        return $urls;
    }

    private function resolveSubcategories(array $config): array
    {
        if (isset($config['subcategories_source'])) {
            $method = $config['subcategories_source'];
            return app(\App\Services\DiscoverConfigService::class)->{$method}();
        }

        $subs = $config['subcategories'] ?? [];
        return is_callable($subs) ? $subs() : $subs;
    }
}
