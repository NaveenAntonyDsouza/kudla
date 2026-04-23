# Step 10 — `POST /api/v1/photos/privacy`

## Goal
Single endpoint to update `photo_privacy_settings` for the user.

## Procedure

### 1. Add method to `PhotoController`

```php
/**
 * @authenticated
 * @group Photos
 */
public function updatePrivacy(Request $request): JsonResponse
{
    $data = $request->validate([
        'gated_premium' => 'sometimes|boolean',
        'show_watermark' => 'sometimes|boolean',
        'blur_non_premium' => 'sometimes|boolean',
    ]);

    $profile = $request->user()->profile;

    $setting = \App\Models\PhotoPrivacySetting::updateOrCreate(
        ['profile_id' => $profile->id],
        $data,
    );

    return ApiResponse::ok(['privacy' => [
        'gated_premium' => (bool) $setting->gated_premium,
        'show_watermark' => (bool) $setting->show_watermark,
        'blur_non_premium' => (bool) $setting->blur_non_premium,
    ]]);
}
```

### 2. Route

```php
Route::post('/photos/privacy', [\App\Http\Controllers\Api\V1\PhotoController::class, 'updatePrivacy']);
```

### 3. Test

```php
it('updates photo privacy settings', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('t')->plainTextToken;

    $response = postJson('/api/v1/photos/privacy', [
        'blur_non_premium' => true,
    ], ['Authorization' => "Bearer $token"]);

    $response->assertOk()->assertJsonPath('data.privacy.blur_non_premium', true);
});
```

## Verification

- [ ] Toggles persist to `photo_privacy_settings`
- [ ] Only provided keys are updated (PATCH-like semantics)

## Commit

```bash
git add app/Http/Controllers/Api/V1/PhotoController.php routes/api.php
git commit -m "phase-2a wk-03: step-10 POST /photos/privacy"
```

## Next step
→ [step-11-photo-request-endpoints.md](step-11-photo-request-endpoints.md)
