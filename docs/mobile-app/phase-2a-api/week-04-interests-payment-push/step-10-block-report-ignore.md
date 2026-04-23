# Step 10 — Block / Report / Ignore Endpoints

## Goal
6 endpoints covering 3 adjacent features: block (list, add, remove), report (submit), ignore (list, toggle).

## Procedure

### 1. `BlockController`

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ProfileCardResource;
use App\Http\Responses\ApiResponse;
use App\Models\BlockedProfile;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $profile = $request->user()->profile;
        $paginator = Profile::whereIn('id', function ($q) use ($profile) {
            $q->select('blocked_profile_id')->from('blocked_profiles')->where('blocker_profile_id', $profile->id);
        })->with(['primaryPhoto'])->paginate(20);

        return ApiResponse::paginated($paginator, ProfileCardResource::class);
    }

    public function block(Request $request, string $matriId): JsonResponse
    {
        $data = $request->validate(['reason' => 'nullable|string|max:500']);
        $target = Profile::where('matri_id', $matriId)->firstOrFail();
        $viewer = $request->user()->profile;

        abort_if($viewer->id === $target->id, 400);
        abort_if($viewer->gender === $target->gender, 403);

        BlockedProfile::firstOrCreate(
            ['blocker_profile_id' => $viewer->id, 'blocked_profile_id' => $target->id],
            ['reason' => $data['reason'] ?? null],
        );

        // Cancel any pending interests between them
        \App\Models\Interest::where(function ($q) use ($viewer, $target) {
            $q->where(['sender_profile_id' => $viewer->id, 'receiver_profile_id' => $target->id])
              ->orWhere(['sender_profile_id' => $target->id, 'receiver_profile_id' => $viewer->id]);
        })->where('status', 'pending')->delete();

        // Remove shortlist entries
        \App\Models\Shortlist::where('profile_id', $viewer->id)->where('shortlisted_profile_id', $target->id)->delete();

        return ApiResponse::ok(['blocked' => true]);
    }

    public function unblock(Request $request, string $matriId): JsonResponse
    {
        $target = Profile::where('matri_id', $matriId)->firstOrFail();
        BlockedProfile::where('blocker_profile_id', $request->user()->profile->id)
            ->where('blocked_profile_id', $target->id)
            ->delete();
        return ApiResponse::ok(['blocked' => false]);
    }
}
```

### 2. `ReportController`

```php
public function store(Request $request, string $matriId): JsonResponse
{
    $data = $request->validate([
        'reason' => 'required|in:inappropriate_content,fake_profile,harassment_or_abuse,scam_or_fraud,underage,already_married,offensive_messages,other',
        'description' => 'nullable|string|max:2000',
    ]);

    $target = \App\Models\Profile::where('matri_id', $matriId)->firstOrFail();
    $viewer = $request->user()->profile;

    abort_if($viewer->id === $target->id, 400);

    // Dupe check
    $existing = \App\Models\ProfileReport::where('reporter_profile_id', $viewer->id)
        ->where('reported_profile_id', $target->id)
        ->where('status', 'pending')
        ->first();

    if ($existing) {
        return ApiResponse::error('ALREADY_EXISTS', 'You already have a pending report for this profile.', status: 409);
    }

    $report = \App\Models\ProfileReport::create([
        'reporter_profile_id' => $viewer->id,
        'reported_profile_id' => $target->id,
        'reason' => $data['reason'],
        'description' => $data['description'] ?? null,
        'status' => 'pending',
    ]);

    return ApiResponse::created([
        'report_id' => $report->id,
        'status' => 'pending',
        'message' => 'Our team will review within 48 hours.',
    ]);
}
```

### 3. `IgnoredProfileController`

```php
public function index(Request $request): JsonResponse
{
    $profile = $request->user()->profile;
    $paginator = Profile::whereIn('id', function ($q) use ($profile) {
        $q->select('ignored_profile_id')->from('ignored_profiles')->where('profile_id', $profile->id);
    })->with(['primaryPhoto'])->paginate(20);

    return ApiResponse::paginated($paginator, ProfileCardResource::class);
}

public function toggle(Request $request, string $matriId): JsonResponse
{
    $target = Profile::where('matri_id', $matriId)->firstOrFail();
    $viewer = $request->user()->profile;

    $existing = \App\Models\IgnoredProfile::where('profile_id', $viewer->id)
        ->where('ignored_profile_id', $target->id)->first();

    if ($existing) {
        $existing->delete();
        return ApiResponse::ok(['is_ignored' => false]);
    }

    \App\Models\IgnoredProfile::create(['profile_id' => $viewer->id, 'ignored_profile_id' => $target->id]);
    return ApiResponse::ok(['is_ignored' => true]);
}
```

### 4. Routes

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/blocked', [\App\Http\Controllers\Api\V1\BlockController::class, 'index']);
    Route::post('/profiles/{matriId}/block', [\App\Http\Controllers\Api\V1\BlockController::class, 'block']);
    Route::post('/profiles/{matriId}/unblock', [\App\Http\Controllers\Api\V1\BlockController::class, 'unblock']);
    Route::post('/profiles/{matriId}/report', [\App\Http\Controllers\Api\V1\ReportController::class, 'store']);
    Route::get('/ignored', [\App\Http\Controllers\Api\V1\IgnoredProfileController::class, 'index']);
    Route::post('/profiles/{matriId}/ignore-toggle', [\App\Http\Controllers\Api\V1\IgnoredProfileController::class, 'toggle']);
});
```

## Verification
- [ ] Block cancels pending interests + removes shortlist
- [ ] Block prevents target from appearing in search (already covered by query filters)
- [ ] Report validates reason enum
- [ ] Report dupe prevention
- [ ] Ignore toggle is idempotent

## Commit
```bash
git commit -am "phase-2a wk-04: step-10 block + report + ignore endpoints"
```

## Next step
→ [step-11-id-proof.md](step-11-id-proof.md)
