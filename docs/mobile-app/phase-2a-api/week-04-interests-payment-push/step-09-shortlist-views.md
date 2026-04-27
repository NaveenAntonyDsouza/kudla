# Step 9 — Shortlist + Views Endpoints

## Goal
- `GET /api/v1/shortlist` — list
- `POST /api/v1/profiles/{matriId}/shortlist` — toggle
- `GET /api/v1/views?tab=viewed_by|i_viewed` — profile views

## Procedure

### 1. `ShortlistController`

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ProfileCardResource;
use App\Http\Responses\ApiResponse;
use App\Models\Profile;
use App\Models\Shortlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShortlistController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $profile = $request->user()->profile;

        $paginator = Profile::whereIn('id', function ($q) use ($profile) {
            $q->select('shortlisted_profile_id')->from('shortlists')->where('profile_id', $profile->id);
        })->with(['religiousInfo', 'educationDetail', 'locationInfo', 'primaryPhoto', 'user'])
          ->paginate(20);

        return ApiResponse::paginated($paginator, ProfileCardResource::class);
    }

    public function toggle(Request $request, string $matriId): JsonResponse
    {
        $target = Profile::where('matri_id', $matriId)->firstOrFail();
        $viewer = $request->user()->profile;

        abort_if($viewer->id === $target->id, 400);
        abort_if($viewer->gender === $target->gender, 403);

        $existing = Shortlist::where('profile_id', $viewer->id)->where('shortlisted_profile_id', $target->id)->first();

        if ($existing) {
            $existing->delete();
            $isShortlisted = false;
        } else {
            Shortlist::create(['profile_id' => $viewer->id, 'shortlisted_profile_id' => $target->id]);
            $isShortlisted = true;

            // Notify the target
            app(\App\Services\NotificationService::class)->dispatch(
                $target->user, 'profile.shortlisted',
                'Someone shortlisted you',
                ($viewer->full_name ?? 'Someone') . ' added you to their shortlist.',
                ['matri_id' => $viewer->matri_id],
            );
        }

        $total = Shortlist::where('profile_id', $viewer->id)->count();
        return ApiResponse::ok(['is_shortlisted' => $isShortlisted, 'shortlist_count' => $total]);
    }
}
```

### 2. `ProfileViewController` — GET /views

```php
public function index(Request $request): JsonResponse
{
    $tab = $request->query('tab', 'viewed_by');
    $profile = $request->user()->profile;
    $isPremium = (bool) $profile->user?->activeMembership;

    if ($tab === 'viewed_by') {
        $total = \App\Models\ProfileView::where('profile_id', $profile->id)->count();

        if (! $isPremium) {
            return ApiResponse::ok([
                'total_count' => $total,
                'is_premium' => false,
                'viewers' => [],  // hidden for free users
            ]);
        }

        $views = \App\Models\ProfileView::where('profile_id', $profile->id)
            ->select('viewer_id', \DB::raw('COUNT(*) as view_count'), \DB::raw('MAX(created_at) as last_viewed_at'))
            ->groupBy('viewer_id')
            ->orderByDesc('last_viewed_at')
            ->paginate(20);

        // ... map to profile cards with view_count
        return ApiResponse::ok([
            'total_count' => $total,
            'is_premium' => true,
            'viewers' => $views->items(),  // simplified
        ], [
            'page' => $views->currentPage(),
            'per_page' => $views->perPage(),
            'total' => $views->total(),
            'last_page' => $views->lastPage(),
        ]);
    }

    // tab === 'i_viewed'
    // ... returns profiles viewer has viewed
    return ApiResponse::ok(['viewed_profiles' => []]);
}
```

### 3. Routes

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/shortlist', [\App\Http\Controllers\Api\V1\ShortlistController::class, 'index']);
    Route::post('/profiles/{matriId}/shortlist', [\App\Http\Controllers\Api\V1\ShortlistController::class, 'toggle']);
    Route::get('/views', [\App\Http\Controllers\Api\V1\ProfileViewController::class, 'index']);
});
```

## Verification
- [ ] Shortlist toggle creates/deletes row
- [ ] Views tab=viewed_by shows total but empty viewers for free users
- [ ] Premium user sees full viewers list with view counts

## Commit
```bash
git commit -am "phase-2a wk-04: step-09 shortlist + views endpoints"
```

## Next step
→ [step-10-block-report-ignore.md](step-10-block-report-ignore.md)
