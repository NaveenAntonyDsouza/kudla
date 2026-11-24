<?php

namespace App\Services;

use App\Models\ContactInfo;
use App\Models\DifferentlyAbledInfo;
use App\Models\EducationDetail;
use App\Models\FamilyDetail;
use App\Models\LifestyleInfo;
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
            // the app sends accept_terms once its sign-up screen has the tick box
            'terms_accepted_at' => ! empty($data['accept_terms']) ? now() : null,
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
            // This service is the native-app registration path (only the
            // /api/v1 AuthController + RegistrationController call it).
            'registration_source' => \App\Support\DeviceDetector::APP,
        ]);

        return ['user' => $user, 'profile' => $profile];
    }

    /**
     * Step 2: physical + marital + languages + religion: the same fields the website's step 2 saves.
     * (24 Nov 2026: this had drifted from the website. complexion / body_type had moved to onboarding,
     * so every app sign-up failed here, and mother tongue, weight and languages were never saved.)
     * Complexion, body type, physical status and family status belong to onboarding step 1: still
     * taken here when an app sends them, but never wiped when it does not.
     * Writes: profiles, lifestyle_infos, religious_infos, (optional) family_details, differently_abled_infos.
     */
    public function updateStep2(Profile $profile, array $data): void
    {
        $profile->update(array_merge([
            'height' => $data['height'],
            'weight_kg' => $data['weight_kg'] ?? null,
            'marital_status' => $data['marital_status'],
            'children_with_me' => $data['children_with_me'] ?? 0,
            'children_not_with_me' => $data['children_not_with_me'] ?? 0,
            'mother_tongue' => $data['mother_tongue'],
            'onboarding_step_completed' => max(2, (int) $profile->onboarding_step_completed),
        ], array_intersect_key($data, array_flip(['complexion', 'body_type', 'physical_status']))));

        LifestyleInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            ['languages_known' => $data['languages_known'] ?? []],
        );

        if (array_key_exists('family_status', $data)) {
            FamilyDetail::updateOrCreate(
                ['profile_id' => $profile->id],
                ['family_status' => $data['family_status']],
            );
        }

        // Fields that do not belong to the chosen religion are cleared, as on the website
        $religious = ReligiousInfo::clearFieldsForReligion([
            'religion' => $data['religion'],
            'caste' => $data['caste'] ?? null,
            'other_caste_name' => $data['other_caste_name'] ?? null,
            'sub_caste' => $data['sub_caste'] ?? null,
            'denomination' => $data['denomination'] ?? null,
            'other_denomination_name' => $data['other_denomination_name'] ?? null,
            'diocese' => $data['diocese'] ?? null,
            'diocese_name' => $data['diocese_name'] ?? null,
            'parish_name_place' => $data['parish_name_place'] ?? null,
            'muslim_sect' => $data['muslim_sect'] ?? null,
            'muslim_community' => $data['muslim_community'] ?? null,
            'religious_observance' => $data['religious_observance'] ?? null,
            'jain_sect' => $data['jain_sect'] ?? null,
            'other_religion_name' => $data['other_religion_name'] ?? null,
        ], $data['religion']);
        ReligiousInfo::updateOrCreate(['profile_id' => $profile->id], $religious);

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
     * Step 4: native place, working place, phone numbers and who created the profile, as on the
     * website's step 4. (24 Nov 2026: the working country / state / district / city and the native
     * place were checked but never saved.) Custodian, address and PIN belong to onboarding step 2:
     * saved only when sent, so going back to this step does not wipe them.
     * Writes: location_infos, education_details (working place), contact_infos, profiles.
     */
    public function updateStep4(Profile $profile, array $data): void
    {
        LocationInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            array_merge([
                'native_country' => $data['native_country'] ?? null,
                'native_state' => $data['native_state'] ?? null,
                'native_district' => $data['native_district'] ?? null,
                'native_place' => $data['native_place'] ?? null,
            ], array_key_exists('pin_zip_code', $data) ? ['pin_zip_code' => $data['pin_zip_code']] : []),
        );

        // The working place lives on education_details (the row exists from step 3)
        EducationDetail::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'working_country' => $data['working_country'] ?? null,
                'working_state' => $data['working_state'] ?? null,
                'working_district' => $data['working_district'] ?? null,
                'working_city' => $data['working_city'] ?? null,
            ],
        );

        $contact = [
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
            'primary_phone' => $data['mobile_number'] ?? null,
        ];
        foreach (['custodian_name' => 'contact_person', 'custodian_relation' => 'contact_relationship',
            'communication_address' => 'communication_address', 'pin_zip_code' => 'pincode'] as $field => $column) {
            if (array_key_exists($field, $data)) {
                $contact[$column] = $data[$field];
            }
        }
        ContactInfo::updateOrCreate(['profile_id' => $profile->id], $contact);

        $profile->update([
            'created_by' => $data['created_by'] ?? $profile->created_by,
            'creator_name' => $data['creator_name'] ?? $profile->creator_name,
            'creator_contact_number' => $data['creator_contact_number'] ?? $profile->creator_contact_number,
            'how_did_you_hear_about_us' => $data['how_did_you_hear_about_us'] ?? $profile->how_did_you_hear_about_us,
            'onboarding_step_completed' => max(4, (int) $profile->onboarding_step_completed),
        ]);
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
