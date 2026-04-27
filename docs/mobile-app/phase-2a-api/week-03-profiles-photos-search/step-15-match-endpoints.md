# Step 15 — Match Endpoints (My Matches, Mutual, Score)

## Goal
- `GET /api/v1/matches/my` — profiles matching user's PartnerPreference
- `GET /api/v1/matches/mutual` — bidirectional matches
- `GET /api/v1/matches/score/{matriId}` — on-demand score compute

## Procedure

### 1. `MatchController`

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ProfileCardResource;
use App\Http\Responses\ApiResponse;
use App\Models\Profile;
use App\Services\MatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends BaseApiController
{
    public function __construct(private MatchingService $matches) {}

    /**
     * @authenticated
     * @group Matches
     */
    public function my(Request $request): JsonResponse
    {
        $viewer = $request->user()->profile;
        $minScore = (int) $request->query('min_score', 0);
        $perPage = min((int) $request->query('per_page', 20), 50);

        $paginator = $this->matches->findMatches($viewer, $minScore)->paginate($perPage);

        return ApiResponse::paginated($paginator, ProfileCardResource::class);
    }

    /**
     * @authenticated
     * @group Matches
     */
    public function mutual(Request $request): JsonResponse
    {
        $viewer = $request->user()->profile;
        $cacheKey = "mutual_matches:{$viewer->id}";

        $paginator = \Illuminate\Support\Facades\Cache::remember(
            $cacheKey,
            now()->addHours(6),
            fn () => $this->matches->findMutualMatches($viewer)->paginate(20),
        );

        return ApiResponse::paginated($paginator, ProfileCardResource::class);
    }

    /**
     * @authenticated
     * @group Matches
     */
    public function score(Request $request, string $matriId): JsonResponse
    {
        $target = Profile::where('matri_id', $matriId)->firstOrFail();
        $viewer = $request->user()->profile;

        abort_if($viewer->gender === $target->gender, 403);

        $cacheKey = "match_score:{$viewer->id}:{$target->id}";
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if ($cached) {
            return ApiResponse::ok(array_merge($cached, ['cached' => true]));
        }

        $score = $this->matches->calculateScore($target, $viewer->partnerPreference);
        \Illuminate\Support\Facades\Cache::put($cacheKey, $score, now()->addDay());

        return ApiResponse::ok(array_merge($score, ['cached' => false]));
    }
}
```

### 2. Routes

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/matches/my', [\App\Http\Controllers\Api\V1\MatchController::class, 'my']);
    Route::get('/matches/mutual', [\App\Http\Controllers\Api\V1\MatchController::class, 'mutual']);
    Route::get('/matches/score/{matriId}', [\App\Http\Controllers\Api\V1\MatchController::class, 'score'])
        ->middleware('throttle:30,60');
});
```

### 3. Test

```bash
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/v1/matches/my | jq '.meta.total'
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/v1/matches/mutual | jq '.meta.total'
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/v1/matches/score/AM200001 | jq
```

## Verification

- [ ] `my` returns profiles matching viewer's preferences
- [ ] `mutual` returns bi-directional matches
- [ ] Score endpoint caches; second call returns `cached: true`
- [ ] Same-gender score returns 403

## Commit

```bash
git add app/Http/Controllers/Api/V1/MatchController.php routes/api.php
git commit -m "phase-2a wk-03: step-15 match endpoints (my/mutual/score)"
```

## Next step
→ [week-03-acceptance.md](week-03-acceptance.md)
