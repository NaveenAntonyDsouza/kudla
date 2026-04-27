# Step 14 — Discover Endpoints (hub + category + results)

## Goal
Public endpoints (no auth required) for the 13 discover categories.

**Design ref:** [`design/06-search-discover-api.md §6.7–6.9`](../../design/06-search-discover-api.md)

## Procedure

### 1. Create `DiscoverController`

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ProfileCardResource;
use App\Http\Responses\ApiResponse;
use App\Services\DiscoverConfigService;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DiscoverController extends BaseApiController
{
    public function __construct(private DiscoverConfigService $discover) {}

    /**
     * @unauthenticated
     * @group Discover
     */
    public function hub(): JsonResponse
    {
        $data = Cache::remember('api:v1:discover:hub', 300, function () {
            $categories = config('discover.categories', []);
            return collect($categories)->map(function ($cat, $key) {
                return [
                    'category' => $key,
                    'label' => $cat['label'] ?? $key,
                    'description' => $cat['description'] ?? '',
                    'icon_url' => $cat['icon_url'] ?? null,
                    'show_search' => (bool) ($cat['show_search'] ?? true),
                    'subcategory_count' => $this->discover->getSubcategoryCount($key),
                    'estimated_profile_count' => $this->discover->getEstimatedCount($key),
                ];
            })->values()->all();
        });

        return ApiResponse::ok($data);
    }

    /**
     * @unauthenticated
     * @group Discover
     */
    public function category(string $category): JsonResponse
    {
        $config = config("discover.categories.{$category}");
        if (! $config) {
            return ApiResponse::error('NOT_FOUND', 'Category not found.', status: 404);
        }

        // direct_filter categories skip subcategory selection
        if (! empty($config['direct_filter'])) {
            $profiles = $this->runFilteredQuery($config['direct_filter']);
            $paginator = $profiles->paginate(20);
            return ApiResponse::paginated($paginator, ProfileCardResource::class, [
                'category' => $category,
                'label' => $config['label'],
                'direct_filter' => $config['direct_filter'],
            ]);
        }

        // Normal categories — return subcategory list
        $subs = $this->discover->getSubcategories($category);

        return ApiResponse::ok([
            'category' => $category,
            'label' => $config['label'],
            'subcategories' => $subs,
        ]);
    }

    /**
     * @unauthenticated
     * @group Discover
     */
    public function results(string $category, string $slug): JsonResponse
    {
        $filters = $this->discover->resolveFiltersFor($category, $slug);
        if (! $filters) {
            return ApiResponse::error('NOT_FOUND', 'Subcategory not found.', status: 404);
        }

        $paginator = $this->runFilteredQuery($filters)->paginate(20);

        return ApiResponse::paginated($paginator, ProfileCardResource::class, [
            'category' => $category,
            'slug' => $slug,
            'filters' => $filters,
        ]);
    }

    private function runFilteredQuery(array $filters)
    {
        $query = Profile::query()
            ->where('is_active', true)
            ->where('is_approved', true)
            ->where('is_hidden', false)
            ->with(['religiousInfo', 'educationDetail', 'locationInfo', 'primaryPhoto', 'user']);

        foreach ($filters as $key => $value) {
            // Map known keys to relation queries
            match ($key) {
                'religion', 'caste', 'sub_caste', 'denomination', 'diocese', 'nakshatra', 'manglik' =>
                    $query->whereHas('religiousInfo', fn ($q) => $q->where($key, $value)),
                'occupation', 'education_level' =>
                    $query->whereHas('educationDetail', fn ($q) => $q->where($key, $value)),
                'native_country', 'native_state', 'native_district', 'residing_country' =>
                    $query->whereHas('locationInfo', fn ($q) => $q->where($key, $value)),
                'mother_tongue', 'marital_status', 'gender' =>
                    $query->where($key, $value),
                default => null,
            };
        }

        return $query;
    }
}
```

### 2. Routes (public)

```php
// Under public section of routes/api.php
Route::get('/discover', [\App\Http\Controllers\Api\V1\DiscoverController::class, 'hub']);
Route::get('/discover/{category}', [\App\Http\Controllers\Api\V1\DiscoverController::class, 'category']);
Route::get('/discover/{category}/{slug}', [\App\Http\Controllers\Api\V1\DiscoverController::class, 'results']);
```

### 3. Test

```bash
curl http://localhost:8000/api/v1/discover | jq '.data | length'
# Expect: 13

curl http://localhost:8000/api/v1/discover/nri-matrimony | jq '.data.subcategories | length'

curl http://localhost:8000/api/v1/discover/kannadiga-matrimony | jq '.data'
# Expect: results list (direct_filter category)
```

## Verification

- [ ] 13 categories returned at `/discover`
- [ ] Each category returns either subcategory list or direct results
- [ ] Cache warms on first hit (second call < 50ms)

## Commit

```bash
git add app/Http/Controllers/Api/V1/DiscoverController.php routes/api.php
git commit -m "phase-2a wk-03: step-14 discover hub + category + results"
```

## Next step
→ [step-15-match-endpoints.md](step-15-match-endpoints.md)
