<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Services\OnboardingService;
use App\Services\ProfileCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Optional post-registration onboarding endpoints (5 routes, all auth).
 *
 *   POST /api/v1/onboarding/step-1                Personal + Professional + Family
 *   POST /api/v1/onboarding/step-2                Location + Contact
 *   POST /api/v1/onboarding/partner-preferences   Partner prefs
 *   POST /api/v1/onboarding/lifestyle             Lifestyle + Social
 *   POST /api/v1/onboarding/finish                Skip-to-dashboard sentinel
 *
 * Each step is independently idempotent — Flutter can re-submit without
 * undoing earlier progress. The final step (lifestyle) AND finish both
 * set onboarding_completed=true so the dashboard banner stops showing.
 *
 * Request shape per the design doc uses NESTED objects (e.g. step-1 has
 * { personal: {...}, professional: {...}, family: {...} }) — the nested
 * shape mirrors how Flutter holds form state, and we flatten on this
 * side before passing to OnboardingService.
 *
 * Reference: docs/mobile-app/design/03-onboarding-api.md
 */
class OnboardingController extends BaseApiController
{
    public function __construct(
        private OnboardingService $onboarding,
        private ProfileCompletionService $completion,
    ) {}

    /* ==================================================================
     |  POST /onboarding/step-1
     | ================================================================== */

    /**
     * Onboarding step 1 — personal + professional + family extras.
     *
     * @authenticated
     *
     * @group Onboarding
     *
     * @bodyParam personal object Personal extras (weight_kg, blood_group, mother_tongue, languages_known[], about_me).
     * @bodyParam professional object Professional extras (education_detail, occupation_detail, employer_name).
     * @bodyParam family object Family extras (father_name, mother_name, brothers/sisters counts, candidate_asset_details, about_candidate_family).
     *
     * @response 200 scenario="success" {"success": true, "data": {"profile_completion_pct": 65, "next_step": "onboarding.step-2"}}
     * @response 401 scenario="invalid-token" {"success": false, "error": {"code": "UNAUTHENTICATED", "message": "..."}}
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function step1(Request $request): JsonResponse
    {
        $profile = $request->user()->profile;
        if (! $profile) {
            return $this->profileRequired();
        }

        $data = $request->validate([
            // Personal — match web's existing rules (string weight per schema column).
            'personal.weight_kg' => 'nullable|string|max:20',
            'personal.blood_group' => 'nullable|string|max:10',
            'personal.mother_tongue' => 'nullable|string|max:50',
            'personal.languages_known' => 'nullable|array',
            'personal.languages_known.*' => 'string|max:50',
            'personal.about_me' => 'nullable|string|max:5000',
            // Professional → education_details.
            'professional.education_detail' => 'nullable|string|max:200',
            'professional.occupation_detail' => 'nullable|string|max:200',
            'professional.employer_name' => 'nullable|string|max:100',
            // Family → family_details.
            'family.father_name' => 'nullable|string|max:100',
            'family.father_house_name' => 'nullable|string|max:100',
            'family.father_native_place' => 'nullable|string|max:100',
            'family.father_occupation' => 'nullable|string|max:100',
            'family.mother_name' => 'nullable|string|max:100',
            'family.mother_house_name' => 'nullable|string|max:100',
            'family.mother_native_place' => 'nullable|string|max:100',
            'family.mother_occupation' => 'nullable|string|max:100',
            'family.candidate_asset_details' => 'nullable|string|max:500',
            'family.about_candidate_family' => 'nullable|string|max:5000',
            'family.brothers_married' => 'nullable|integer|min:0',
            'family.brothers_unmarried' => 'nullable|integer|min:0',
            'family.brothers_priest' => 'nullable|integer|min:0',
            'family.sisters_married' => 'nullable|integer|min:0',
            'family.sisters_unmarried' => 'nullable|integer|min:0',
            'family.sisters_nun' => 'nullable|integer|min:0',
        ]);

        $this->onboarding->updateStep1(
            $profile,
            $data['personal'] ?? [],
            $data['professional'] ?? [],
            $data['family'] ?? [],
        );

        return ApiResponse::ok([
            'profile_completion_pct' => $this->completion->recalculate($profile),
            'next_step' => 'onboarding.step-2',
        ]);
    }

    /* ==================================================================
     |  POST /onboarding/step-2
     | ================================================================== */

    /**
     * Onboarding step 2 — location + extended contact info.
     *
     * @authenticated
     *
     * @group Onboarding
     *
     * @bodyParam location object Location extras (residing_country, residency_status, outstation_leave_date_from/to).
     * @bodyParam contact object Extended contact (residential_phone_number, secondary_phone, alternate_email, reference_*, present_address, permanent_address, …).
     *
     * @response 200 scenario="success" {"success": true, "data": {"profile_completion_pct": 78, "next_step": "onboarding.partner-preferences"}}
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function step2(Request $request): JsonResponse
    {
        $profile = $request->user()->profile;
        if (! $profile) {
            return $this->profileRequired();
        }

        $data = $request->validate([
            // Location → location_infos.
            'location.residing_country' => 'nullable|string|max:100',
            'location.residency_status' => 'nullable|string|max:50',
            'location.outstation_leave_date_from' => 'nullable|date',
            'location.outstation_leave_date_to' => 'nullable|date|after_or_equal:location.outstation_leave_date_from',
            // Contact → contact_infos.
            'contact.residential_phone_number' => 'nullable|string|max:20',
            'contact.secondary_phone' => 'nullable|string|max:15',
            'contact.preferred_call_time' => 'nullable|string|max:30',
            'contact.alternate_email' => 'nullable|email|max:150',
            'contact.reference_name' => 'nullable|string|max:100',
            'contact.reference_relationship' => 'nullable|string|max:50',
            'contact.reference_mobile' => 'nullable|string|max:15',
            'contact.present_address_same_as_comm' => 'nullable|boolean',
            'contact.present_address' => 'nullable|string|max:200',
            'contact.present_pin_zip_code' => 'nullable|string|max:10',
            'contact.permanent_address_same_as_comm' => 'nullable|boolean',
            'contact.permanent_address_same_as_present' => 'nullable|boolean',
            'contact.permanent_address' => 'nullable|string|max:200',
            'contact.permanent_pin_zip_code' => 'nullable|string|max:10',
        ]);

        $this->onboarding->updateStep2(
            $profile,
            $data['location'] ?? [],
            $data['contact'] ?? [],
        );

        return ApiResponse::ok([
            'profile_completion_pct' => $this->completion->recalculate($profile),
            'next_step' => 'onboarding.partner-preferences',
        ]);
    }

    /* ==================================================================
     |  POST /onboarding/partner-preferences
     | ================================================================== */

    /**
     * Onboarding partner-preferences — multi-select arrays drive the partner-search filters.
     *
     * @authenticated
     *
     * @group Onboarding
     *
     * @bodyParam age_from integer Min preferred age (18-70).
     * @bodyParam age_to integer Max preferred age (18-70). Must be >= age_from.
     * @bodyParam height_from_cm integer Min preferred height in cm (100-250).
     * @bodyParam height_to_cm integer Max preferred height in cm (100-250).
     * @bodyParam complexion string[] Preferred complexion values.
     * @bodyParam body_type string[] Preferred body types.
     * @bodyParam marital_status string[] Acceptable marital statuses.
     * @bodyParam religions string[] Acceptable religions; downstream filters (denomination, caste, etc.) apply per-religion.
     * @bodyParam education_levels string[] Preferred education levels.
     * @bodyParam occupations string[] Preferred occupations.
     * @bodyParam income_range string[] Acceptable income buckets.
     * @bodyParam working_countries string[] Preferred working countries.
     * @bodyParam native_countries string[] Preferred native countries.
     * @bodyParam about_partner string Free-text "about the partner" (max 5000 chars).
     *
     * @response 200 scenario="success" {"success": true, "data": {"profile_completion_pct": 90, "next_step": "onboarding.lifestyle"}}
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function partnerPrefs(Request $request): JsonResponse
    {
        $profile = $request->user()->profile;
        if (! $profile) {
            return $this->profileRequired();
        }

        $data = $request->validate([
            'age_from' => 'nullable|integer|min:18|max:70',
            'age_to' => 'nullable|integer|min:18|max:70|gte:age_from',
            'height_from_cm' => 'nullable|integer|min:100|max:250',
            'height_to_cm' => 'nullable|integer|min:100|max:250',
            'complexion' => 'nullable|array',
            'body_type' => 'nullable|array',
            'marital_status' => 'nullable|array',
            'children_status' => 'nullable|array',
            'physical_status' => 'nullable|array',
            'da_category' => 'nullable|array',
            'family_status' => 'nullable|array',
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
            'education_levels' => 'nullable|array',
            'educational_qualifications' => 'nullable|array',
            'occupations' => 'nullable|array',
            'employment_status' => 'nullable|array',
            'income_range' => 'nullable|array',
            'working_countries' => 'nullable|array',
            'native_countries' => 'nullable|array',
            'about_partner' => 'nullable|string|max:5000',
        ]);

        $this->onboarding->updatePartnerPrefs($profile, $data);

        return ApiResponse::ok([
            'profile_completion_pct' => $this->completion->recalculate($profile),
            'next_step' => 'onboarding.lifestyle',
        ]);
    }

    /* ==================================================================
     |  POST /onboarding/lifestyle
     | ================================================================== */

    /**
     * Final onboarding step — also flips onboarding_completed=true.
     *
     * @authenticated
     *
     * @group Onboarding
     *
     * @bodyParam lifestyle object Lifestyle (diet, drinking, smoking, cultural_background, hobbies[], favorite_music[], …).
     * @bodyParam social object Social-media URLs (facebook_url, instagram_url, linkedin_url, youtube_url, website_url).
     *
     * @response 200 scenario="success" {"success": true, "data": {"profile_completion_pct": 100, "next_step": "dashboard", "onboarding_finished": true}}
     * @response 422 scenario="invalid-url" {"success": false, "error": {"code": "VALIDATION_FAILED", "message": "...", "fields": {"social.facebook_url": ["The social.facebook url field must be a valid URL."]}}}
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function lifestyle(Request $request): JsonResponse
    {
        $profile = $request->user()->profile;
        if (! $profile) {
            return $this->profileRequired();
        }

        $data = $request->validate([
            // Lifestyle → lifestyle_infos (excluding languages_known, preserved from step-1).
            'lifestyle.diet' => 'nullable|string|max:30',
            'lifestyle.drinking' => 'nullable|string|max:20',
            'lifestyle.smoking' => 'nullable|string|max:20',
            'lifestyle.cultural_background' => 'nullable|string|max:30',
            'lifestyle.hobbies' => 'nullable|array',
            'lifestyle.favorite_music' => 'nullable|array',
            'lifestyle.preferred_books' => 'nullable|array',
            'lifestyle.preferred_movies' => 'nullable|array',
            'lifestyle.sports_fitness_games' => 'nullable|array',
            'lifestyle.favorite_cuisine' => 'nullable|array',
            // Social → social_media_links.
            'social.facebook_url' => 'nullable|url|max:200',
            'social.instagram_url' => 'nullable|url|max:200',
            'social.linkedin_url' => 'nullable|url|max:200',
            'social.youtube_url' => 'nullable|url|max:200',
            'social.website_url' => 'nullable|url|max:200',
        ]);

        $this->onboarding->updateLifestyle(
            $profile,
            $data['lifestyle'] ?? [],
            $data['social'] ?? [],
        );

        $profile->update(['onboarding_completed' => true]);

        return ApiResponse::ok([
            'profile_completion_pct' => $this->completion->recalculate($profile),
            'next_step' => 'dashboard',
            'onboarding_finished' => true,
        ]);
    }

    /* ==================================================================
     |  POST /onboarding/finish
     | ================================================================== */

    /**
     * Skip-to-dashboard sentinel — Flutter calls this from "Do this
     * later" buttons. Flips onboarding_completed=true so the dashboard
     * banner stops showing; doesn't touch any field data.
     *
     * @authenticated
     *
     * @group Onboarding
     *
     * @response 200 scenario="success" {"success": true, "data": {"profile_completion_pct": 65, "next_step": "dashboard", "onboarding_finished": true}}
     * @response 422 scenario="no-profile" {"success": false, "error": {"code": "PROFILE_REQUIRED", "message": "..."}}
     */
    public function finish(Request $request): JsonResponse
    {
        $profile = $request->user()->profile;
        if (! $profile) {
            return $this->profileRequired();
        }

        $profile->update(['onboarding_completed' => true]);

        return ApiResponse::ok([
            'profile_completion_pct' => $this->completion->recalculate($profile),
            'next_step' => 'dashboard',
            'onboarding_finished' => true,
        ]);
    }

    /* ==================================================================
     |  Helpers
     | ================================================================== */

    private function profileRequired(): JsonResponse
    {
        return ApiResponse::error(
            'PROFILE_REQUIRED',
            'Complete registration before using onboarding.',
            null,
            422,
        );
    }
}
