# Step 12 — `GET /api/v1/search` (Partner Search)

## Goal
Main search endpoint. 15+ filter params, sort, pagination. Reuse existing `ProfileQueryFilters` trait.

**Design ref:** [`design/06-search-discover-api.md §6.1`](../../design/06-search-discover-api.md)

## Procedure

### 1. Create `SearchController`

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ProfileCardResource;
use App\Http\Responses\ApiResponse;
use App\Models\Profile;
use App\Traits\ProfileQueryFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends BaseApiController
{
    use ProfileQueryFilters;  // existing trait from web

    /**
     * @authenticated
     * @group Search
     * @queryParam age_from integer
     * @queryParam age_to integer
     * @queryParam religions string Comma-separated
     * @queryParam castes string Comma-separated
     * @queryParam sort string relevance|newest|recently_active|age_asc|age_desc|match_score
     */
    public function partner(Request $request): JsonResponse
    {
        $viewer = $request->user()->profile;
        abort_if(! $viewer, 422);

        $perPage = min((int) $request->query('per_page', 20), 50);

        $query = Profile::query()
            ->where('is_active', true)
            ->where('is_approved', true)
            ->where('is_hidden', false)
            ->where('gender', '!=', $viewer->gender)
            ->where('id', '!=', $viewer->id)
            ->whereNotIn('id', function ($q) use ($viewer) {
                $q->select('blocked_profile_id')->from('blocked_profiles')->where('blocker_profile_id', $viewer->id);
            })
            ->whereNotIn('id', function ($q) use ($viewer) {
                $q->select('ignored_profile_id')->from('ignored_profiles')->where('profile_id', $viewer->id);
            })
            ->with(['religiousInfo', 'educationDetail', 'locationInfo', 'primaryPhoto', 'user']);

        // Apply filters using the trait (pass request query string)
        $this->applyFilters($query, $request);

        // Sort
        $this->applySort($query, $request->query('sort', 'relevance'));

        $paginator = $query->paginate($perPage);

        return ApiResponse::paginated($paginator, ProfileCardResource::class, [
            'applied_filters' => $request->only([
                'age_from', 'age_to', 'religions', 'castes',
                // etc — echo back for UI "active filters"
            ]),
        ]);
    }

    private function applySort($query, string $sort): void
    {
        match ($sort) {
            'newest' => $query->latest('created_at'),
            'recently_active' => $query->orderByDesc(\App\Models\User::select('last_login_at')->whereColumn('users.id', 'profiles.user_id')),
            'age_asc' => $query->orderBy('date_of_birth', 'desc'),  // youngest first
            'age_desc' => $query->orderBy('date_of_birth', 'asc'),
            'match_score' => $query->orderByDesc(/* cached match_score — see design */),
            default => $query->orderByRaw('CASE WHEN is_vip = 1 THEN 0 WHEN is_featured = 1 THEN 1 ELSE 2 END')->latest('created_at'),
        };
    }
}
```

> **Note:** `applyFilters()` is the existing method on `ProfileQueryFilters` trait. Open the trait to confirm it works with a request (may need to adapt for query params vs form data).

### 2. Register route

```php
Route::get('/search', [\App\Http\Controllers\Api\V1\SearchController::class, 'partner']);
```

### 3. Test

```bash
curl -H "Authorization: Bearer $TOKEN" "http://localhost:8000/api/v1/search?religions=Hindu&castes=Brahmin&age_from=25&age_to=30&sort=newest&per_page=10" | jq '.meta.total'
```

### 4. Pest test

```php
it('searches by religion filter', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id, 'gender' => 'Male']);

    Profile::factory()->count(5)->create(['gender' => 'Female']);
    Profile::factory()->for(/* religion=Hindu via related model */)->create(['gender' => 'Female']);

    $token = $user->createToken('t')->plainTextToken;
    $response = getJson('/api/v1/search?religions=Hindu', ['Authorization' => "Bearer $token"]);

    $response->assertOk()->assertJsonStructure(['data', 'meta' => ['page', 'total']]);
});
```

## Verification

- [ ] Search with religion/caste filter returns narrowed list
- [ ] Sort variants all produce different ordering
- [ ] `per_page > 50` is capped at 50
- [ ] Blocked + ignored profiles never appear in results

## Commit

```bash
git add app/Http/Controllers/Api/V1/SearchController.php routes/api.php tests/Feature/Api/V1/
git commit -m "phase-2a wk-03: step-12 GET /api/v1/search (partner filters)"
```

## Next step
→ [step-13-keyword-id-saved.md](step-13-keyword-id-saved.md)
