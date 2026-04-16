<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\ContactInfo;
use App\Models\EducationDetail;
use App\Models\FamilyDetail;
use App\Models\LifestyleInfo;
use App\Models\LocationInfo;
use App\Models\Profile;
use App\Models\ReligiousInfo;
use App\Models\SocialMediaLink;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // 1. Create User
        $user = User::create([
            'name' => $data['full_name'],
            'email' => $data['user_email'],
            'phone' => $data['user_phone'] ?? null,
            'password' => bcrypt(Str::random(12)),
            'role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // 2. Assign role
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('User');
        }

        // 3. Create Profile (matri_id auto-generates via model boot)
        $profile = Profile::create([
            'user_id' => $user->id,
            'full_name' => $data['full_name'],
            'gender' => $data['gender'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'marital_status' => $data['marital_status'] ?? 'Unmarried',
            'height' => $data['height'] ?? null,
            'weight_kg' => $data['weight_kg'] ?? null,
            'complexion' => $data['complexion'] ?? null,
            'body_type' => $data['body_type'] ?? null,
            'blood_group' => $data['blood_group'] ?? null,
            'physical_status' => $data['physical_status'] ?? 'Normal',
            'mother_tongue' => $data['mother_tongue'] ?? null,
            'about_me' => $data['about_me'] ?? null,
            'created_by' => 'admin',
            'is_approved' => $data['is_approved'] ?? true,
            'is_active' => $data['is_active'] ?? true,
            'onboarding_completed' => true,
            'onboarding_step_completed' => 5,
        ]);

        // 4. Create Religious Info
        ReligiousInfo::create([
            'profile_id' => $profile->id,
            'religion' => $data['rel_religion'] ?? null,
            'denomination' => $data['rel_denomination'] ?? null,
            'diocese' => $data['rel_diocese'] ?? null,
            'diocese_name' => $data['rel_diocese_name'] ?? null,
            'parish_name_place' => $data['rel_parish'] ?? null,
            'caste' => $data['rel_caste'] ?? null,
            'sub_caste' => $data['rel_sub_caste'] ?? null,
            'gotra' => $data['rel_gotra'] ?? null,
            'nakshatra' => $data['rel_nakshatra'] ?? null,
            'rashi' => $data['rel_rashi'] ?? null,
            'dosh' => $data['rel_manglik'] ?? null,
            'muslim_sect' => $data['rel_muslim_sect'] ?? null,
            'muslim_community' => $data['rel_muslim_community'] ?? null,
            'jain_sect' => $data['rel_jain_sect'] ?? null,
            'time_of_birth' => $data['rel_time_of_birth'] ?? null,
            'place_of_birth' => $data['rel_place_of_birth'] ?? null,
        ]);

        // 5. Create Education Detail
        EducationDetail::create([
            'profile_id' => $profile->id,
            'highest_education' => $data['edu_highest_education'] ?? null,
            'education_level' => $data['edu_education_level'] ?? null,
            'education_detail' => $data['edu_education_detail'] ?? null,
            'college_name' => $data['edu_college_name'] ?? null,
            'occupation' => $data['edu_occupation'] ?? null,
            'occupation_detail' => $data['edu_occupation_detail'] ?? null,
            'employment_category' => $data['edu_employment_category'] ?? null,
            'employer_name' => $data['edu_employer_name'] ?? null,
            'annual_income' => $data['edu_annual_income'] ?? null,
            'working_country' => $data['edu_working_country'] ?? null,
            'working_state' => $data['edu_working_state'] ?? null,
            'working_district' => $data['edu_working_district'] ?? null,
        ]);

        // 6. Create Family Detail
        FamilyDetail::create([
            'profile_id' => $profile->id,
            'father_name' => $data['fam_father_name'] ?? null,
            'father_occupation' => $data['fam_father_occupation'] ?? null,
            'father_house_name' => $data['fam_father_house_name'] ?? null,
            'father_native_place' => $data['fam_father_native_place'] ?? null,
            'mother_name' => $data['fam_mother_name'] ?? null,
            'mother_occupation' => $data['fam_mother_occupation'] ?? null,
            'mother_house_name' => $data['fam_mother_house_name'] ?? null,
            'mother_native_place' => $data['fam_mother_native_place'] ?? null,
            'family_status' => $data['fam_family_status'] ?? null,
            'brothers_married' => $data['fam_brothers_married'] ?? null,
            'brothers_unmarried' => $data['fam_brothers_unmarried'] ?? null,
            'brothers_priest' => $data['fam_brothers_priest'] ?? null,
            'sisters_married' => $data['fam_sisters_married'] ?? null,
            'sisters_unmarried' => $data['fam_sisters_unmarried'] ?? null,
            'sisters_nun' => $data['fam_sisters_nun'] ?? null,
            'candidate_asset_details' => $data['fam_candidate_asset_details'] ?? null,
            'about_candidate_family' => $data['fam_about_family'] ?? null,
        ]);

        // 7. Create Location Info
        LocationInfo::create([
            'profile_id' => $profile->id,
            'residing_country' => $data['loc_residing_country'] ?? null,
            'native_country' => $data['loc_native_country'] ?? null,
            'native_state' => $data['loc_native_state'] ?? null,
            'native_district' => $data['loc_native_district'] ?? null,
            'residency_status' => $data['loc_residency_status'] ?? 'citizen',
            'pin_zip_code' => $data['loc_pin_zip_code'] ?? null,
        ]);

        // 8. Create Contact Info
        ContactInfo::create([
            'profile_id' => $profile->id,
            'whatsapp_number' => $data['cont_whatsapp'] ?? null,
            'contact_person' => $data['cont_custodian_name'] ?? null,
            'contact_relationship' => $data['cont_custodian_relation'] ?? null,
            'preferred_call_time' => $data['cont_preferred_call_time'] ?? null,
            'communication_address' => $data['cont_communication_address'] ?? null,
            'pincode' => $data['cont_pin_zip_code'] ?? null,
            'reference_name' => $data['cont_reference_name'] ?? null,
            'reference_mobile' => $data['cont_reference_mobile'] ?? null,
        ]);

        // 9. Create Lifestyle Info
        LifestyleInfo::create([
            'profile_id' => $profile->id,
            'diet' => $data['life_diet'] ?? null,
            'smoking' => $data['life_smoking'] ?? null,
            'drinking' => $data['life_drinking'] ?? null,
            'cultural_background' => $data['life_cultural_background'] ?? null,
        ]);

        // 10. Create Social Media Links
        SocialMediaLink::create([
            'profile_id' => $profile->id,
            'instagram_url' => $data['social_instagram'] ?? null,
            'facebook_url' => $data['social_facebook'] ?? null,
            'linkedin_url' => $data['social_linkedin'] ?? null,
        ]);

        Notification::make()
            ->title('Profile created: ' . $profile->matri_id)
            ->success()
            ->send();

        return $profile;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
