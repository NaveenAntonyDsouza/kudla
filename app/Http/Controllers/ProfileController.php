<?php

namespace App\Http\Controllers;

use App\Models\ContactInfo;
use App\Models\DifferentlyAbledInfo;
use App\Models\EducationDetail;
use App\Models\FamilyDetail;
use App\Models\LifestyleInfo;
use App\Models\LocationInfo;
use App\Models\PartnerPreference;
use App\Models\Profile;
use App\Models\ReligiousInfo;
use App\Models\SocialMediaLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        $profile = $user->profile;
        $completionPct = $profile->calculateCompletion();

        // Eager load all relationships
        $profile->load([
            'religiousInfo', 'educationDetail', 'familyDetail',
            'locationInfo', 'contactInfo', 'lifestyleInfo',
            'socialMediaLink', 'partnerPreference', 'primaryPhoto',
            'differentlyAbledInfo',
        ]);

        // Backfill residing_country from working_country if not set
        if ($profile->locationInfo && ! $profile->locationInfo->residing_country && $profile->educationDetail?->working_country) {
            $profile->locationInfo->update(['residing_country' => $profile->educationDetail->working_country]);
        }

        // Determine which section to open (from query or default)
        $openSection = request('section', 'primary');

        return view('profile.show', compact('user', 'profile', 'completionPct', 'openSection'));
    }

    public function preview()
    {
        $user = auth()->user();
        $profile = $user->profile;

        $profile->load([
            'religiousInfo', 'educationDetail', 'familyDetail',
            'locationInfo', 'contactInfo', 'lifestyleInfo',
            'socialMediaLink', 'partnerPreference', 'primaryPhoto',
            'profilePhotos', 'differentlyAbledInfo', 'photoPrivacySetting',
        ]);

        $activeTab = request('tab', 'personal');
        $isOwn = true;

        return view('profile.preview', compact('user', 'profile', 'activeTab', 'isOwn'));
    }

    public function printProfile(Profile $profile)
    {
        $isOwn = auth()->id() === $profile->user_id;

        if (!$isOwn && auth()->user()->profile->gender === $profile->gender) {
            abort(403, 'You cannot view this profile.');
        }

        $profile->load([
            'religiousInfo', 'educationDetail', 'familyDetail',
            'locationInfo', 'contactInfo', 'lifestyleInfo',
            'partnerPreference', 'primaryPhoto',
        ]);

        $user = $profile->user;
        $siteName = \App\Models\SiteSetting::getValue('site_name', 'Anugraha Matrimony');

        return view('profile.print', compact('profile', 'user', 'isOwn', 'siteName'));
    }

    public function viewProfile(Profile $profile)
    {
        $user = $profile->user;
        $isOwn = auth()->id() === $user->id;

        // Block same-gender profile viewing (except own profile)
        if (! $isOwn && auth()->user()->profile->gender === $profile->gender) {
            abort(403, 'You cannot view this profile.');
        }

        $profile->load([
            'religiousInfo', 'educationDetail', 'familyDetail',
            'locationInfo', 'contactInfo', 'lifestyleInfo',
            'socialMediaLink', 'partnerPreference', 'primaryPhoto',
            'profilePhotos', 'differentlyAbledInfo', 'photoPrivacySetting',
        ]);

        $activeTab = request('tab', 'personal');

        // Track profile view
        if (! $isOwn) {
            ProfileViewController::track(auth()->user()->profile->id, $profile->id);
        }

        // Match score breakdown
        $matchResult = null;
        if (! $isOwn) {
            $matchResult = app(\App\Services\MatchingService::class)
                ->getMatchBreakdown(auth()->user()->profile, $profile);
        }

        return view('profile.preview', compact('user', 'profile', 'activeTab', 'isOwn', 'matchResult'));
    }

    public function update(Request $request, string $section)
    {
        $profile = auth()->user()->profile;

        $allowed = ['primary', 'religious', 'education', 'family', 'location', 'contact', 'hobbies', 'social', 'partner'];
        if (! in_array($section, $allowed)) {
            abort(404, 'Unknown profile section.');
        }
        $method = 'update' . ucfirst($section);

        $this->$method($request, $profile);

        // Recalculate completion
        $profile->refresh();
        $profile->update(['profile_completion_pct' => $profile->calculateCompletion()]);

        return redirect()->route('profile.show', ['section' => $section])
            ->with('success', 'Profile updated successfully!');
    }

    // ── Section Update Methods ──────────────────────────────────

    private function updatePrimary(Request $request, $profile): void
    {
        $validated = $request->validate([
            'weight_kg' => 'nullable|string|max:20',
            'blood_group' => 'nullable|string|max:10',
            'mother_tongue' => 'nullable|string|max:50',
            'languages_known' => 'nullable|array',
            'complexion' => 'nullable|string|max:30',
            'body_type' => 'nullable|string|max:30',
            'about_me' => 'nullable|string|max:5000',
        ]);

        $profile->update([
            'weight_kg' => $validated['weight_kg'] ?? null,
            'blood_group' => $validated['blood_group'] ?? null,
            'mother_tongue' => $validated['mother_tongue'] ?? null,
            'complexion' => $validated['complexion'] ?? null,
            'body_type' => $validated['body_type'] ?? null,
            'about_me' => $validated['about_me'] ?? null,
        ]);

        LifestyleInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            ['languages_known' => $validated['languages_known'] ?? []]
        );
    }

    private function updateReligious(Request $request, $profile): void
    {
        $validated = $request->validate([
            'religion' => 'required|string|max:50',
            'caste' => 'nullable|string|max:50',
            'sub_caste' => 'nullable|string|max:50',
            'gotra' => 'nullable|string|max:50',
            'nakshatra' => 'nullable|string|max:50',
            'rashi' => 'nullable|string|max:50',
            'manglik' => 'nullable|string|max:20',
            'denomination' => 'nullable|string|max:50',
            'diocese' => 'nullable|string|max:100',
            'diocese_name' => 'nullable|string|max:100',
            'parish_name_place' => 'nullable|string|max:200',
            'time_of_birth' => 'nullable|string|max:20',
            'place_of_birth' => 'nullable|string|max:100',
            'muslim_sect' => 'nullable|string|max:50',
            'muslim_community' => 'nullable|string|max:50',
            'religious_observance' => 'nullable|string|max:50',
            'jain_sect' => 'nullable|string|max:50',
            'other_religion_name' => 'nullable|string|max:50',
            'jathakam' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $data = collect($validated)->except('jathakam', 'manglik')->toArray();
        $data['dosh'] = $validated['manglik'] ?? null;

        ReligiousInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            $data
        );

        if ($request->hasFile('jathakam')) {
            $path = $request->file('jathakam')->store('jathakam', 'public');
            $profile->religiousInfo->update(['jathakam_upload_url' => $path]);
        }
    }

    private function updateEducation(Request $request, $profile): void
    {
        $validated = $request->validate([
            'highest_education' => 'nullable|string|max:100',
            'education_detail' => 'nullable|string|max:200',
            'college_name' => 'nullable|string|max:100',
            'occupation' => 'nullable|string|max:100',
            'occupation_detail' => 'nullable|string|max:200',
            'employer_name' => 'nullable|string|max:100',
            'annual_income' => 'nullable|string|max:50',
            'working_country' => 'nullable|string|max:100',
            'working_state' => 'nullable|string|max:100',
            'working_district' => 'nullable|string|max:100',
        ]);

        EducationDetail::updateOrCreate(
            ['profile_id' => $profile->id],
            $validated
        );
    }

    private function updateFamily(Request $request, $profile): void
    {
        $validated = $request->validate([
            'father_name' => 'nullable|string|max:100',
            'father_house_name' => 'nullable|string|max:100',
            'father_native_place' => 'nullable|string|max:100',
            'father_occupation' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'mother_house_name' => 'nullable|string|max:100',
            'mother_native_place' => 'nullable|string|max:100',
            'mother_occupation' => 'nullable|string|max:100',
            'family_status' => 'nullable|string|max:50',
            'brothers_married' => 'nullable|integer|min:0',
            'brothers_unmarried' => 'nullable|integer|min:0',
            'brothers_priest' => 'nullable|integer|min:0',
            'sisters_married' => 'nullable|integer|min:0',
            'sisters_unmarried' => 'nullable|integer|min:0',
            'sisters_nun' => 'nullable|integer|min:0',
            'candidate_asset_details' => 'nullable|string|max:500',
            'about_candidate_family' => 'nullable|string|max:5000',
        ]);

        FamilyDetail::updateOrCreate(
            ['profile_id' => $profile->id],
            $validated
        );
    }

    private function updateLocation(Request $request, $profile): void
    {
        $validated = $request->validate([
            'native_country' => 'nullable|string|max:100',
            'native_state' => 'nullable|string|max:100',
            'native_district' => 'nullable|string|max:100',
            'residing_country' => 'nullable|string|max:100',
            'residency_status' => 'nullable|string|max:50',
            'pin_zip_code' => 'nullable|string|max:10',
            'outstation_leave_date_from' => 'nullable|date',
            'outstation_leave_date_to' => 'nullable|date|after_or_equal:outstation_leave_date_from',
        ]);

        LocationInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            $validated
        );
    }

    private function updateContact(Request $request, $profile): void
    {
        $validated = $request->validate([
            'whatsapp_number' => 'nullable|string|max:15',
            'secondary_phone' => 'nullable|string|max:15',
            'residential_phone_number' => 'nullable|string|max:20',
            'preferred_call_time' => 'nullable|string|max:30',
            'alternate_email' => 'nullable|email|max:150',
            'reference_name' => 'nullable|string|max:100',
            'reference_relationship' => 'nullable|string|max:50',
            'reference_mobile' => 'nullable|string|max:15',
            'communication_address' => 'nullable|string|max:200',
            'present_address' => 'nullable|string|max:200',
            'present_pin_zip_code' => 'nullable|string|max:10',
            'permanent_address' => 'nullable|string|max:200',
            'permanent_pin_zip_code' => 'nullable|string|max:10',
        ]);

        ContactInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            $validated
        );
    }

    private function updateHobbies(Request $request, $profile): void
    {
        $validated = $request->validate([
            'diet' => 'nullable|string|max:30',
            'drinking' => 'nullable|string|max:20',
            'smoking' => 'nullable|string|max:20',
            'cultural_background' => 'nullable|string|max:30',
            'hobbies' => 'nullable|array',
            'favorite_music' => 'nullable|array',
            'preferred_books' => 'nullable|array',
            'preferred_movies' => 'nullable|array',
            'sports_fitness_games' => 'nullable|array',
            'favorite_cuisine' => 'nullable|array',
        ]);

        // Ensure deselected arrays are saved as empty (not left unchanged)
        $arrayFields = ['hobbies', 'favorite_music', 'preferred_books', 'preferred_movies', 'sports_fitness_games', 'favorite_cuisine'];
        foreach ($arrayFields as $field) {
            if (! isset($validated[$field])) {
                $validated[$field] = null;
            }
        }

        $existing = $profile->lifestyleInfo;
        LifestyleInfo::updateOrCreate(
            ['profile_id' => $profile->id],
            array_merge($validated, [
                'languages_known' => $existing?->languages_known,
            ])
        );
    }

    private function updateSocial(Request $request, $profile): void
    {
        $validated = $request->validate([
            'facebook_url' => 'nullable|url|max:200',
            'instagram_url' => 'nullable|url|max:200',
            'linkedin_url' => 'nullable|url|max:200',
            'youtube_url' => 'nullable|url|max:200',
            'website_url' => 'nullable|url|max:200',
        ]);

        SocialMediaLink::updateOrCreate(
            ['profile_id' => $profile->id],
            $validated
        );
    }

    private function updatePartner(Request $request, $profile): void
    {
        $validated = $request->validate([
            'age_from' => 'nullable|integer|min:18|max:70',
            'age_to' => 'nullable|integer|min:18|max:70|gte:age_from',
            'height_from' => 'nullable|string|max:20',
            'height_to' => 'nullable|string|max:20',
            'complexion' => 'nullable|array',
            'body_type' => 'nullable|array',
            'marital_status' => 'nullable|array',
            'physical_status' => 'nullable|array',
            'family_status' => 'nullable|array',
            'religions' => 'nullable|array',
            'denomination' => 'nullable|array',
            'diocese' => 'nullable|array',
            'caste' => 'nullable|array',
            'sub_caste' => 'nullable|array',
            'mother_tongues' => 'nullable|array',
            'education_levels' => 'nullable|array',
            'occupations' => 'nullable|array',
            'working_countries' => 'nullable|array',
            'native_countries' => 'nullable|array',
            'about_partner' => 'nullable|string|max:5000',
        ]);

        // Strip "Any" from arrays
        foreach ($validated as $key => $value) {
            if (is_array($value)) {
                $filtered = array_values(array_filter($value, fn($v) => $v !== 'Any'));
                $validated[$key] = empty($filtered) ? null : $filtered;
            }
        }

        PartnerPreference::updateOrCreate(
            ['profile_id' => $profile->id],
            array_merge($validated, [
                'height_from_cm' => $validated['height_from'] ?? null,
                'height_to_cm' => $validated['height_to'] ?? null,
            ])
        );
    }
}
