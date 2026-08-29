<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\ContactInfo;
use App\Models\EducationDetail;
use App\Models\FamilyDetail;
use App\Models\LifestyleInfo;
use App\Models\LocationInfo;
use App\Models\PartnerPreference;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\ReligiousInfo;
use App\Models\SocialMediaLink;
use App\Models\User;
use App\Services\WatermarkService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // 1. Create User
            $user = User::create([
                'name' => $data['full_name'],
                // Email is optional; store blank as NULL so multiple no-email
                // members don't collide on the unique index (empty string would).
                'email' => filled($data['user_email'] ?? null) ? $data['user_email'] : null,
                'phone' => $data['user_phone'] ?? null,
                // Admin may set a login password; blank = auto-generated. Passed
                // RAW — the User model's 'hashed' cast bcrypts it once (pre-hashing
                // here would double-hash and lock the member out).
                'password' => filled($data['user_password'] ?? null)
                    ? $data['user_password']
                    : Str::random(12),
                'role' => 'user',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // 2. Assign role
            if (method_exists($user, 'assignRole')) {
                try {
                    $user->assignRole('User');
                } catch (\Throwable $e) {
                    // Role may not exist (RoleSeeder creates staff roles only) - non-fatal
                }
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
                'children_with_me' => $data['children_with_me'] ?? null,
                'children_not_with_me' => $data['children_not_with_me'] ?? null,
                'how_did_you_hear_about_us' => $data['how_did_you_hear_about_us'] ?? null,
                'created_by' => 'admin',
                'registration_source' => \App\Support\DeviceDetector::ADMIN,
                'is_approved' => $data['is_approved'] ?? true,
                'is_active' => $data['is_active'] ?? true,
                'onboarding_completed' => true,
                'onboarding_step_completed' => 5,
            ]);

            // 4. Create Religious Info (clear fields that don't match the religion)
            $relData = [
                'profile_id' => $profile->id,
                'religion' => $data['rel_religion'] ?? null,
                'denomination' => $data['rel_denomination'] ?? null,
                'other_denomination_name' => $data['rel_other_denomination_name'] ?? null,
                'diocese' => $data['rel_diocese'] ?? null,
                'diocese_name' => $data['rel_diocese_name'] ?? null,
                'parish_name_place' => $data['rel_parish'] ?? null,
                'caste' => $data['rel_caste'] ?? null,
                'other_caste_name' => $data['rel_other_caste_name'] ?? null,
                'sub_caste' => $data['rel_sub_caste'] ?? null,
                'gotra' => $data['rel_gotra'] ?? null,
                'nakshatra' => $data['rel_nakshatra'] ?? null,
                'rashi' => $data['rel_rashi'] ?? null,
                'dosh' => $data['rel_manglik'] ?? null,
                'muslim_sect' => $data['rel_muslim_sect'] ?? null,
                'muslim_community' => $data['rel_muslim_community'] ?? null,
                'jain_sect' => $data['rel_jain_sect'] ?? null,
                'religious_observance' => $data['rel_religious_observance'] ?? null,
                'other_religion_name' => $data['rel_other_religion_name'] ?? null,
                'time_of_birth' => $data['rel_time_of_birth'] ?? null,
                'place_of_birth' => $data['rel_place_of_birth'] ?? null,
                // Religion-agnostic — clearFieldsForReligion leaves it untouched.
                'jathakam_upload_url' => $data['rel_jathakam'] ?? null,
            ];
            $relData = ReligiousInfo::clearFieldsForReligion($relData, $data['rel_religion'] ?? null);
            ReligiousInfo::create($relData);

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
                'working_city' => $data['edu_working_city'] ?? null,
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
                'native_place' => $data['loc_native_place'] ?? null,
                'residency_status' => $data['loc_residency_status'] ?? 'citizen',
                'pin_zip_code' => $data['loc_pin_zip_code'] ?? null,
                'outstation_leave_date_from' => $data['loc_outstation_from'] ?? null,
                'outstation_leave_date_to' => $data['loc_outstation_to'] ?? null,
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
                'secondary_phone' => $data['cont_secondary_phone'] ?? null,
                'alternate_email' => $data['cont_alternate_email'] ?? null,
                'reference_relationship' => $data['cont_reference_relationship'] ?? null,
                'present_address' => $data['cont_present_address'] ?? null,
                'present_pin_zip_code' => $data['cont_present_pin_zip_code'] ?? null,
                'permanent_address' => $data['cont_permanent_address'] ?? null,
                'permanent_pin_zip_code' => $data['cont_permanent_pin_zip_code'] ?? null,
            ]);

            // 9. Create Lifestyle Info
            LifestyleInfo::create([
                'profile_id' => $profile->id,
                'diet' => $data['life_diet'] ?? null,
                'smoking' => $data['life_smoking'] ?? null,
                'drinking' => $data['life_drinking'] ?? null,
                'cultural_background' => $data['life_cultural_background'] ?? null,
                'hobbies' => $data['life_hobbies'] ?? null,
                'interests' => $data['life_interests'] ?? null,
                'languages_known' => $data['life_languages_known'] ?? null,
                'favorite_music' => $data['life_favorite_music'] ?? null,
                'preferred_books' => $data['life_preferred_books'] ?? null,
                'preferred_movies' => $data['life_preferred_movies'] ?? null,
                'sports_fitness_games' => $data['life_sports_fitness_games'] ?? null,
                'favorite_cuisine' => $data['life_favorite_cuisine'] ?? null,
            ]);

            // 10. Create Social Media Links
            SocialMediaLink::create([
                'profile_id' => $profile->id,
                'instagram_url' => $data['social_instagram'] ?? null,
                'facebook_url' => $data['social_facebook'] ?? null,
                'linkedin_url' => $data['social_linkedin'] ?? null,
                'youtube_url' => $data['social_youtube'] ?? null,
                'website_url' => $data['social_website'] ?? null,
            ]);

            // 11. Create Partner Preferences
            PartnerPreference::create([
                'profile_id' => $profile->id,
                'age_from' => $data['pp_age_from'] ?? null,
                'age_to' => $data['pp_age_to'] ?? null,
                'height_from_cm' => $data['pp_height_from'] ?? null,
                'height_to_cm' => $data['pp_height_to'] ?? null,
                'marital_status' => $data['pp_marital_status'] ?? [],
                'complexion' => $data['pp_complexion'] ?? [],
                'body_type' => $data['pp_body_type'] ?? [],
                'physical_status' => $data['pp_physical_status'] ?? [],
                'family_status' => $data['pp_family_status'] ?? [],
                'religions' => $data['pp_religions'] ?? [],
                'mother_tongues' => $data['pp_mother_tongues'] ?? [],
                'education_levels' => $data['pp_education_levels'] ?? [],
                'occupations' => $data['pp_occupations'] ?? [],
                'working_countries' => $data['pp_working_countries'] ?? [],
                'working_states' => $data['pp_working_states'] ?? [],
                'working_districts' => $data['pp_working_districts'] ?? [],
                'native_districts' => $data['pp_native_districts'] ?? [],
                'languages_known' => $data['pp_languages_known'] ?? [],
                'income_range' => $data['pp_income_range'] ?? [],
                'children_status' => $data['pp_children_status'] ?? [],
                'manglik' => $data['pp_manglik'] ?? [],
                'da_category' => $data['pp_da_category'] ?? [],
                'about_partner' => $data['pp_about_partner'] ?? null,
            ]);

            // 12. Profile photo (optional) — mirrors the Photos relation manager:
            // watermark the file, store as the primary/visible 'profile' photo.
            if (! empty($data['profile_photo'])) {
                $path = $data['profile_photo'];
                try {
                    app(WatermarkService::class)->apply($path);
                } catch (\Throwable $e) {
                    // Watermark failure must not block profile creation.
                }
                ProfilePhoto::create([
                    'profile_id' => $profile->id,
                    'photo_type' => 'profile',
                    'photo_url' => $path,
                    'thumbnail_url' => $path,
                    'is_primary' => true,
                    'is_visible' => true,
                    'display_order' => 0,
                ]);
            }

            Notification::make()
                ->title('Profile created: ' . $profile->matri_id)
                ->success()
                ->send();

            return $profile;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
