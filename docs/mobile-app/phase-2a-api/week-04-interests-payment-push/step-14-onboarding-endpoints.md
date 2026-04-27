# Step 14 — Onboarding Endpoints (4 optional steps)

## Goal
`POST /api/v1/onboarding/{step}` — 5 endpoints: step-1, step-2, partner-preferences, lifestyle, finish.

**Design ref:** [`design/03-onboarding-api.md`](../../design/03-onboarding-api.md)

## Procedure

### 1. Create `OnboardingService`

Extract logic from web `OnboardingController`:

```php
<?php

namespace App\Services;

use App\Models\Profile;

class OnboardingService
{
    public function updateStep1(Profile $profile, array $personal, array $professional, array $family): void
    {
        $profile->update(array_filter([
            'weight_kg' => $personal['weight_kg'] ?? null,
            'blood_group' => $personal['blood_group'] ?? null,
            'mother_tongue' => $personal['mother_tongue'] ?? null,
            'about_me' => $personal['about_me'] ?? null,
        ]));

        \App\Models\LifestyleInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            ['languages_known' => $personal['languages_known'] ?? []],
        );

        \App\Models\EducationDetail::updateOrCreate(
            ['profile_id' => $profile->id],
            array_filter($professional),
        );

        \App\Models\FamilyDetail::updateOrCreate(
            ['profile_id' => $profile->id],
            array_filter($family),
        );

        app(ProfileCompletionService::class)->recalculate($profile);
    }

    public function updateStep2(Profile $profile, array $location, array $contact): void { /* ... */ }
    public function updatePartnerPrefs(Profile $profile, array $data): void { /* ... */ }
    public function updateLifestyle(Profile $profile, array $lifestyle, array $social): void { /* ... */ }
}
```

### 2. `OnboardingController`

```php
public function step1(Request $request, OnboardingService $svc): JsonResponse
{
    $data = $request->validate([
        'personal' => 'required|array',
        'professional' => 'required|array',
        'family' => 'required|array',
    ]);
    $svc->updateStep1($request->user()->profile, $data['personal'], $data['professional'], $data['family']);
    return ApiResponse::ok([
        'profile_completion_pct' => $request->user()->profile->fresh()->profile_completion_pct,
        'next_step' => 'onboarding.step-2',
    ]);
}

// step2, partnerPrefs, lifestyle — same pattern
// finish — no-op, returns next_step=dashboard
```

### 3. Routes

```php
Route::middleware('auth:sanctum')->prefix('onboarding')->group(function () {
    Route::post('/step-1', [\App\Http\Controllers\Api\V1\OnboardingController::class, 'step1']);
    Route::post('/step-2', [\App\Http\Controllers\Api\V1\OnboardingController::class, 'step2']);
    Route::post('/partner-preferences', [\App\Http\Controllers\Api\V1\OnboardingController::class, 'partnerPrefs']);
    Route::post('/lifestyle', [\App\Http\Controllers\Api\V1\OnboardingController::class, 'lifestyle']);
    Route::post('/finish', [\App\Http\Controllers\Api\V1\OnboardingController::class, 'finish']);
});
```

## Verification
- [ ] Each step persists to correct tables
- [ ] `profile_completion_pct` increases after each step
- [ ] `finish` returns `next_step: dashboard` without modifying anything

## Commit
```bash
git commit -am "phase-2a wk-04: step-14 onboarding endpoints"
```

## Next step
→ [step-15-bruno-load-test.md](step-15-bruno-load-test.md)
