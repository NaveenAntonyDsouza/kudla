# Step 11 — Photo Request Endpoints

## Goal
- `POST /api/v1/profiles/{matriId}/photo-request` — send request
- `GET /api/v1/photo-requests` — list received + sent
- `POST /api/v1/photo-requests/{photoRequest}/approve` — approve (creates `PhotoAccessGrant`)
- `POST /api/v1/photo-requests/{photoRequest}/ignore` — decline silently

## Procedure

### 1. Methods on `PhotoController`

```php
/**
 * @authenticated
 * @group Photo Requests
 */
public function sendRequest(Request $request, string $matriId): JsonResponse
{
    $data = $request->validate(['message' => 'nullable|string|max:300']);
    $target = \App\Models\Profile::where('matri_id', $matriId)->firstOrFail();
    $requester = $request->user()->profile;

    abort_if($requester->id === $target->id, 400, 'Cannot request own photos');
    abort_if($requester->gender === $target->gender, 403);

    // Check for existing pending/approved request
    $existing = \App\Models\PhotoRequest::where('requester_profile_id', $requester->id)
        ->where('target_profile_id', $target->id)
        ->whereIn('status', ['pending', 'approved'])
        ->first();

    if ($existing) {
        return ApiResponse::error('ALREADY_EXISTS', 'You already have an open request.', status: 409);
    }

    $req = \App\Models\PhotoRequest::create([
        'requester_profile_id' => $requester->id,
        'target_profile_id' => $target->id,
        'message' => $data['message'] ?? null,
        'status' => 'pending',
        'expires_at' => now()->addDays(30),
    ]);

    // Fire notification
    app(\App\Services\NotificationService::class)->dispatch(
        $target->user,
        'photo_request.received',
        'New photo request',
        ($requester->full_name ?? 'Someone') . ' has requested to see your photos.',
        ['photo_request_id' => $req->id, 'requester_matri_id' => $requester->matri_id],
    );

    return ApiResponse::created([
        'request_id' => $req->id,
        'status' => 'pending',
        'expires_at' => $req->expires_at->toIso8601String(),
    ]);
}

/**
 * @authenticated
 * @group Photo Requests
 */
public function listRequests(Request $request): JsonResponse
{
    $profile = $request->user()->profile;

    $received = \App\Models\PhotoRequest::where('target_profile_id', $profile->id)
        ->with('requester.primaryPhoto')
        ->latest()
        ->get()
        ->map(fn ($r) => [
            'id' => $r->id,
            'requester' => (new \App\Http\Resources\V1\ProfileCardResource($r->requester))->resolve(),
            'message' => $r->message,
            'status' => $r->status,
            'created_at' => $r->created_at->toIso8601String(),
            'expires_at' => $r->expires_at?->toIso8601String(),
        ]);

    $sent = \App\Models\PhotoRequest::where('requester_profile_id', $profile->id)
        ->with('target.primaryPhoto')
        ->latest()
        ->get()
        ->map(fn ($r) => [
            'id' => $r->id,
            'target' => (new \App\Http\Resources\V1\ProfileCardResource($r->target))->resolve(),
            'message' => $r->message,
            'status' => $r->status,
            'created_at' => $r->created_at->toIso8601String(),
        ]);

    return ApiResponse::ok([
        'received' => $received,
        'sent' => $sent,
    ]);
}

/**
 * @authenticated
 * @group Photo Requests
 */
public function approveRequest(Request $request, \App\Models\PhotoRequest $photoRequest): JsonResponse
{
    abort_if($photoRequest->target_profile_id !== $request->user()->profile->id, 403);
    abort_if($photoRequest->status !== 'pending', 422);

    $photoRequest->update(['status' => 'approved', 'approved_at' => now()]);

    // Grant photo access
    app(\App\Services\PhotoAccessService::class)->grant(
        $request->user()->profile,
        $photoRequest->requester,
    );

    // Notify requester
    app(\App\Services\NotificationService::class)->dispatch(
        $photoRequest->requester->user,
        'photo_request.approved',
        'Photo request approved',
        ($request->user()->profile->full_name ?? 'Someone') . ' approved your photo request.',
        ['target_matri_id' => $request->user()->profile->matri_id],
    );

    return ApiResponse::ok(['approved' => true]);
}

/**
 * @authenticated
 * @group Photo Requests
 */
public function ignoreRequest(Request $request, \App\Models\PhotoRequest $photoRequest): JsonResponse
{
    abort_if($photoRequest->target_profile_id !== $request->user()->profile->id, 403);

    $photoRequest->update(['status' => 'ignored']);
    // No notification — by design

    return ApiResponse::ok(['ignored' => true]);
}
```

### 2. Routes

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/photo-requests', [\App\Http\Controllers\Api\V1\PhotoController::class, 'listRequests']);
    Route::post('/profiles/{matriId}/photo-request', [\App\Http\Controllers\Api\V1\PhotoController::class, 'sendRequest']);
    Route::post('/photo-requests/{photoRequest}/approve', [\App\Http\Controllers\Api\V1\PhotoController::class, 'approveRequest']);
    Route::post('/photo-requests/{photoRequest}/ignore', [\App\Http\Controllers\Api\V1\PhotoController::class, 'ignoreRequest']);
});
```

### 3. Test

```php
it('sends a photo request', function () {
    $m = User::factory()->create();
    $mp = Profile::factory()->create(['user_id' => $m->id, 'gender' => 'Male']);
    $fp = Profile::factory()->create(['gender' => 'Female', 'matri_id' => 'AM300001']);
    $token = $m->createToken('t')->plainTextToken;

    $response = postJson('/api/v1/profiles/AM300001/photo-request', [
        'message' => 'Hi',
    ], ['Authorization' => "Bearer $token"]);

    $response->assertCreated();
    expect(\App\Models\PhotoRequest::where('requester_profile_id', $mp->id)->exists())->toBeTrue();
});
```

## Verification

- [ ] Send creates pending request + notification to target
- [ ] Approve creates `photo_access_grants` row
- [ ] Post-approval, target's blurred photos now show unblurred to requester
- [ ] Ignore is silent (no notification)

## Commit

```bash
git add app/Http/Controllers/Api/V1/PhotoController.php routes/api.php tests/Feature/Api/V1/
git commit -m "phase-2a wk-03: step-11 photo request lifecycle endpoints"
```

## Next step
→ [step-12-search-partner-endpoint.md](step-12-search-partner-endpoint.md)
