<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\ContactInfo;
use App\Models\EducationDetail;
use App\Models\FamilyDetail;
use App\Models\LifestyleInfo;
use App\Models\LocationInfo;
use App\Models\ReligiousInfo;
use App\Models\SocialMediaLink;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\Action::make('toggleActive')
                ->label(fn() => $this->record->is_active ? 'Deactivate User' : 'Activate User')
                ->color(fn() => $this->record->is_active ? 'danger' : 'success')
                ->icon(fn() => $this->record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['is_active' => !$this->record->is_active]);
                    $this->refreshFormData(['is_active']);
                }),
        ];
    }

    /**
     * Load all related data into form fields.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $profile = $this->record;

        // User contact
        $user = $profile->user;
        $data['user_email'] = $user?->email;
        $data['user_phone'] = $user?->phone;

        // Religious info
        $rel = $profile->religiousInfo;
        $data['rel_religion'] = $rel?->religion;
        $data['rel_denomination'] = $rel?->denomination;
        $data['rel_diocese'] = $rel?->diocese;
        $data['rel_diocese_name'] = $rel?->diocese_name;
        $data['rel_parish'] = $rel?->parish_name_place;
        $data['rel_caste'] = $rel?->caste;
        $data['rel_sub_caste'] = $rel?->sub_caste;
        $data['rel_gotra'] = $rel?->gotra;
        $data['rel_nakshatra'] = $rel?->nakshatra;
        $data['rel_rashi'] = $rel?->rashi;
        $data['rel_manglik'] = $rel?->dosh;
        $data['rel_muslim_sect'] = $rel?->muslim_sect;
        $data['rel_muslim_community'] = $rel?->muslim_community;
        $data['rel_jain_sect'] = $rel?->jain_sect;
        $data['rel_time_of_birth'] = $rel?->time_of_birth;
        $data['rel_place_of_birth'] = $rel?->place_of_birth;

        // Education
        $edu = $profile->educationDetail;
        $data['edu_highest_education'] = $edu?->highest_education;
        $data['edu_education_level'] = $edu?->education_level;
        $data['edu_education_detail'] = $edu?->education_detail;
        $data['edu_college_name'] = $edu?->college_name;
        $data['edu_occupation'] = $edu?->occupation;
        $data['edu_occupation_detail'] = $edu?->occupation_detail;
        $data['edu_employment_category'] = $edu?->employment_category;
        $data['edu_employer_name'] = $edu?->employer_name;
        $data['edu_annual_income'] = $edu?->annual_income;
        $data['edu_working_country'] = $edu?->working_country;
        $data['edu_working_state'] = $edu?->working_state;
        $data['edu_working_district'] = $edu?->working_district;

        // Family
        $fam = $profile->familyDetail;
        $data['fam_father_name'] = $fam?->father_name;
        $data['fam_father_occupation'] = $fam?->father_occupation;
        $data['fam_father_house_name'] = $fam?->father_house_name;
        $data['fam_father_native_place'] = $fam?->father_native_place;
        $data['fam_mother_name'] = $fam?->mother_name;
        $data['fam_mother_occupation'] = $fam?->mother_occupation;
        $data['fam_mother_house_name'] = $fam?->mother_house_name;
        $data['fam_mother_native_place'] = $fam?->mother_native_place;
        $data['fam_family_status'] = $fam?->family_status;
        $data['fam_brothers_married'] = $fam?->brothers_married;
        $data['fam_brothers_unmarried'] = $fam?->brothers_unmarried;
        $data['fam_brothers_priest'] = $fam?->brothers_priest;
        $data['fam_sisters_married'] = $fam?->sisters_married;
        $data['fam_sisters_unmarried'] = $fam?->sisters_unmarried;
        $data['fam_sisters_nun'] = $fam?->sisters_nun;
        $data['fam_candidate_asset_details'] = $fam?->candidate_asset_details;
        $data['fam_about_family'] = $fam?->about_candidate_family;

        // Location
        $loc = $profile->locationInfo;
        $data['loc_residing_country'] = $loc?->residing_country;
        $data['loc_native_country'] = $loc?->native_country;
        $data['loc_native_state'] = $loc?->native_state;
        $data['loc_native_district'] = $loc?->native_district;
        $data['loc_residency_status'] = $loc?->residency_status;
        $data['loc_pin_zip_code'] = $loc?->pin_zip_code;

        // Contact Info
        $cont = $profile->contactInfo;
        $data['cont_whatsapp'] = $cont?->whatsapp_number;
        $data['cont_custodian_name'] = $cont?->contact_person;
        $data['cont_custodian_relation'] = $cont?->contact_relationship;
        $data['cont_preferred_call_time'] = $cont?->preferred_call_time;
        $data['cont_communication_address'] = $cont?->communication_address;
        $data['cont_pin_zip_code'] = $cont?->pincode ?? $cont?->present_pin_zip_code;
        $data['cont_reference_name'] = $cont?->reference_name;
        $data['cont_reference_mobile'] = $cont?->reference_mobile;

        // Lifestyle
        $life = $profile->lifestyleInfo;
        $data['life_diet'] = $life?->diet;
        $data['life_smoking'] = $life?->smoking;
        $data['life_drinking'] = $life?->drinking;
        $data['life_cultural_background'] = $life?->cultural_background;

        // Social Media
        $social = $profile->socialMediaLink;
        $data['social_instagram'] = $social?->instagram_url;
        $data['social_facebook'] = $social?->facebook_url;
        $data['social_linkedin'] = $social?->linkedin_url;

        return $data;
    }

    /**
     * Save all related data from form fields.
     */
    protected function afterSave(): void
    {
        $data = $this->form->getState();
        $profile = $this->record;

        // Update user email/phone
        $profile->user->update([
            'email' => $data['user_email'] ?? $profile->user->email,
            'phone' => $data['user_phone'] ?? $profile->user->phone,
        ]);

        // Update religious info
        ReligiousInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            [
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
            ]
        );

        // Update education
        EducationDetail::updateOrCreate(
            ['profile_id' => $profile->id],
            [
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
            ]
        );

        // Update family details
        FamilyDetail::updateOrCreate(
            ['profile_id' => $profile->id],
            [
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
            ]
        );

        // Update location
        LocationInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'residing_country' => $data['loc_residing_country'] ?? null,
                'native_country' => $data['loc_native_country'] ?? null,
                'native_state' => $data['loc_native_state'] ?? null,
                'native_district' => $data['loc_native_district'] ?? null,
                'residency_status' => $data['loc_residency_status'] ?? null,
                'pin_zip_code' => $data['loc_pin_zip_code'] ?? null,
            ]
        );

        // Update contact info
        ContactInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'whatsapp_number' => $data['cont_whatsapp'] ?? null,
                'contact_person' => $data['cont_custodian_name'] ?? null,
                'contact_relationship' => $data['cont_custodian_relation'] ?? null,
                'preferred_call_time' => $data['cont_preferred_call_time'] ?? null,
                'communication_address' => $data['cont_communication_address'] ?? null,
                'pincode' => $data['cont_pin_zip_code'] ?? null,
                'reference_name' => $data['cont_reference_name'] ?? null,
                'reference_mobile' => $data['cont_reference_mobile'] ?? null,
            ]
        );

        // Update lifestyle
        LifestyleInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'diet' => $data['life_diet'] ?? null,
                'smoking' => $data['life_smoking'] ?? null,
                'drinking' => $data['life_drinking'] ?? null,
                'cultural_background' => $data['life_cultural_background'] ?? null,
            ]
        );

        // Update social media
        SocialMediaLink::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'instagram_url' => $data['social_instagram'] ?? null,
                'facebook_url' => $data['social_facebook'] ?? null,
                'linkedin_url' => $data['social_linkedin'] ?? null,
            ]
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
