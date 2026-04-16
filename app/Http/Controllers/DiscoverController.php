<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Traits\ProfileQueryFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class DiscoverController extends Controller
{
    use ProfileQueryFilters;

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

        return view('discover.hub', compact('categories'));
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

        return view('discover.category', compact(
            'category', 'config', 'subcategories', 'showSearch', 'otherCategories'
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

        // Apply filters
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

        $sort = request()->get('sort', 'newest');
        $query = $this->applySortOrder($query, $sort);
        $results = $query->paginate(20)->withQueryString();
        $currentSort = $sort;

        return view('discover.results', compact(
            'category', 'slug', 'title', 'config', 'results', 'currentSort'
        ));
    }

    /**
     * Apply sort order to query.
     * Uses subqueries instead of JOINs to avoid ambiguous column issues with baseQuery.
     */
    private function applySortOrder(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'recently_active' => $query
                ->orderByRaw('(SELECT last_login_at FROM users WHERE users.id = profiles.user_id) IS NULL ASC')
                ->orderByRaw('(SELECT last_login_at FROM users WHERE users.id = profiles.user_id) DESC'),

            'age_low' => $query
                ->orderByRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) ASC'),

            'age_high' => $query
                ->orderByRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) DESC'),

            // Default: newest first
            default => $query->orderBy('profiles.created_at', 'desc'),
        };
    }

    /**
     * Resolve subcategories — handles both arrays and callables.
     */
    private function resolveSubcategories(array $config): array
    {
        $subs = $config['subcategories'] ?? [];
        return is_callable($subs) ? $subs() : $subs;
    }
}
