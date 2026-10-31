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
     * Discover hub sections with cards, in this order, after the Communities
     * chips (Oct 2026). "religion" cards (Catholic, Diocese, Muslim…) are not on
     * the hub — the communities list holds them, as on the homepage, and the
     * Catholic sites get their dioceses as chips too.
     */
    public const HUB_GROUPS = [
        'language' => 'Language',
        'place' => 'Place',
        'work' => 'Work',
        'life' => 'Life situation',
    ];

    /** A category needs this many live profiles for a button (like the homepage chips). */
    public const HUB_MIN_PROFILES = 3;

    /**
     * The categories that get a button — on the hub, the member dashboard and a
     * category page's "other directories" (Oct 2026): not the ones that repeat
     * another (hub => false), only with 3+ live profiles, biggest first.
     *
     * @return \Illuminate\Support\Collection<string, array{slug: string, label: string, hub_label: string, group: string, count: int}>
     */
    public static function hubSections(): \Illuminate\Support\Collection
    {
        return collect(\App\Support\NriWording::all())
            ->reject(fn ($cat) => ($cat['hub'] ?? true) === false)
            ->map(fn ($cat, $slug) => [
                'slug' => $slug,
                'label' => $cat['label'],
                'hub_label' => $cat['hub_label'] ?? $cat['label'],
                'group' => $cat['hub_group'] ?? 'community',
                'order' => $cat['hub_order'] ?? 99,
                'count' => array_sum(self::profileCounts($slug)),
            ])
            ->filter(fn ($s) => $s['count'] >= self::HUB_MIN_PROFILES)
            ->sortByDesc('count');
    }

    /**
     * Level 1: the Discover hub — cards grouped under headings, each with its
     * number of profiles and its three biggest entries (Oct 2026).
     */
    public function hub()
    {
        // Communities first, as chips — the same list as the homepage's Community tab
        // (castes, denominations and sects together; hidden religions left out).
        // One chip alone is no choice: on Crasta it is "Roman Catholic", nearly everyone.
        $communities = collect(self::homeLinkCounts('community-matrimony'))
            ->filter(fn ($link) => $link['count'] >= self::HUB_MIN_PROFILES)
            ->values();
        // the Catholic sites (every member Christian) browse by diocese as well
        $dioceses = \App\Support\SiteReligions::christianOnly()
            ? collect(self::homeLinkCounts('diocese-matrimony'))->filter(fn ($link) => $link['count'] >= self::HUB_MIN_PROFILES)->values()
            : collect();
        $chipRows = collect([
            ['title' => 'Communities', 'links' => $communities->count() >= 2 ? $communities : collect(), 'all' => 'community-matrimony', 'all_label' => 'All communities'],
            ['title' => 'Dioceses', 'links' => $dioceses, 'all' => 'diocese-matrimony', 'all_label' => 'All dioceses'],
        ])->filter(fn ($row) => $row['links']->isNotEmpty())->values();

        $sections = self::hubSections()
            ->filter(fn ($s) => isset(self::HUB_GROUPS[$s['group']]))
            ->map(fn ($s) => $s + [
                'top' => collect(self::homeLinkCounts($s['slug']))->take(3)
                    ->map(fn ($link) => trim(preg_replace('/\s+Matrimony$/i', '', $link['label'])))->all(),
            ]);
        $groups = collect(self::HUB_GROUPS)
            ->map(fn ($title, $key) => ['title' => $title, 'sections' => $sections->where('group', $key)
                ->sortBy([['order', 'asc'], ['count', 'desc']])->values()])
            ->filter(fn ($group) => $group['sections']->isNotEmpty());
        $seo = \App\Support\DiscoverSeo::forHub();

        return view('discover.hub', compact('chipRows', 'groups', 'seo'));
    }

    /**
     * Level 2: Subcategory list for a category.
     * If category has no subcategories (direct_filter), shows results directly.
     */
    public function category(string $category)
    {
        $config = \App\Support\NriWording::config($category);
        if (!$config) {
            abort(404, 'Category not found.');
        }

        // Direct filter categories (e.g., Kannadiga) skip to results
        if (isset($config['direct_filter'])) {
            return $this->showResults($category, null, $config['label'], $config['direct_filter']);
        }

        $subcategories = self::withMembers($category, $this->resolveSubcategories($config));
        $showSearch = ($config['show_search'] ?? false) && count($subcategories) > 12;

        // Other directories (cross-links to other categories that have members)
        $otherCategories = self::hubSections()   // biggest first, no repeats
            ->except($category)
            ->map(fn ($s) => ['label' => $s['label'], 'slug' => $s['slug']])
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
        $config = \App\Support\NriWording::config($category);
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
        $config = \App\Support\NriWording::config($category);

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
        // Community > Roman Catholic lists the same people as Catholic > Roman Catholic
        if ($category === 'community-matrimony') {
            $home = collect(app(\App\Services\DiscoverConfigService::class)->communities())->firstWhere('slug', $slug)['canonical_category'] ?? null;
            if ($home) {
                return route('discover.results', [$home, $slug]);
            }
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
                // a list for a profession group ("Doctors & Nurses")
                'occupation' => $query->whereHas('educationDetail', fn($q) => is_array($value) ? $q->whereIn('occupation', $value) : $q->where('occupation', $value)),
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
        foreach (array_keys(config('discover', [])) as $category) {
            if ($category === self::DUPLICATE_OF_COMMUNITY || isset(config("discover.{$category}")['direct_filter'])) {
                continue;
            }
            foreach (array_keys(self::profileCounts($category)) as $slug) {
                $urls[] = self::canonicalUrl($category, $slug);   // e.g. Community > Roman Catholic → Catholic > Roman Catholic
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * Only the communities / places / jobs that have members, biggest first,
     * each with its 'count' (Oct 2026: NRI listed ~190 countries, most empty).
     */
    public static function withMembers(string $category, array $subcategories): array
    {
        $counts = self::profileCounts($category);

        return collect($subcategories)
            ->filter(fn ($sub) => isset($counts[$sub['slug']]))
            ->map(fn ($sub) => $sub + ['count' => $counts[$sub['slug']]])
            ->sortBy([['count', 'desc'], ['label', 'asc']])
            ->values()
            ->all();
    }

    /**
     * Homepage "Browse profiles by" links (Oct 2026): the subcategories of a
     * category that have members, as ['label' => 'Bunts', 'url' => …] with
     * "Profiles" dropped from the label. Biggest first, or A–Z when asked.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public static function homeLinks(string $category, int $limit = 0, bool $alphabetical = false): array
    {
        $links = collect(self::homeLinkCounts($category))->map(fn ($link) => ['label' => $link['label'], 'url' => $link['url']]);
        if ($alphabetical) {
            $links = $links->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE);
        }

        return ($limit > 0 ? $links->take($limit) : $links)->values()->all();
    }

    /**
     * The same links with their member counts, biggest first:
     * [['label' => 'Bunts', 'url' => …, 'count' => 59], …].
     *
     * @return array<int, array{label: string, url: string, count: int}>
     */
    public static function homeLinkCounts(string $category): array
    {
        $config = config("discover.{$category}");
        if (! $config || isset($config['direct_filter'])) {
            return [];
        }

        try {
            $subs = self::withMembers($category, app(self::class)->resolveSubcategories($config));
        } catch (\Throwable $e) {
            report($e);

            return [];
        }

        return collect($subs)->map(fn ($sub) => [
            'label' => trim(preg_replace('/\s+Profiles$/i', '', $sub['label'])),
            'url' => self::canonicalUrl($category, $sub['slug']),   // the page Google is told to show
            'count' => (int) $sub['count'],
        ])->values()->all();
    }

    /**
     * Public profiles per subcategory of a Discover category, biggest first,
     * empty ones left out: [slug => count]. A direct-filter category (e.g.
     * Kannadiga) gives ['' => count]. Plain array, cached 1 hour.
     *
     * @return array<string, int>
     */
    public static function profileCounts(string $category): array
    {
        $config = config("discover.{$category}");
        if (! $config) {
            return [];
        }

        try {
            return \Illuminate\Support\Facades\Cache::remember("discover.counts.{$category}", 3600, function () use ($config) {
                $subs = isset($config['direct_filter'])
                    ? [['slug' => '', 'filter' => $config['direct_filter']]]
                    : app(self::class)->resolveSubcategories($config);

                $counts = [];
                foreach ($subs as $sub) {
                    $count = self::applyFilters(
                        Profile::where('is_active', true)->approved()->where(fn ($q) => $q->where('is_hidden', false)->orWhereNull('is_hidden')),
                        $sub['filter'] ?? []
                    )->count();
                    if ($count > 0) {
                        $counts[$sub['slug']] = $count;
                    }
                }
                arsort($counts);

                return $counts;
            });
        } catch (\Throwable $e) {
            report($e);

            return []; // counting failed: show nothing rather than break the page
        }
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
