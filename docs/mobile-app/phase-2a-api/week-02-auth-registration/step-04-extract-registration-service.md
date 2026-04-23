# Step 4 — Extract `RegistrationService`

## Goal
Move the 5-step registration logic out of `RegisterController` into a service. Both web controller and new API controller will call it.

## Prerequisites
- [ ] [step-03 — Extract AuthService](step-03-extract-auth-service.md) complete

## Procedure

### 1. Create the service

Create `app/Services/RegistrationService.php`:

```php
<?php

namespace App\Services;

use App\Models\ContactInfo;
use App\Models\DifferentlyAbledInfo;
use App\Models\EducationDetail;
use App\Models\FamilyDetail;
use App\Models\LocationInfo;
use App\Models\Profile;
use App\Models\ReligiousInfo;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegistrationService
{
    public function __construct(private AffiliateTracker $affiliate) {}

    /**
     * Create a fresh user + profile (registration step 1).
     *
     * @param  array<string,mixed>  $data  validated payload
     * @return array{user: User, profile: Profile}
     */
    public function createFreeAccount(array $data, ?Request $request = null): array
    {
        $autoApprove = SiteSetting::getValue('auto_approve_profiles', '1') === '1';

        $user = User::create([
            'name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'user',
            'is_active' => true,
        ]);

        if ($request) {
            $this->affiliate->attributeRegistration($request, $user);
            $user->refresh();
        }

        $profile = Profile::create([
            'user_id' => $user->id,
            'full_name' => $data['full_name'],
            'gender' => $data['gender'],
            'date_of_birth' => $data['date_of_birth'],
            'onboarding_step_completed' => 1,
            'is_active' => true,
            'is_approved' => $autoApprove,
            'branch_id' => $user->branch_id,
        ]);

        return ['user' => $user, 'profile' => $profile];
    }

    /**
     * Step 2: primary details + religious info + family status.
     */
    public function updateStep2(Profile $profile, array $data): void
    {
        $profile->update([
            'height' => $data['height'],
            'complexion' => $data['complexion'],
            'body_type' => $data['body_type'],
            'physical_status' => $data['physical_status'] ?? null,
            'marital_status' => $data['marital_status'],
            'children_with_me' => $data['children_with_me'] ?? 0,
            'children_not_with_me' => $data['children_not_with_me'] ?? 0,
            'onboarding_step_completed' => 2,
        ]);

        FamilyDetail::updateOrCreate(
            ['profile_id' => $profile->id],
            ['family_status' => $data['family_status'] ?? null],
        );

        ReligiousInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'religion' => $data['religion'],
                'caste' => $data['caste'] ?? null,
                'sub_caste' => $data['sub_caste'] ?? null,
                'gotra' => $data['gotra'] ?? null,
                'nakshatra' => $data['nakshatra'] ?? null,
                'rashi' => $data['rashi'] ?? null,
                'dosh' => $data['manglik'] ?? null,
                'denomination' => $data['denomination'] ?? null,
                'diocese' => $data['diocese'] ?? null,
                'diocese_name' => $data['diocese_name'] ?? null,
                'parish_name_place' => $data['parish_name_place'] ?? null,
                'time_of_birth' => $data['time_of_birth'] ?? null,
                'place_of_birth' => $data['place_of_birth'] ?? null,
                'muslim_sect' => $data['muslim_sect'] ?? null,
                'muslim_community' => $data['muslim_community'] ?? null,
                'religious_observance' => $data['religious_observance'] ?? null,
                'jain_sect' => $data['jain_sect'] ?? null,
                'other_religion_name' => $data['other_religion_name'] ?? null,
            ],
        );

        if (($data['physical_status'] ?? '') === 'Differently Abled') {
            DifferentlyAbledInfo::updateOrCreate(
                ['profile_id' => $profile->id],
                [
                    'category' => $data['da_category'] ?? null,
                    'specify' => $data['da_category_other'] ?? null,
                    'description' => $data['da_description'] ?? null,
                ],
            );
        }
    }

    /**
     * Step 3: education & occupation.
     */
    public function updateStep3(Profile $profile, array $data): void
    {
        EducationDetail::updateOrCreate(
            ['profile_id' => $profile->id],
            $data,
        );

        $profile->update(['onboarding_step_completed' => 3]);
    }

    /**
     * Step 4: location + contact.
     */
    public function updateStep4(Profile $profile, array $data): void
    {
        LocationInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'native_country' => $data['native_country'] ?? null,
                'native_state' => $data['native_state'] ?? null,
                'native_district' => $data['native_district'] ?? null,
                'pin_zip_code' => $data['pin_zip_code'] ?? null,
            ],
        );

        ContactInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'whatsapp_number' => $data['whatsapp_number'] ?? null,
                'primary_phone' => $data['mobile_number'] ?? null,
                'contact_person' => $data['custodian_name'] ?? null,
                'contact_relationship' => $data['custodian_relation'] ?? null,
                'communication_address' => $data['communication_address'] ?? null,
                'pincode' => $data['pin_zip_code'] ?? null,
            ],
        );

        $profile->update(['onboarding_step_completed' => 4]);
    }

    /**
     * Step 5: creator info + finalization. Returns next verification step.
     *
     * @return string  'verify.email' | 'verify.phone' | 'complete'
     */
    public function finalizeStep5(Profile $profile, array $data): string
    {
        $profile->update([
            'created_by' => $data['created_by'],
            'creator_name' => $data['creator_name'] ?? null,
            'creator_contact_number' => $data['creator_contact_number'] ?? null,
            'how_did_you_hear_about_us' => $data['how_did_you_hear_about_us'] ?? null,
            'onboarding_step_completed' => 5,
        ]);

        return $this->nextVerificationStep($profile->user);
    }

    public function nextVerificationStep(User $user): string
    {
        $emailRequired = SiteSetting::getValue('email_verification_enabled', '1') === '1';
        $phoneRequired = SiteSetting::getValue('phone_verification_enabled', '0') === '1';

        if ($emailRequired && ! $user->email_verified_at) {
            return 'verify.email';
        }
        if ($phoneRequired && ! $user->phone_verified_at) {
            return 'verify.phone';
        }

        $user->profile->update(['onboarding_completed' => true]);
        return 'complete';
    }
}
```

### 2. Refactor web `RegisterController` to use `RegistrationService`

Open `app/Http/Controllers/Auth/RegisterController.php`. Each `storeStepN` method gets much shorter:

```php
public function storeStep1(RegisterStep1Request $request, RegistrationService $reg)
{
    $result = $reg->createFreeAccount($request->validated(), $request);
    Auth::login($result['user']);
    return redirect()->route('register.step2');
}

public function storeStep2(RegisterStep2Request $request, RegistrationService $reg)
{
    $reg->updateStep2(auth()->user()->profile, $request->validated());

    if ($request->hasFile('jathakam')) {
        $path = $request->file('jathakam')->store('jathakam', 'public');
        auth()->user()->profile->religiousInfo->update(['jathakam_upload_url' => $path]);
    }

    return redirect()->route('register.step3');
}

public function storeStep3(RegisterStep3Request $request, RegistrationService $reg)
{
    $reg->updateStep3(auth()->user()->profile, $request->validated());
    return redirect()->route('register.step4');
}

public function storeStep4(RegisterStep4Request $request, RegistrationService $reg)
{
    $reg->updateStep4(auth()->user()->profile, $request->validated());
    return redirect()->route('register.step5');
}

public function storeStep5(RegisterStep5Request $request, RegistrationService $reg)
{
    $next = $reg->finalizeStep5(auth()->user()->profile, $request->validated());
    return match ($next) {
        'verify.email' => redirect()->route('register.verifyemail'),
        'verify.phone' => redirect()->route('register.verify'),
        'complete' => redirect()->route('register.complete'),
    };
}
```

### 3. Pest tests

Create `tests/Unit/Services/RegistrationServiceTest.php`:

```php
<?php

use App\Models\Profile;
use App\Models\ReligiousInfo;
use App\Models\User;
use App\Services\RegistrationService;

it('creates user and profile in step 1', function () {
    $result = app(RegistrationService::class)->createFreeAccount([
        'full_name' => 'Naveen D\'Souza',
        'email' => 'naveen@example.com',
        'phone' => '9876543210',
        'password' => 'password',
        'gender' => 'Male',
        'date_of_birth' => '1995-04-12',
    ]);

    expect($result['user'])->toBeInstanceOf(User::class);
    expect($result['profile'])->toBeInstanceOf(Profile::class);
    expect($result['profile']->onboarding_step_completed)->toBe(1);
    expect($result['profile']->gender)->toBe('Male');
});

it('step 2 creates religious info', function () {
    $profile = Profile::factory()->create(['onboarding_step_completed' => 1]);

    app(RegistrationService::class)->updateStep2($profile, [
        'height' => 170,
        'complexion' => 'Wheatish',
        'body_type' => 'Average',
        'physical_status' => 'Normal',
        'marital_status' => 'Never Married',
        'family_status' => 'Middle Class',
        'religion' => 'Hindu',
        'caste' => 'Brahmin',
    ]);

    $profile->refresh();
    expect($profile->onboarding_step_completed)->toBe(2);
    expect($profile->height)->toBe(170);

    $religious = ReligiousInfo::where('profile_id', $profile->id)->first();
    expect($religious->religion)->toBe('Hindu');
    expect($religious->caste)->toBe('Brahmin');
});

it('step 5 returns verify.email when email verification required', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    $profile = Profile::factory()->create(['user_id' => $user->id, 'onboarding_step_completed' => 4]);

    \App\Models\SiteSetting::set('email_verification_enabled', '1');
    \App\Models\SiteSetting::set('phone_verification_enabled', '0');

    $next = app(RegistrationService::class)->finalizeStep5($profile, [
        'created_by' => 'Self',
    ]);

    expect($next)->toBe('verify.email');
});

it('step 5 returns complete when no verification needed', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $profile = Profile::factory()->create(['user_id' => $user->id, 'onboarding_step_completed' => 4]);

    \App\Models\SiteSetting::set('email_verification_enabled', '0');
    \App\Models\SiteSetting::set('phone_verification_enabled', '0');

    $next = app(RegistrationService::class)->finalizeStep5($profile, [
        'created_by' => 'Self',
    ]);

    expect($next)->toBe('complete');
    expect($profile->fresh()->onboarding_completed)->toBeTrue();
});
```

### 4. Run tests + web smoke

```bash
./vendor/bin/pest --filter=RegistrationService
```

Web smoke: complete a fresh registration via web `/register` flow — should work end-to-end.

## Verification

- [ ] 4 Pest tests pass
- [ ] Web registration still works end-to-end
- [ ] `RegisterController` methods are now 3–5 lines each (just call service + redirect)

## Common issues

| Issue | Fix |
|-------|-----|
| Factory errors | `Profile::factory()` may not exist. Create one in `database/factories/` if missing |
| Step 5 test fails with "undefined method set" | Check `SiteSetting::set()` vs `SiteSetting::updateOrCreate` — use actual API |
| Web step 2 loses jathakam upload | The file upload happens in the controller after the service call — double-check the Controller change |

## Commit

```bash
git add app/Services/RegistrationService.php app/Http/Controllers/Auth/RegisterController.php tests/Unit/Services/RegistrationServiceTest.php
git commit -m "phase-2a wk-02: step-04 extract RegistrationService"
```

## Next step
→ [step-05-api-form-request-pattern.md](step-05-api-form-request-pattern.md)
