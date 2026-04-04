<?php

namespace App\Http\Controllers;

use App\Traits\ProfileQueryFilters;
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
     */
    private function showResults(string $category, ?string $slug, string $title, array $filter)
    {
        $profile = auth()->user()->profile;
        $config = config("discover.{$category}");
        $query = $this->baseQuery($profile);

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

        $results = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('discover.results', compact(
            'category', 'slug', 'title', 'config', 'results'
        ));
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
