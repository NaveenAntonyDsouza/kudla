<?php

namespace App\Services;

use App\Models\ContactInfo;
use App\Models\EducationDetail;
use App\Models\FamilyDetail;
use App\Models\LifestyleInfo;
use App\Models\LocationInfo;
use App\Models\PartnerPreference;
use App\Models\Profile;
use App\Models\SocialMediaLink;

/**
 * Persistence helper for the onboarding API surface.
 *
 * Mirrors `App\Http\Controllers\OnboardingController`'s save logic so
 * web + API write the same shape — no chance of drift between the two.
 * Each public method does the writes for ONE onboarding step. Profile
 * completion % recompute is the caller's job (controller calls
 * ProfileCompletionService::recalculate after each).
 *
 * Reference: docs/mobile-app/design/03-onboarding-api.md
 */
class OnboardingService
{
    /**
     * Step 1 — Personal + Professional + Family.
     *
     * Writes to: profiles (basic personal cols), lifestyle_infos
     * (languages_known), education_details (professional), family_details.
     *
     * @param  array<string,mixed>  $personal      `weight_kg, blood_group, mother_tongue, languages_known, about_me`
     * @param  array<string,mixed>  $professional  `education_detail, occupation_detail, employer_name`
     * @param  array<string,mixed>  $family        `father_*, mother_*, brothers_*, sisters_*, candidate_asset_details, about_candidate_family`
     */
    public function updateStep1(Profile $profile, array $personal, array $professional, array $family): void
    {
        // Personal cols on profile itself.
        $profileCols = array_intersect_key($personal, array_flip(['weight_kg', 'blood_group', 'mother_tongue', 'about_me']));
        if (! empty($profileCols)) {
            $profile->update($profileCols);
        }

        // Languages → lifestyle_infos (preserved across other lifestyle saves).
        if (array_key_exists('languages_known', $personal)) {
            LifestyleInfo::updateOrCreate(
                ['profile_id' => $profile->id],
                ['languages_known' => $personal['languages_known'] ?: []],
            );
        }

        // Professional → education_details.
        $profCols = array_intersect_key($professional, array_flip(['education_detail', 'occupation_detail', 'employer_name']));
        if (! empty($profCols)) {
            EducationDetail::updateOrCreate(
                ['profile_id' => $profile->id],
                $profCols,
            );
        }

        // Family → family_details.
        $familyCols = array_intersect_key($family, array_flip([
            'father_name', 'father_house_name', 'father_native_place', 'father_occupation',
            'mother_name', 'mother_house_name', 'mother_native_place', 'mother_occupation',
            'candidate_asset_details', 'about_candidate_family',
            'brothers_married', 'brothers_unmarried', 'brothers_priest',
            'sisters_married', 'sisters_unmarried', 'sisters_nun',
        ]));
        if (! empty($familyCols)) {
            FamilyDetail::updateOrCreate(
                ['profile_id' => $profile->id],
                $familyCols,
            );
        }
    }

    /**
     * Step 2 — Location + Contact.
     *
     * Writes to: location_infos, contact_infos.
     *
     * `residing_country === 'India'` clears NRI-specific fields
     * (residency_status, outstation_leave_date_from/to). The
     * "same-as-communication" / "same-as-present" toggles clear the
     * underlying address fields when set, matching web behaviour.
     *
     * @param  array<string,mixed>  $location  `residing_country, residency_status, outstation_leave_date_from, outstation_leave_date_to`
     * @param  array<string,mixed>  $contact   `residential_phone_number, secondary_phone, preferred_call_time, alternate_email, reference_*, present_address*, permanent_address*`
     */
    public function updateStep2(Profile $profile, array $location, array $contact): void
    {
        $isIndia = ($location['residing_country'] ?? '') === 'India';

        LocationInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'residing_country' => $location['residing_country'] ?? null,
                'residency_status' => $isIndia ? null : ($location['residency_status'] ?? null),
                'outstation_leave_date_from' => $isIndia ? null : ($location['outstation_leave_date_from'] ?? null),
                'outstation_leave_date_to' => $isIndia ? null : ($location['outstation_leave_date_to'] ?? null),
            ],
        );

        $presentSameAsComm = (bool) ($contact['present_address_same_as_comm'] ?? false);
        $permSameAsComm = (bool) ($contact['permanent_address_same_as_comm'] ?? false);
        $permSameAsPresent = (bool) ($contact['permanent_address_same_as_present'] ?? false);

        ContactInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'residential_phone_number' => $contact['residential_phone_number'] ?? null,
                'secondary_phone' => $contact['secondary_phone'] ?? null,
                'preferred_call_time' => $contact['preferred_call_time'] ?? null,
                'alternate_email' => $contact['alternate_email'] ?? null,
                'reference_name' => $contact['reference_name'] ?? null,
                'reference_relationship' => $contact['reference_relationship'] ?? null,
                'reference_mobile' => $contact['reference_mobile'] ?? null,
                'present_address_same_as_comm' => $presentSameAsComm,
                'present_address' => $presentSameAsComm ? null : ($contact['present_address'] ?? null),
                'present_pin_zip_code' => $presentSameAsComm ? null : ($contact['present_pin_zip_code'] ?? null),
                'permanent_address_same_as_comm' => $permSameAsComm,
                'permanent_address_same_as_present' => $permSameAsPresent,
                'permanent_address' => ($permSameAsComm || $permSameAsPresent) ? null : ($contact['permanent_address'] ?? null),
                'permanent_pin_zip_code' => ($permSameAsComm || $permSameAsPresent) ? null : ($contact['permanent_pin_zip_code'] ?? null),
            ],
        );
    }

    /**
     * Step 3 — Partner Preferences.
     *
     * Writes to: partner_preferences (one row per profile).
     *
     * Religion-specific fields (denomination, caste, sub_caste,
     * muslim_*, jain_sect, manglik) are only persisted when the chosen
     * religions array contains the relevant key — same gate web
     * applies, prevents stale values surviving a religion switch.
     *
     * "Any" sentinel is stripped from multi-select arrays — empty
     * preferences mean "no filter".
     *
     * @param  array<string,mixed>  $data  flat partner_preferences fields
     */
    public function updatePartnerPrefs(Profile $profile, array $data): void
    {
        // Strip "Any" sentinel from every array field.
        $arrayFields = [
            'complexion', 'body_type', 'marital_status', 'children_status',
            'physical_status', 'family_status', 'religions', 'denomination',
            'diocese', 'caste', 'sub_caste', 'muslim_sect', 'muslim_community',
            'jain_sect', 'manglik', 'mother_tongues', 'languages_known',
            'education_levels', 'educational_qualifications', 'occupations',
            'employment_status', 'da_category', 'income_range',
            'working_countries', 'native_countries',
        ];
        foreach ($arrayFields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $filtered = array_values(array_filter($data[$field], fn ($v) => $v !== 'Any'));
                $data[$field] = empty($filtered) ? null : $filtered;
            }
        }

        $religions = $data['religions'] ?? null;
        $isReligions = fn (array $needle) => is_array($religions) && ! empty(array_intersect($needle, $religions));

        PartnerPreference::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'age_from' => $data['age_from'] ?? null,
                'age_to' => $data['age_to'] ?? null,
                'height_from_cm' => $data['height_from_cm'] ?? $data['height_from'] ?? null,
                'height_to_cm' => $data['height_to_cm'] ?? $data['height_to'] ?? null,
                'complexion' => $data['complexion'] ?? null,
                'body_type' => $data['body_type'] ?? null,
                'marital_status' => $data['marital_status'] ?? null,
                'children_status' => $data['children_status'] ?? null,
                'physical_status' => $data['physical_status'] ?? null,
                'family_status' => $data['family_status'] ?? null,
                'religions' => $religions,
                'denomination' => $isReligions(['Christian']) ? ($data['denomination'] ?? null) : null,
                'diocese' => $isReligions(['Christian']) ? ($data['diocese'] ?? null) : null,
                'caste' => $isReligions(['Hindu', 'Jain']) ? ($data['caste'] ?? null) : null,
                'sub_caste' => $isReligions(['Hindu', 'Jain']) ? ($data['sub_caste'] ?? null) : null,
                'muslim_sect' => $isReligions(['Muslim']) ? ($data['muslim_sect'] ?? null) : null,
                'muslim_community' => $isReligions(['Muslim']) ? ($data['muslim_community'] ?? null) : null,
                'jain_sect' => $isReligions(['Jain']) ? ($data['jain_sect'] ?? null) : null,
                'manglik' => $isReligions(['Hindu']) ? ($data['manglik'] ?? null) : null,
                'mother_tongues' => $data['mother_tongues'] ?? null,
                'languages_known' => $data['languages_known'] ?? null,
                'education_levels' => $data['education_levels'] ?? null,
                'educational_qualifications' => $data['educational_qualifications'] ?? null,
                'occupations' => $data['occupations'] ?? null,
                'employment_status' => $data['employment_status'] ?? null,
                'da_category' => $data['da_category'] ?? null,
                'income_range' => $data['income_range'] ?? null,
                'working_countries' => $data['working_countries'] ?? null,
                'native_countries' => $data['native_countries'] ?? null,
                'about_partner' => $data['about_partner'] ?? null,
            ],
        );
    }

    /**
     * Step 4 — Lifestyle + Social.
     *
     * Writes to: lifestyle_infos (preserves languages_known from step
     * 1) + social_media_links.
     *
     * @param  array<string,mixed>  $lifestyle  `diet, drinking, smoking, cultural_background, hobbies, favorite_*, preferred_*, sports_fitness_games, favorite_cuisine`
     * @param  array<string,mixed>  $social     `facebook_url, instagram_url, linkedin_url, youtube_url, website_url`
     */
    public function updateLifestyle(Profile $profile, array $lifestyle, array $social): void
    {
        // Preserve languages_known from earlier (step-1) saves.
        $existing = LifestyleInfo::where('profile_id', $profile->id)->first();

        LifestyleInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'diet' => $lifestyle['diet'] ?? null,
                'drinking' => $lifestyle['drinking'] ?? null,
                'smoking' => $lifestyle['smoking'] ?? null,
                'cultural_background' => $lifestyle['cultural_background'] ?? null,
                'hobbies' => $lifestyle['hobbies'] ?? null,
                'favorite_music' => $lifestyle['favorite_music'] ?? null,
                'preferred_books' => $lifestyle['preferred_books'] ?? null,
                'preferred_movies' => $lifestyle['preferred_movies'] ?? null,
                'sports_fitness_games' => $lifestyle['sports_fitness_games'] ?? null,
                'favorite_cuisine' => $lifestyle['favorite_cuisine'] ?? null,
                'languages_known' => $existing?->languages_known,
            ],
        );

        SocialMediaLink::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'facebook_url' => $social['facebook_url'] ?? null,
                'instagram_url' => $social['instagram_url'] ?? null,
                'linkedin_url' => $social['linkedin_url'] ?? null,
                'youtube_url' => $social['youtube_url'] ?? null,
                'website_url' => $social['website_url'] ?? null,
            ],
        );
    }
}
