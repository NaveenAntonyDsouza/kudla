<?php

namespace App\Http\Controllers;

use App\Models\ContactInfo;
use App\Models\EducationDetail;
use App\Models\FamilyDetail;
use App\Models\LifestyleInfo;
use App\Models\LocationInfo;
use App\Models\PartnerPreference;
use App\Models\Profile;
use App\Models\SocialMediaLink;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    // ── Step 1: Additional Personal Info ─────────────────────────

    public function showStep1()
    {
        $profile = auth()->user()->profile;
        $educationDetail = $profile?->educationDetail;
        $familyDetail = $profile?->familyDetail;
        $lifestyleInfo = $profile?->lifestyleInfo;
        $completionPct = $profile?->calculateCompletion() ?? 0;

        return view('onboarding.step1', compact('profile', 'educationDetail', 'familyDetail', 'lifestyleInfo', 'completionPct'));
    }

    public function storeStep1(Request $request)
    {
        $profile = auth()->user()->profile;

        $validated = $request->validate([
            // Personal
            'weight_kg' => 'nullable|string|max:20',
            'blood_group' => 'nullable|string|max:10',
            'mother_tongue' => 'required|string|max:50',
            'languages_known' => 'nullable|array',
            'languages_known.*' => 'string|max:50',
            'about_me' => 'nullable|string|max:5000',
            // Professional (update existing)
            'education_detail' => 'nullable|string|max:200',
            'occupation_detail' => 'nullable|string|max:200',
            'employer_name' => 'nullable|string|max:100',
            // Family
            'father_name' => 'nullable|string|max:100',
            'father_house_name' => 'nullable|string|max:100',
            'father_native_place' => 'nullable|string|max:100',
            'father_occupation' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'mother_house_name' => 'nullable|string|max:100',
            'mother_native_place' => 'nullable|string|max:100',
            'mother_occupation' => 'nullable|string|max:100',
            'candidate_asset_details' => 'nullable|string|max:500',
            'about_candidate_family' => 'nullable|string|max:5000',
            // Siblings
            'brothers_married' => 'nullable|integer|min:0',
            'brothers_unmarried' => 'nullable|integer|min:0',
            'brothers_priest' => 'nullable|integer|min:0',
            'sisters_married' => 'nullable|integer|min:0',
            'sisters_unmarried' => 'nullable|integer|min:0',
            'sisters_nun' => 'nullable|integer|min:0',
        ]);

        // Update profile (personal details)
        $profile->update([
            'weight_kg' => $validated['weight_kg'] ?? null,
            'blood_group' => $validated['blood_group'] ?? null,
            'mother_tongue' => $validated['mother_tongue'] ?? null,
            'about_me' => $validated['about_me'] ?? null,
        ]);

        // Save languages known to lifestyle_info
        LifestyleInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            ['languages_known' => $validated['languages_known'] ?? []]
        );

        // Update education details (professional)
        EducationDetail::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'education_detail' => $validated['education_detail'] ?? $profile->educationDetail?->education_detail,
                'occupation_detail' => $validated['occupation_detail'] ?? $profile->educationDetail?->occupation_detail,
                'employer_name' => $validated['employer_name'] ?? $profile->educationDetail?->employer_name,
            ]
        );

        // Family details
        FamilyDetail::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'father_name' => $validated['father_name'] ?? null,
                'father_house_name' => $validated['father_house_name'] ?? null,
                'father_native_place' => $validated['father_native_place'] ?? null,
                'father_occupation' => $validated['father_occupation'] ?? null,
                'mother_name' => $validated['mother_name'] ?? null,
                'mother_house_name' => $validated['mother_house_name'] ?? null,
                'mother_native_place' => $validated['mother_native_place'] ?? null,
                'mother_occupation' => $validated['mother_occupation'] ?? null,
                'candidate_asset_details' => $validated['candidate_asset_details'] ?? null,
                'about_candidate_family' => $validated['about_candidate_family'] ?? null,
                'brothers_married' => $validated['brothers_married'] ?? 0,
                'brothers_unmarried' => $validated['brothers_unmarried'] ?? 0,
                'brothers_priest' => $validated['brothers_priest'] ?? 0,
                'sisters_married' => $validated['sisters_married'] ?? 0,
                'sisters_unmarried' => $validated['sisters_unmarried'] ?? 0,
                'sisters_nun' => $validated['sisters_nun'] ?? 0,
            ]
        );

        // Recalculate completion
        $profile->refresh();
        $profile->update(['profile_completion_pct' => $profile->calculateCompletion()]);

        return redirect()->route('onboarding.step2')->with('success', 'Additional info saved successfully!');
    }

    // ── Step 2: Location & Contact Details ───────────────────────

    public function showStep2()
    {
        $profile = auth()->user()->profile;
        $locationInfo = $profile?->locationInfo;
        $contactInfo = $profile?->contactInfo;
        $completionPct = $profile?->calculateCompletion() ?? 0;

        // Default residing country to working country if not yet set
        $defaultResidingCountry = $locationInfo?->residing_country ?? $profile?->educationDetail?->working_country ?? '';

        return view('onboarding.step2', compact('profile', 'locationInfo', 'contactInfo', 'completionPct', 'defaultResidingCountry'));
    }

    public function storeStep2(Request $request)
    {
        $profile = auth()->user()->profile;

        $validated = $request->validate([
            // Location
            'residing_country' => 'nullable|string|max:100',
            'residency_status' => 'nullable|string|max:50',
            'outstation_leave_date_from' => 'nullable|date',
            'outstation_leave_date_to' => 'nullable|date|after_or_equal:outstation_leave_date_from',
            // Contact
            'residential_phone_number' => 'nullable|string|max:20',
            'secondary_phone' => 'nullable|string|max:15',
            'preferred_call_time' => 'nullable|string|max:30',
            'alternate_email' => 'nullable|email|max:150',
            'reference_name' => 'nullable|string|max:100',
            'reference_relationship' => 'nullable|string|max:50',
            'reference_mobile' => 'nullable|string|max:15',
            // Present address
            'present_address_same_as_comm' => 'nullable|boolean',
            'present_address' => 'nullable|string|max:200',
            'present_pin_zip_code' => 'nullable|string|max:10',
            // Permanent address
            'permanent_address_same_as_comm' => 'nullable|boolean',
            'permanent_address_same_as_present' => 'nullable|boolean',
            'permanent_address' => 'nullable|string|max:200',
            'permanent_pin_zip_code' => 'nullable|string|max:10',
        ]);

        // Update location info (clear NRI fields if residing in India)
        $isIndia = ($validated['residing_country'] ?? '') === 'India';
        LocationInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'residing_country' => $validated['residing_country'] ?? null,
                'residency_status' => $isIndia ? null : ($validated['residency_status'] ?? null),
                'outstation_leave_date_from' => $isIndia ? null : ($validated['outstation_leave_date_from'] ?? null),
                'outstation_leave_date_to' => $isIndia ? null : ($validated['outstation_leave_date_to'] ?? null),
            ]
        );

        // Update contact info (clear address fields when "same as" is checked)
        $presentSameAsComm = $validated['present_address_same_as_comm'] ?? false;
        $permSameAsComm = $validated['permanent_address_same_as_comm'] ?? false;
        $permSameAsPresent = $validated['permanent_address_same_as_present'] ?? false;

        ContactInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'residential_phone_number' => $validated['residential_phone_number'] ?? null,
                'secondary_phone' => $validated['secondary_phone'] ?? null,
                'preferred_call_time' => $validated['preferred_call_time'] ?? null,
                'alternate_email' => $validated['alternate_email'] ?? null,
                'reference_name' => $validated['reference_name'] ?? null,
                'reference_relationship' => $validated['reference_relationship'] ?? null,
                'reference_mobile' => $validated['reference_mobile'] ?? null,
                'present_address_same_as_comm' => $presentSameAsComm,
                'present_address' => $presentSameAsComm ? null : ($validated['present_address'] ?? null),
                'present_pin_zip_code' => $presentSameAsComm ? null : ($validated['present_pin_zip_code'] ?? null),
                'permanent_address_same_as_comm' => $permSameAsComm,
                'permanent_address_same_as_present' => $permSameAsPresent,
                'permanent_address' => ($permSameAsComm || $permSameAsPresent) ? null : ($validated['permanent_address'] ?? null),
                'permanent_pin_zip_code' => ($permSameAsComm || $permSameAsPresent) ? null : ($validated['permanent_pin_zip_code'] ?? null),
            ]
        );

        // Recalculate completion
        $profile->refresh();
        $profile->update(['profile_completion_pct' => $profile->calculateCompletion()]);

        return redirect()->route('onboarding.preferences')->with('success', 'Profile details saved successfully!');
    }

    // ── Partner Preferences ──────────────────────────────────────

    public function showPartnerPreferences()
    {
        $profile = auth()->user()->profile;
        $pref = $profile?->partnerPreference;
        $completionPct = $profile?->calculateCompletion() ?? 0;

        // Default religion to user's own religion if no preferences saved yet
        $userReligion = $profile?->religiousInfo?->religion;
        $defaultReligions = $pref?->religions ?? ($userReligion ? [$userReligion] : []);

        return view('onboarding.partner-preferences', compact('profile', 'pref', 'completionPct', 'defaultReligions'));
    }

    public function storePartnerPreferences(Request $request)
    {
        $profile = auth()->user()->profile;

        $validated = $request->validate([
            // Primary
            'age_from' => 'nullable|integer|min:18|max:70',
            'age_to' => 'nullable|integer|min:18|max:70|gte:age_from',
            'height_from' => 'nullable|string|max:50',
            'height_to' => 'nullable|string|max:50',
            'complexion' => 'nullable|array',
            'body_type' => 'nullable|array',
            'marital_status' => 'nullable|array',
            'children_status' => 'nullable|array',
            'physical_status' => 'nullable|array',
            'da_category' => 'nullable|array',
            'family_status' => 'nullable|array',
            // Religious
            'religions' => 'nullable|array',
            'denomination' => 'nullable|array',
            'diocese' => 'nullable|array',
            'caste' => 'nullable|array',
            'sub_caste' => 'nullable|array',
            'muslim_sect' => 'nullable|array',
            'muslim_community' => 'nullable|array',
            'jain_sect' => 'nullable|array',
            'manglik' => 'nullable|array',
            'mother_tongues' => 'nullable|array',
            'languages_known' => 'nullable|array',
            // Professional
            'education_levels' => 'nullable|array',
            'educational_qualifications' => 'nullable|array',
            'occupations' => 'nullable|array',
            'employment_status' => 'nullable|array',
            'income_range' => 'nullable|array',
            'working_countries' => 'nullable|array',
            // Location
            'native_countries' => 'nullable|array',
            'about_partner' => 'nullable|string|max:5000',
        ]);

        // Strip "Any" from multi-select arrays — store only actual values
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
            if (isset($validated[$field]) && is_array($validated[$field])) {
                $filtered = array_values(array_filter($validated[$field], fn($v) => $v !== 'Any'));
                $validated[$field] = empty($filtered) ? null : $filtered;
            }
        }

        PartnerPreference::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'age_from' => $validated['age_from'] ?? null,
                'age_to' => $validated['age_to'] ?? null,
                'height_from_cm' => $validated['height_from'] ?? null,
                'height_to_cm' => $validated['height_to'] ?? null,
                'complexion' => $validated['complexion'] ?? null,
                'body_type' => $validated['body_type'] ?? null,
                'marital_status' => $validated['marital_status'] ?? null,
                'children_status' => $validated['children_status'] ?? null,
                'physical_status' => $validated['physical_status'] ?? null,
                'family_status' => $validated['family_status'] ?? null,
                'religions' => $religions = $validated['religions'] ?? null,
                'denomination' => (is_array($religions) && in_array('Christian', $religions)) ? ($validated['denomination'] ?? null) : null,
                'diocese' => (is_array($religions) && in_array('Christian', $religions)) ? ($validated['diocese'] ?? null) : null,
                'caste' => (is_array($religions) && (in_array('Hindu', $religions) || in_array('Jain', $religions))) ? ($validated['caste'] ?? null) : null,
                'sub_caste' => (is_array($religions) && (in_array('Hindu', $religions) || in_array('Jain', $religions))) ? ($validated['sub_caste'] ?? null) : null,
                'muslim_sect' => (is_array($religions) && in_array('Muslim', $religions)) ? ($validated['muslim_sect'] ?? null) : null,
                'muslim_community' => (is_array($religions) && in_array('Muslim', $religions)) ? ($validated['muslim_community'] ?? null) : null,
                'jain_sect' => (is_array($religions) && in_array('Jain', $religions)) ? ($validated['jain_sect'] ?? null) : null,
                'manglik' => (is_array($religions) && in_array('Hindu', $religions)) ? ($validated['manglik'] ?? null) : null,
                'mother_tongues' => $validated['mother_tongues'] ?? null,
                'languages_known' => $validated['languages_known'] ?? null,
                'education_levels' => $validated['education_levels'] ?? null,
                'educational_qualifications' => $validated['educational_qualifications'] ?? null,
                'occupations' => $validated['occupations'] ?? null,
                'employment_status' => $validated['employment_status'] ?? null,
                'da_category' => $validated['da_category'] ?? null,
                'income_range' => $validated['income_range'] ?? null,
                'working_countries' => $validated['working_countries'] ?? null,
                'native_countries' => $validated['native_countries'] ?? null,
                'about_partner' => $validated['about_partner'] ?? null,
            ]
        );

        // Recalculate completion & track step
        $profile->refresh();
        $profile->update(['profile_completion_pct' => $profile->calculateCompletion()]);

        return redirect()->route('onboarding.lifestyle')->with('success', 'Partner preferences saved successfully!');
    }

    // ── Lifestyle & Social Media ─────────────────────────────────

    public function showLifestyle()
    {
        $profile = auth()->user()->profile;
        $lifestyle = $profile?->lifestyleInfo;
        $socialMedia = $profile?->socialMediaLink;
        $completionPct = $profile?->calculateCompletion() ?? 0;

        return view('onboarding.lifestyle', compact('profile', 'lifestyle', 'socialMedia', 'completionPct'));
    }

    public function storeLifestyle(Request $request)
    {
        $profile = auth()->user()->profile;

        $validated = $request->validate([
            // Lifestyle
            'diet' => 'nullable|string|max:30',
            'drinking' => 'nullable|string|max:20',
            'smoking' => 'nullable|string|max:20',
            'cultural_background' => 'nullable|string|max:30',
            // Hobbies (multi-select)
            'hobbies' => 'nullable|array',
            'favorite_music' => 'nullable|array',
            'preferred_books' => 'nullable|array',
            'preferred_movies' => 'nullable|array',
            'sports_fitness_games' => 'nullable|array',
            'favorite_cuisine' => 'nullable|array',
            // Social media
            'facebook_url' => 'nullable|url|max:200',
            'instagram_url' => 'nullable|url|max:200',
            'linkedin_url' => 'nullable|url|max:200',
            'youtube_url' => 'nullable|url|max:200',
            'website_url' => 'nullable|url|max:200',
        ]);

        // Save lifestyle info (preserve languages_known from earlier)
        $existing = $profile->lifestyleInfo;
        LifestyleInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'diet' => $validated['diet'] ?? null,
                'drinking' => $validated['drinking'] ?? null,
                'smoking' => $validated['smoking'] ?? null,
                'cultural_background' => $validated['cultural_background'] ?? null,
                'hobbies' => $validated['hobbies'] ?? null,
                'favorite_music' => $validated['favorite_music'] ?? null,
                'preferred_books' => $validated['preferred_books'] ?? null,
                'preferred_movies' => $validated['preferred_movies'] ?? null,
                'sports_fitness_games' => $validated['sports_fitness_games'] ?? null,
                'favorite_cuisine' => $validated['favorite_cuisine'] ?? null,
                'languages_known' => $existing?->languages_known,
            ]
        );

        // Save social media links
        SocialMediaLink::updateOrCreate(
            ['profile_id' => $profile->id],
            [
                'facebook_url' => $validated['facebook_url'] ?? null,
                'instagram_url' => $validated['instagram_url'] ?? null,
                'linkedin_url' => $validated['linkedin_url'] ?? null,
                'youtube_url' => $validated['youtube_url'] ?? null,
                'website_url' => $validated['website_url'] ?? null,
            ]
        );

        // Recalculate completion & mark onboarding done
        $profile->refresh();
        $profile->update([
            'profile_completion_pct' => $profile->calculateCompletion(),
            'onboarding_completed' => true,
        ]);

        return redirect()->route('dashboard')->with('success', 'Lifestyle & social media saved successfully!');
    }

    // ── Finish Onboarding (skip remaining steps) ────────────────

    public function finishOnboarding()
    {
        $profile = auth()->user()->profile;
        if ($profile) {
            $profile->update([
                'onboarding_completed' => true,
                'profile_completion_pct' => $profile->calculateCompletion(),
            ]);
        }

        return redirect()->route('dashboard');
    }
}
