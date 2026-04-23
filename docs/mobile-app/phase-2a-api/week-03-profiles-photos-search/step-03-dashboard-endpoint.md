# Step 3 — `GET /api/v1/dashboard`

## Goal
Single endpoint that returns all dashboard data (CTA, stats, recommended matches, mutual matches, recent views, newly joined, discover teasers). Flutter calls on every app open.

## Prerequisites
- [ ] [step-02 — ProfileAccessService](step-02-profile-access-service.md) complete

## Procedure

### 1. Create `DashboardController`

`app/Http/Controllers/Api/V1/DashboardController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ProfileCardResource;
use App\Http\Responses\ApiResponse;
use App\Services\MatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends BaseApiController
{
    public function __construct(private MatchingService $matches) {}

    /**
     * @authenticated
     * @group Profile
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile;

        abort_if(! $profile, 422, 'Profile required');

        $data = [
            'cta' => $this->buildCta($user, $profile),
            'stats' => $this->buildStats($profile),
            'recommended_matches' => ProfileCardResource::collection(
                $this->matches->findMatches($profile)->take(10)
            )->resolve(),
            'mutual_matches' => ProfileCardResource::collection(
                $this->matches->findMutualMatches($profile)->take(10)
            )->resolve(),
            'recent_views' => ProfileCardResource::collection(
                \App\Models\ProfileView::where('profile_id', $profile->id)
                    ->with('viewer.profilePhotos')
                    ->latest()
                    ->take(10)
                    ->get()
                    ->pluck('viewer.profile')
                    ->filter()
            )->resolve(),
            'newly_joined' => ProfileCardResource::collection(
                \App\Models\Profile::where('gender', '!=', $profile->gender)
                    ->where('is_active', true)
                    ->where('is_approved', true)
                    ->latest()
                    ->take(10)
                    ->get()
            )->resolve(),
            'discover_teasers' => $this->buildDiscoverTeasers(),
        ];

        return ApiResponse::ok($data);
    }

    private function buildCta($user, $profile): array
    {
        return [
            'show_profile_completion' => $profile->profile_completion_pct < 80,
            'profile_completion_pct' => $profile->profile_completion_pct ?? 0,
            'show_photo_upload' => $profile->profilePhotos()->count() === 0,
            'show_verify_email' => ! $user->email_verified_at,
            'show_verify_phone' => ! $user->phone_verified_at,
            'show_upgrade' => ! $user->activeMembership,
        ];
    }

    private function buildStats($profile): array
    {
        return [
            'interests_received' => \App\Models\Interest::where('receiver_profile_id', $profile->id)->where('status', 'pending')->count(),
            'interests_sent' => \App\Models\Interest::where('sender_profile_id', $profile->id)->where('status', 'pending')->count(),
            'profile_views_total' => \App\Models\ProfileView::where('profile_id', $profile->id)->count(),
            'shortlisted_count' => \App\Models\Shortlist::where('profile_id', $profile->id)->count(),
            'unread_notifications' => \App\Models\Notification::where('user_id', $profile->user_id)->where('is_read', false)->count(),
        ];
    }

    private function buildDiscoverTeasers(): array
    {
        $categories = config('discover.categories', []);
        return collect($categories)->take(6)->map(fn ($cat, $key) => [
            'category' => $key,
            'label' => $cat['label'] ?? $key,
            'count' => \App\Models\Profile::where('is_active', true)->count(),  // placeholder
        ])->values()->all();
    }
}
```

### 2. Register route

In `routes/api.php` under `auth:sanctum`:

```php
Route::get('/dashboard', [\App\Http\Controllers\Api\V1\DashboardController::class, 'show']);
```

### 3. Test

```bash
TOKEN="<from-login>"
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/v1/dashboard | jq '.data | keys'
# Expect: ["cta","discover_teasers","mutual_matches","newly_joined","recent_views","recommended_matches","stats"]
```

### 4. Pest test

```php
// tests/Feature/Api/V1/DashboardTest.php
it('returns dashboard data for authenticated user', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id, 'gender' => 'Male']);
    $token = $user->createToken('test')->plainTextToken;

    $response = getJson('/api/v1/dashboard', ['Authorization' => "Bearer $token"]);

    $response->assertOk()
        ->assertJsonStructure(['data' => [
            'cta', 'stats',
            'recommended_matches', 'mutual_matches',
            'recent_views', 'newly_joined', 'discover_teasers',
        ]]);
});
```

## Verification

- [ ] Dashboard returns all 7 sections
- [ ] Profile with `profile_completion_pct < 80` has `cta.show_profile_completion=true`
- [ ] Test passes

## Commit

```bash
git add app/Http/Controllers/Api/V1/DashboardController.php routes/api.php tests/Feature/Api/V1/DashboardTest.php
git commit -m "phase-2a wk-03: step-03 GET /api/v1/dashboard"
```

## Next step
→ [step-04-profile-me-endpoint.md](step-04-profile-me-endpoint.md)
