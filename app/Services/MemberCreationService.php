<?php

namespace App\Services;

use App\Models\ContactInfo;
use App\Models\EducationDetail;
use App\Models\FamilyDetail;
use App\Models\LifestyleInfo;
use App\Models\LocationInfo;
use App\Models\Profile;
use App\Models\ReligiousInfo;
use App\Models\SocialMediaLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * MemberCreationService — single source of truth for creating a complete Member
 * (User + Profile + 7 related-info rows) from a flat input array.
 *
 * Used by:
 *   - BulkImportExecutor (Phase 2.1C)
 *   - Future refactor of CreateUser (admin "Register on Behalf")
 *
 * Input array uses CLEAN field names (no rel_/edu_/fam_ prefixes). The service
 * routes each field to its correct related table. Unknown keys are ignored.
 *
 * Behavior:
 *   - Wraps everything in a DB transaction (rollback on any failure)
 *   - Auto-generates a 12-character temporary password
 *   - Always creates ALL 7 related rows (matches existing CreateUser pattern)
 *     so future field updates don't need to create missing relations
 *   - Auto-stamps branch_id via the BranchScopable trait if not explicit
 */
class MemberCreationService
{
    /**
     * Create a complete member.
     *
     * @param  array  $data  Flat field map (see ACCEPTED_FIELDS in docblock above class methods).
     * @param  array  $options  ['branch_id' => int|null, 'created_by_staff_id' => int|null, 'is_approved' => bool, 'mark_email_verified' => bool, 'temp_password' => string|null (otherwise auto-generated)]
     * @return array{user: User, profile: Profile, temp_password: string}
     *
     * @throws \Throwable on any DB failure (transaction rolled back)
     */
    public function create(array $data, array $options = []): array
    {
        $tempPassword = $options['temp_password'] ?? Str::random(12);
        $isApproved = $options['is_approved'] ?? true;
        $markEmailVerified = $options['mark_email_verified'] ?? true;
        $branchId = $options['branch_id'] ?? null;
        $createdByStaffId = $options['created_by_staff_id'] ?? null;

        return DB::transaction(function () use ($data, $tempPassword, $isApproved, $markEmailVerified, $branchId, $createdByStaffId) {
            // 1. User
            $user = User::create([
                'name' => $data['full_name'] ?? '',
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => bcrypt($tempPassword),
                'role' => 'user',
                'is_active' => true,
                'email_verified_at' => $markEmailVerified ? now() : null,
                'branch_id' => $branchId, // BranchScopable trait will fill from auth user if NULL & no key
            ]);

            // Spatie role assignment (if available)
            if (method_exists($user, 'assignRole')) {
                try {
                    $user->assignRole('User');
                } catch (\Throwable $e) {
                    // Role may not exist — non-fatal
                }
            }

            // 2. Profile
            $profile = Profile::create($this->profileFields($data, $user->id, $branchId, $createdByStaffId, $isApproved));

            // 3. Related rows (always created — see class docblock)
            ReligiousInfo::create($this->religiousFields($data, $profile->id));
            EducationDetail::create($this->educationFields($data, $profile->id));
            FamilyDetail::create($this->familyFields($data, $profile->id));
            LocationInfo::create($this->locationFields($data, $profile->id));
            ContactInfo::create($this->contactFields($data, $profile->id));
            LifestyleInfo::create($this->lifestyleFields($data, $profile->id));
            SocialMediaLink::create([
                'profile_id' => $profile->id,
                'instagram_url' => $data['instagram_url'] ?? null,
                'facebook_url' => $data['facebook_url'] ?? null,
                'linkedin_url' => $data['linkedin_url'] ?? null,
            ]);

            return [
                'user' => $user->refresh(),
                'profile' => $profile->refresh(),
                'temp_password' => $tempPassword,
            ];
        });
    }

    /* ------------------------------------------------------------------
     |  Per-table field extractors
     |  Each accepts the flat input array and returns the row data for that
     |  specific table. Missing keys default to null.
     | ------------------------------------------------------------------ */

    private function profileFields(array $data, int $userId, ?int $branchId, ?int $createdByStaffId, bool $isApproved): array
    {
        return [
            'user_id' => $userId,
            'full_name' => $data['full_name'] ?? '',
            'gender' => isset($data['gender']) ? strtolower(trim($data['gender'])) : null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'marital_status' => $data['marital_status'] ?? 'never_married',
            'height' => $data['height'] ?? null,
            'weight_kg' => $data['weight_kg'] ?? null,
            'complexion' => $data['complexion'] ?? null,
            'body_type' => $data['body_type'] ?? null,
            'blood_group' => $data['blood_group'] ?? null,
            'physical_status' => $data['physical_status'] ?? 'normal',
            'mother_tongue' => $data['mother_tongue'] ?? null,
            'about_me' => $data['about_me'] ?? null,
            'created_by' => $data['created_by'] ?? 'admin',
            'created_by_staff_id' => $createdByStaffId,
            'branch_id' => $branchId,
            'is_approved' => $isApproved,
            'is_active' => true,
            'onboarding_completed' => true,
            'onboarding_step_completed' => 5,
        ];
    }

    private function religiousFields(array $data, int $profileId): array
    {
        return [
            'profile_id' => $profileId,
            'religion' => $data['religion'] ?? null,
            'denomination' => $data['denomination'] ?? null,
            'diocese' => $data['diocese'] ?? null,
            'diocese_name' => $data['diocese_name'] ?? null,
            'parish_name_place' => $data['parish_name_place'] ?? null,
            'caste' => $data['caste'] ?? null,
            'sub_caste' => $data['sub_caste'] ?? null,
            'gotra' => $data['gotra'] ?? null,
            'nakshatra' => $data['nakshatra'] ?? null,
            'rashi' => $data['rashi'] ?? null,
            'dosh' => $data['manglik'] ?? $data['dosh'] ?? null,
            'muslim_sect' => $data['muslim_sect'] ?? null,
            'muslim_community' => $data['muslim_community'] ?? null,
            'jain_sect' => $data['jain_sect'] ?? null,
            'time_of_birth' => $data['time_of_birth'] ?? null,
            'place_of_birth' => $data['place_of_birth'] ?? null,
        ];
    }

    private function educationFields(array $data, int $profileId): array
    {
        return [
            'profile_id' => $profileId,
            'highest_education' => $data['highest_education'] ?? null,
            'education_level' => $data['education_level'] ?? null,
            'education_detail' => $data['education_detail'] ?? null,
            'college_name' => $data['college_name'] ?? null,
            'occupation' => $data['occupation'] ?? null,
            'occupation_detail' => $data['occupation_detail'] ?? null,
            'employment_category' => $data['employment_category'] ?? null,
            'employer_name' => $data['employer_name'] ?? null,
            'annual_income' => $data['annual_income'] ?? null,
            'working_country' => $data['working_country'] ?? null,
            'working_state' => $data['working_state'] ?? null,
            'working_district' => $data['working_district'] ?? null,
        ];
    }

    private function familyFields(array $data, int $profileId): array
    {
        return [
            'profile_id' => $profileId,
            'father_name' => $data['father_name'] ?? null,
            'father_occupation' => $data['father_occupation'] ?? null,
            'father_house_name' => $data['father_house_name'] ?? null,
            'father_native_place' => $data['father_native_place'] ?? null,
            'mother_name' => $data['mother_name'] ?? null,
            'mother_occupation' => $data['mother_occupation'] ?? null,
            'mother_house_name' => $data['mother_house_name'] ?? null,
            'mother_native_place' => $data['mother_native_place'] ?? null,
            'family_status' => $data['family_status'] ?? null,
            // Sibling counters: NOT NULL with default 0 in DB — must pass int, not null
            'num_brothers' => (int) ($data['num_brothers'] ?? 0),
            'brothers_married' => (int) ($data['brothers_married'] ?? 0),
            'brothers_unmarried' => (int) ($data['brothers_unmarried'] ?? 0),
            'brothers_priest' => (int) ($data['brothers_priest'] ?? 0),
            'num_sisters' => (int) ($data['num_sisters'] ?? 0),
            'sisters_married' => (int) ($data['sisters_married'] ?? 0),
            'sisters_unmarried' => (int) ($data['sisters_unmarried'] ?? 0),
            'sisters_nun' => (int) ($data['sisters_nun'] ?? 0),
        ];
    }

    private function locationFields(array $data, int $profileId): array
    {
        return [
            'profile_id' => $profileId,
            'residing_country' => $data['residing_country'] ?? null,
            'native_country' => $data['native_country'] ?? null,
            'native_state' => $data['native_state'] ?? $data['state'] ?? null,
            'native_district' => $data['native_district'] ?? $data['city'] ?? null,
            'residency_status' => $data['residency_status'] ?? 'citizen',
            'pin_zip_code' => $data['pin_zip_code'] ?? null,
        ];
    }

    private function contactFields(array $data, int $profileId): array
    {
        return [
            'profile_id' => $profileId,
            'whatsapp_number' => $data['whatsapp_number'] ?? $data['whatsapp'] ?? $data['phone'] ?? null,
            'contact_person' => $data['contact_person'] ?? null,
            'contact_relationship' => $data['contact_relationship'] ?? null,
            'preferred_call_time' => $data['preferred_call_time'] ?? null,
            'communication_address' => $data['communication_address'] ?? null,
            'pincode' => $data['pincode'] ?? null,
            'reference_name' => $data['reference_name'] ?? null,
            'reference_mobile' => $data['reference_mobile'] ?? null,
        ];
    }

    private function lifestyleFields(array $data, int $profileId): array
    {
        return [
            'profile_id' => $profileId,
            'diet' => $data['diet'] ?? null,
            'smoking' => $data['smoking'] ?? null,
            'drinking' => $data['drinking'] ?? null,
            'cultural_background' => $data['cultural_background'] ?? null,
        ];
    }
}
