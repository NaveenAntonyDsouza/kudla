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

/**
 * 5-step registration orchestration for the mobile API.
 *
 * Design choice — like AuthService, this is NEW CODE for API use. The
 * existing web RegisterController is NOT refactored (it's live + works,
 * and refactoring risks subtle session/flash regressions). Both paths
 * write to the same underlying DB rows using the same models, so there's
 * no divergence concern.
 *
 * Public API:
 *   createFreeAccount(data, request?) -> ['user' => User, 'profile' => Profile]
 *   updateStep2(profile, data)
 *   updateStep3(profile, data)
 *   updateStep4(profile, data)
 *   finalizeStep5(profile, data)      -> string (next step: 'verify.email'|'verify.phone'|'complete')
 *   nextVerificationStep(user)        -> string
 *
 * Design reference:
 *   docs/mobile-app/phase-2a-api/week-02-auth-registration/step-04-extract-registration-service.md
 */
class RegistrationService
{
    public function __construct(private AffiliateTracker $affiliate) {}

    /**
     * Register Step 1: create User + Profile, attribute affiliate ref.
     *
     * @param  array<string,mixed>  $data     validated step-1 payload
     * @param  Request|null         $request  for affiliate ?ref= attribution
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

        // Affiliate attribution — safe no-op when no ?ref= cookie/param set.
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
     * Step 2: physical + marital + religious info + family status.
     * Writes: profiles, family_details, religious_infos, (optional) differently_abled_infos.
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
     * Step 3: education + profession. Writes: education_details.
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
     * Step 4: location + contact. Writes: location_infos + contact_infos.
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
     * Step 5: creator info + finalize. Returns next step:
     *   'verify.email' | 'verify.phone' | 'complete'
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

    /**
     * Determine next verification gate.
     *  email_verification_enabled site setting (default '1')
     *  phone_verification_enabled site setting (default '0' — SMS cost)
     *
     * Side effect: if neither gate applies, marks profile.onboarding_completed=true.
     */
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

        if ($user->profile && ! $user->profile->onboarding_completed) {
            $user->profile->update(['onboarding_completed' => true]);
        }

        return 'complete';
    }
}
