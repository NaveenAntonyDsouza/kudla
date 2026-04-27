# Step 6 — `PUT /api/v1/profile/me/{section}`

## Goal
Edit any of the 9 profile sections. Each section has its own FormRequest to validate the payload. Recomputes `profile_completion_pct` on save.

## Prerequisites
- [ ] [step-05 — view other profile](step-05-view-other-profile.md) complete

## Procedure

### 1. Create 9 FormRequests

Create FormRequests under `app/Http/Requests/Api/V1/Profile/`:

- `UpdatePrimarySectionRequest.php`
- `UpdateReligiousSectionRequest.php`
- `UpdateEducationSectionRequest.php`
- `UpdateFamilySectionRequest.php`
- `UpdateLocationSectionRequest.php`
- `UpdateContactSectionRequest.php`
- `UpdateHobbiesSectionRequest.php`
- `UpdateSocialSectionRequest.php`
- `UpdatePartnerSectionRequest.php`

Example — `UpdatePrimarySectionRequest.php`:

```php
<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\Api\V1\ApiFormRequest;

class UpdatePrimarySectionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'height' => 'sometimes|integer|between:100,250',
            'weight_kg' => 'sometimes|integer|between:30,200',
            'complexion' => 'sometimes|string|max:50',
            'body_type' => 'sometimes|string|max:50',
            'blood_group' => 'sometimes|string|max:10',
            'mother_tongue' => 'sometimes|string|max:50',
            'languages_known' => 'sometimes|array|max:10',
            'languages_known.*' => 'string|max:50',
            'physical_status' => 'sometimes|string|max:50',
            'about_me' => 'sometimes|string|max:2000',
        ];
    }
}
```

Write the rules for other sections by copying fields from `design/04-profile-api.md §4.3`.

### 2. Add `updateSection` to ProfileController

```php
/**
 * @authenticated
 * @group Profile
 * @urlParam section string required One of: primary|religious|education|family|location|contact|hobbies|social|partner
 */
public function updateSection(Request $request, string $section): JsonResponse
{
    $allowed = ['primary', 'religious', 'education', 'family', 'location', 'contact', 'hobbies', 'social', 'partner'];
    abort_unless(in_array($section, $allowed, true), 404);

    $profile = $request->user()->profile;
    abort_if(! $profile, 422);

    // Dispatch to section-specific handler
    $dto = $this->validateAndDispatch($request, $section);

    $updatedFields = array_keys($dto);

    match ($section) {
        'primary' => $profile->update($dto),
        'religious' => $profile->religiousInfo()->updateOrCreate(['profile_id' => $profile->id], $dto),
        'education' => $profile->educationDetail()->updateOrCreate(['profile_id' => $profile->id], $dto),
        'family' => $profile->familyDetail()->updateOrCreate(['profile_id' => $profile->id], $dto),
        'location' => $profile->locationInfo()->updateOrCreate(['profile_id' => $profile->id], $dto),
        'contact' => $profile->contactInfo()->updateOrCreate(['profile_id' => $profile->id], $dto),
        'hobbies' => $profile->lifestyleInfo()->updateOrCreate(['profile_id' => $profile->id], $dto),
        'social' => $profile->socialMediaLink()->updateOrCreate(['profile_id' => $profile->id], $dto),
        'partner' => $profile->partnerPreference()->updateOrCreate(['profile_id' => $profile->id], $dto),
    };

    // Recompute completion %
    $newPct = app(\App\Services\ProfileCompletionService::class)->recalculate($profile);

    return ApiResponse::ok([
        'section' => $section,
        'updated_fields' => $updatedFields,
        'profile_completion_pct' => $newPct,
    ]);
}

private function validateAndDispatch(Request $request, string $section): array
{
    $formRequestClass = match ($section) {
        'primary' => \App\Http\Requests\Api\V1\Profile\UpdatePrimarySectionRequest::class,
        'religious' => \App\Http\Requests\Api\V1\Profile\UpdateReligiousSectionRequest::class,
        'education' => \App\Http\Requests\Api\V1\Profile\UpdateEducationSectionRequest::class,
        'family' => \App\Http\Requests\Api\V1\Profile\UpdateFamilySectionRequest::class,
        'location' => \App\Http\Requests\Api\V1\Profile\UpdateLocationSectionRequest::class,
        'contact' => \App\Http\Requests\Api\V1\Profile\UpdateContactSectionRequest::class,
        'hobbies' => \App\Http\Requests\Api\V1\Profile\UpdateHobbiesSectionRequest::class,
        'social' => \App\Http\Requests\Api\V1\Profile\UpdateSocialSectionRequest::class,
        'partner' => \App\Http\Requests\Api\V1\Profile\UpdatePartnerSectionRequest::class,
    };

    $fr = app($formRequestClass);
    $fr->setContainer(app())->setRedirector(app('redirect'));
    $fr->initialize($request->query->all(), $request->request->all(), $request->attributes->all(), $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent());
    $fr->setMethod($request->method());
    $fr->validateResolved();

    return $fr->validated();
}
```

### 3. Register route

```php
Route::put('/profile/me/{section}', [\App\Http\Controllers\Api\V1\ProfileController::class, 'updateSection'])
    ->whereIn('section', ['primary','religious','education','family','location','contact','hobbies','social','partner']);
```

### 4. Pest test

```php
it('updates primary section', function () {
    $user = User::factory()->create();
    $profile = Profile::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('t')->plainTextToken;

    $response = putJson('/api/v1/profile/me/primary', [
        'about_me' => 'Updated bio',
        'weight_kg' => 70,
    ], ['Authorization' => "Bearer $token"]);

    $response->assertOk()
        ->assertJsonPath('data.section', 'primary')
        ->assertJsonStructure(['data' => ['updated_fields', 'profile_completion_pct']]);

    expect($profile->fresh()->about_me)->toBe('Updated bio');
});

it('rejects unknown section', function () {
    $user = User::factory()->create();
    $token = $user->createToken('t')->plainTextToken;

    putJson('/api/v1/profile/me/unknown', [], ['Authorization' => "Bearer $token"])
        ->assertStatus(404);
});
```

## Verification

- [ ] 9 sections each updatable via PUT
- [ ] Validation errors return envelope 422 with fields
- [ ] `profile_completion_pct` increases when filling empty fields
- [ ] Unknown section returns 404

## Commit

```bash
git add app/Http/Requests/Api/V1/Profile/ app/Http/Controllers/Api/V1/ProfileController.php routes/api.php tests/Feature/Api/V1/
git commit -m "phase-2a wk-03: step-06 PUT /profile/me/{section} for 9 sections"
```

## Next step
→ [step-07-photo-resource.md](step-07-photo-resource.md)
