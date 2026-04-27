# Step 4 — `GET /api/v1/profile/me`

## Goal
Return authenticated user's full profile with all 9 sections + photos. No privacy gates (user sees everything about themselves).

## Prerequisites
- [ ] [step-03 — dashboard](step-03-dashboard-endpoint.md) complete

## Procedure

### 1. Create `ProfileController`

`app/Http/Controllers/Api/V1/ProfileController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ProfileResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends BaseApiController
{
    /**
     * @authenticated
     * @group Profile
     */
    public function me(Request $request): JsonResponse
    {
        $profile = $request->user()->profile?->load([
            'religiousInfo', 'educationDetail', 'familyDetail',
            'locationInfo', 'contactInfo', 'lifestyleInfo',
            'partnerPreference', 'socialMediaLink', 'photoPrivacySetting',
            'profilePhotos', 'primaryPhoto',
        ]);

        abort_if(! $profile, 422, 'Profile required');

        return ApiResponse::ok([
            'profile' => (new ProfileResource($profile, includeContact: true, viewer: $profile))->resolve(),
        ]);
    }

    // show() and updateSection() come in steps 5-6
}
```

### 2. Register route

```php
Route::get('/profile/me', [\App\Http\Controllers\Api\V1\ProfileController::class, 'me']);
```

### 3. Test

```bash
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/v1/profile/me | jq '.data.profile.sections | keys'
# Expect: ["contact","education","family","hobbies","location","partner","primary","religious","social"]
```

### 4. Pest test

```php
it('returns own profile with all 9 sections', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('test')->plainTextToken;

    $response = getJson('/api/v1/profile/me', ['Authorization' => "Bearer $token"]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['profile' => ['matri_id', 'sections' => [
                'primary', 'religious', 'education', 'family',
                'location', 'contact', 'hobbies', 'social', 'partner',
            ], 'photos']],
        ]);
});
```

## Verification

- [ ] All 9 sections present in response
- [ ] `contact` section is populated (own profile → includeContact=true)
- [ ] Photos array structure correct
- [ ] Test passes

## Commit

```bash
git add app/Http/Controllers/Api/V1/ProfileController.php routes/api.php tests/Feature/Api/V1/ProfileMeTest.php
git commit -m "phase-2a wk-03: step-04 GET /api/v1/profile/me"
```

## Next step
→ [step-05-view-other-profile.md](step-05-view-other-profile.md)
