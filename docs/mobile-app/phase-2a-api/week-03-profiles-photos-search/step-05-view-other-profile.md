# Step 5 — `GET /api/v1/profiles/{matriId}` (with 7 gates)

## Goal
View another user's profile. Apply all 7 privacy gates from `ProfileAccessService`. Track a `ProfileView` for the viewer. Include match score in response.

## Prerequisites
- [ ] [step-04 — profile/me](step-04-profile-me-endpoint.md) complete
- [ ] `ProfileAccessService` from step 2

## Procedure

### 1. Add `show()` to ProfileController

```php
use App\Http\Responses\ApiResponse;
use App\Models\Profile;
use App\Services\MatchingService;
use App\Services\ProfileAccessService;

/**
 * @authenticated
 * @group Profile
 */
public function show(Request $request, string $matriId, ProfileAccessService $access, MatchingService $matches): JsonResponse
{
    $target = Profile::where('matri_id', $matriId)->first();
    abort_if(! $target, 404, 'Profile not found');

    $viewer = $request->user()->profile;
    abort_if(! $viewer, 422);

    $reason = $access->check($viewer, $target);
    if ($reason !== ProfileAccessService::REASON_OK) {
        return $this->accessError($reason);
    }

    $target->load([
        'religiousInfo', 'educationDetail', 'familyDetail',
        'locationInfo', 'contactInfo', 'lifestyleInfo',
        'partnerPreference', 'socialMediaLink', 'photoPrivacySetting',
        'profilePhotos', 'primaryPhoto',
    ]);

    // Track view (deduped to 24h per viewer-target)
    app(\App\Services\ProfileViewService::class)->track($viewer, $target);

    $canViewContact = $access->canViewContact($viewer, $target);
    $resource = new \App\Http\Resources\V1\ProfileResource(
        $target,
        includeContact: $canViewContact,
        viewer: $viewer,
    );

    return ApiResponse::ok([
        'profile' => $resource->resolve(),
        'contact' => $canViewContact ? $target->contactInfo?->toArray() : null,
        'match_score' => $matches->calculateScore($target, $viewer->partnerPreference),
        'interest_status' => $this->interestStatus($viewer, $target),
        'is_shortlisted' => \App\Models\Shortlist::where('profile_id', $viewer->id)->where('shortlisted_profile_id', $target->id)->exists(),
        'is_blocked' => \App\Models\BlockedProfile::where('blocker_profile_id', $viewer->id)->where('blocked_profile_id', $target->id)->exists(),
        'photo_request_status' => $this->photoRequestStatus($viewer, $target),
    ]);
}

private function accessError(string $reason): JsonResponse
{
    return match ($reason) {
        ProfileAccessService::REASON_SAME_GENDER => ApiResponse::error('GENDER_MISMATCH', 'Cannot view same-gender profile.', status: 403),
        ProfileAccessService::REASON_BLOCKED,
        ProfileAccessService::REASON_HIDDEN,
        ProfileAccessService::REASON_SUSPENDED => ApiResponse::error('NOT_FOUND', 'Profile not available.', status: 404),
        ProfileAccessService::REASON_VISIBILITY => ApiResponse::error('UNAUTHORIZED', 'This profile is premium-only.', status: 403),
        default => ApiResponse::error('NOT_FOUND', 'Profile not available.', status: 404),
    };
}

private function interestStatus($viewer, $target): ?string
{
    $interest = \App\Models\Interest::where(function ($q) use ($viewer, $target) {
        $q->where(['sender_profile_id' => $viewer->id, 'receiver_profile_id' => $target->id])
          ->orWhere(['sender_profile_id' => $target->id, 'receiver_profile_id' => $viewer->id]);
    })->latest()->first();

    if (! $interest) return null;
    if ($interest->status === 'accepted') return 'accepted';
    if ($interest->status === 'declined') return 'declined';
    if ($interest->status === 'expired') return 'expired';
    return $interest->sender_profile_id === $viewer->id ? 'sent' : 'received';
}

private function photoRequestStatus($viewer, $target): ?string
{
    $req = \App\Models\PhotoRequest::where('requester_profile_id', $viewer->id)
        ->where('target_profile_id', $target->id)
        ->latest()
        ->first();
    return $req?->status;
}
```

### 2. Create `ProfileViewService`

`app/Services/ProfileViewService.php`:

```php
<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\ProfileView;

class ProfileViewService
{
    /**
     * Record a view. Dedupes per (viewer, target) to once per 24h.
     */
    public function track(Profile $viewer, Profile $target): void
    {
        $existing = ProfileView::where('profile_id', $target->id)
            ->where('viewer_id', $viewer->id)
            ->where('created_at', '>', now()->subDay())
            ->first();

        if ($existing) return;

        ProfileView::create([
            'profile_id' => $target->id,
            'viewer_id' => $viewer->id,
        ]);
    }
}
```

### 3. Register route

```php
Route::get('/profiles/{matriId}', [\App\Http\Controllers\Api\V1\ProfileController::class, 'show']);
```

### 4. Pest tests

```php
it('returns target profile for opposite-gender viewer', function () {
    $male = User::factory()->create();
    $maleP = Profile::factory()->create(['user_id' => $male->id, 'gender' => 'Male']);
    $femaleP = Profile::factory()->create(['gender' => 'Female', 'matri_id' => 'AM200001']);

    $token = $male->createToken('t')->plainTextToken;
    getJson('/api/v1/profiles/AM200001', ['Authorization' => "Bearer $token"])
        ->assertOk()
        ->assertJsonStructure(['data' => ['profile', 'match_score']]);
});

it('returns 403 for same-gender view', function () {
    $m1 = User::factory()->create();
    Profile::factory()->create(['user_id' => $m1->id, 'gender' => 'Male']);
    Profile::factory()->create(['gender' => 'Male', 'matri_id' => 'AM200002']);

    $token = $m1->createToken('t')->plainTextToken;
    getJson('/api/v1/profiles/AM200002', ['Authorization' => "Bearer $token"])
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'GENDER_MISMATCH');
});

it('hides contact for non-premium viewer', function () {
    // Setup: free viewer, no interest → contact is null
    // Full test body: omitted for brevity
});
```

## Verification

- [ ] Opposite-gender view works
- [ ] Same-gender returns 403 GENDER_MISMATCH
- [ ] Blocked target returns 404
- [ ] Hidden target returns 404 unless interest exists
- [ ] Contact section hidden unless premium + accepted interest
- [ ] ProfileView row created; duplicate views within 24h deduped

## Commit

```bash
git add app/Http/Controllers/Api/V1/ProfileController.php app/Services/ProfileViewService.php routes/api.php tests/Feature/Api/V1/
git commit -m "phase-2a wk-03: step-05 GET /profiles/{matriId} with 7 privacy gates"
```

## Next step
→ [step-06-update-profile-section.md](step-06-update-profile-section.md)
