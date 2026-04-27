# Step 13 — Keyword + Matri-ID Search + Saved Searches

## Goal
- `GET /api/v1/search/keyword?q=...` — fuzzy search (profile bio, occupation, location)
- `GET /api/v1/search/id/{matriId}` — direct lookup
- `GET /api/v1/search/saved` — list user's saved searches
- `POST /api/v1/search/saved` — save current filters
- `DELETE /api/v1/search/saved/{savedSearch}` — remove

## Procedure

### 1. Add methods to `SearchController`

```php
/**
 * @authenticated
 * @group Search
 * @queryParam q string required Search term
 */
public function keyword(Request $request): JsonResponse
{
    $data = $request->validate(['q' => 'required|string|min:2|max:100']);
    $viewer = $request->user()->profile;

    $term = '%' . $data['q'] . '%';
    $query = Profile::query()
        ->where('is_active', true)
        ->where('is_approved', true)
        ->where('gender', '!=', $viewer->gender)
        ->where('id', '!=', $viewer->id)
        ->where(function ($q) use ($term) {
            $q->where('full_name', 'like', $term)
              ->orWhere('about_me', 'like', $term)
              ->orWhereHas('educationDetail', fn ($e) => $e->where('occupation', 'like', $term)->orWhere('employer_name', 'like', $term))
              ->orWhereHas('locationInfo', fn ($e) => $e->where('residing_city', 'like', $term)->orWhere('native_district', 'like', $term));
        })
        ->with(['religiousInfo', 'educationDetail', 'locationInfo', 'primaryPhoto', 'user']);

    $paginator = $query->paginate(20);

    return ApiResponse::paginated($paginator, ProfileCardResource::class, [
        'query_term' => $data['q'],
    ]);
}

/**
 * @authenticated
 * @group Search
 */
public function byMatriId(Request $request, string $matriId): JsonResponse
{
    $target = Profile::where('matri_id', $matriId)->first();
    if (! $target) {
        return ApiResponse::error('NOT_FOUND', 'No profile with that ID.', status: 404);
    }

    $viewer = $request->user()->profile;
    $access = app(\App\Services\ProfileAccessService::class)->check($viewer, $target);
    if ($access !== \App\Services\ProfileAccessService::REASON_OK) {
        return ApiResponse::error('NOT_FOUND', 'Profile not available.', status: 404);
    }

    return ApiResponse::ok((new ProfileCardResource($target))->resolve());
}

/**
 * @authenticated
 * @group Saved Searches
 */
public function savedList(Request $request): JsonResponse
{
    $items = \App\Models\SavedSearch::where('profile_id', $request->user()->profile->id)
        ->latest()
        ->get()
        ->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'filters' => $s->filters,  // stored JSON
            'alert_enabled' => (bool) $s->alert_enabled,
            'last_run_count' => $s->last_run_count,
            'created_at' => $s->created_at->toIso8601String(),
        ]);

    return ApiResponse::ok($items);
}

/**
 * @authenticated
 * @group Saved Searches
 */
public function saveSearch(Request $request): JsonResponse
{
    $data = $request->validate([
        'name' => 'required|string|max:80',
        'filters' => 'required|array',
        'alert_enabled' => 'sometimes|boolean',
    ]);

    $profile = $request->user()->profile;
    $count = \App\Models\SavedSearch::where('profile_id', $profile->id)->count();
    abort_if($count >= 10, 422, 'You have reached the 10 saved searches limit.');

    $saved = \App\Models\SavedSearch::create([
        'profile_id' => $profile->id,
        'name' => $data['name'],
        'filters' => $data['filters'],
        'alert_enabled' => $data['alert_enabled'] ?? false,
    ]);

    return ApiResponse::created([
        'id' => $saved->id,
        'name' => $saved->name,
    ]);
}

/**
 * @authenticated
 * @group Saved Searches
 */
public function deleteSaved(Request $request, \App\Models\SavedSearch $savedSearch): JsonResponse
{
    abort_if($savedSearch->profile_id !== $request->user()->profile->id, 403);
    $savedSearch->delete();
    return ApiResponse::ok(['deleted' => true]);
}
```

### 2. Routes

```php
Route::get('/search/keyword', [\App\Http\Controllers\Api\V1\SearchController::class, 'keyword']);
Route::get('/search/id/{matriId}', [\App\Http\Controllers\Api\V1\SearchController::class, 'byMatriId']);
Route::get('/search/saved', [\App\Http\Controllers\Api\V1\SearchController::class, 'savedList']);
Route::post('/search/saved', [\App\Http\Controllers\Api\V1\SearchController::class, 'saveSearch']);
Route::delete('/search/saved/{savedSearch}', [\App\Http\Controllers\Api\V1\SearchController::class, 'deleteSaved']);
```

## Verification

- [ ] Keyword returns matches
- [ ] Matri-ID lookup returns a card
- [ ] Saving 11th search returns 422
- [ ] DELETE only works on own saved searches

## Commit

```bash
git add app/Http/Controllers/Api/V1/SearchController.php routes/api.php tests/Feature/Api/V1/
git commit -m "phase-2a wk-03: step-13 keyword + id search + saved searches"
```

## Next step
→ [step-14-discover-endpoints.md](step-14-discover-endpoints.md)
