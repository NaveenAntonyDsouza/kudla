<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Traits\ProfileQueryFilters;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use ProfileQueryFilters;
    public function index(Request $request)
    {
        $user = auth()->user();
        $profile = $user->profile;
        $prefs = $profile->partnerPreference;

        // Smart defaults based on gender
        $isMale = $profile->gender === 'male' || $profile->gender === 'Male';
        $age = $profile->age ?? 25;

        $defaults = [
            'age_from' => $isMale ? 18 : $age,
            'age_to' => $isMale ? $age : min($age + 10, 70),
            'height_from' => $isMale ? '134 cm - 4 ft 05 inch' : ($profile->height ?? '134 cm - 4 ft 05 inch'),
            'height_to' => $isMale ? ($profile->height ?? '213 cm - 7 ft 00 inch') : '213 cm - 7 ft 00 inch',
        ];

        $activeTab = $request->get('tab', 'partner');

        // Load partner preferences into search form
        if ($request->boolean('load_prefs') && $prefs) {
            $mapped = $this->prefsToSearchParams($prefs);
            $request->merge($mapped);

            // Override defaults for age/height selects
            if ($prefs->age_from) $defaults['age_from'] = $prefs->age_from;
            if ($prefs->age_to) $defaults['age_to'] = $prefs->age_to;
            if ($prefs->height_from_cm) $defaults['height_from'] = $prefs->height_from_cm;
            if ($prefs->height_to_cm) $defaults['height_to'] = $prefs->height_to_cm;
        }

        // If search was submitted, show results page
        if ($request->has('search')) {
            $results = $this->buildSearchQuery($request, $profile)->paginate(20)->withQueryString();
            return view('search.results', compact('profile', 'results'));
        }

        // Keyword search
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $results = $this->baseQuery($profile)
                ->where(function ($q) use ($keyword) {
                    $q->where('full_name', 'LIKE', "%{$keyword}%")
                      ->orWhere('about_me', 'LIKE', "%{$keyword}%")
                      ->orWhere('matri_id', 'LIKE', "%{$keyword}%")
                      ->orWhereHas('educationDetail', fn($q2) => $q2->where('occupation_detail', 'LIKE', "%{$keyword}%")->orWhere('employer_name', 'LIKE', "%{$keyword}%"))
                      ->orWhereHas('religiousInfo', fn($q2) => $q2->where('religion', 'LIKE', "%{$keyword}%")->orWhere('denomination', 'LIKE', "%{$keyword}%"));
                })
                ->orderBy('created_at', 'desc')
                ->paginate(20)
                ->withQueryString();
            return view('search.results', compact('profile', 'results'));
        }

        // Search by ID
        $idResult = null;
        if ($request->filled('matri_id')) {
            $idResult = $this->baseQuery($profile)
                ->where('matri_id', strtoupper($request->matri_id))
                ->first();
        }

        return view('search.index', compact(
            'profile', 'prefs', 'defaults', 'idResult', 'activeTab'
        ));
    }

    private function buildSearchQuery(Request $request, Profile $profile)
    {
        $query = $this->baseQuery($profile);

        // Age filter
        $query->when($request->age_from, fn($q, $v) =>
            $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= ?', [(int) $v])
        );
        $query->when($request->age_to, fn($q, $v) =>
            $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [(int) $v])
        );

        // Height filter (compare by the cm prefix number)
        $query->when($request->height_from, function ($q, $v) {
            $cm = (int) $v;
            if ($cm > 0) {
                $q->whereRaw('CAST(height AS UNSIGNED) >= ?', [$cm]);
            }
        });
        $query->when($request->height_to, function ($q, $v) {
            $cm = (int) $v;
            if ($cm > 0) {
                $q->whereRaw('CAST(height AS UNSIGNED) <= ?', [$cm]);
            }
        });

        // Direct profile field filters
        $query->when($request->marital_status, fn($q, $v) =>
            $q->whereIn('marital_status', array_filter((array) $v, fn($i) => $i !== 'Any'))
        );
        $query->when($request->mother_tongue, fn($q, $v) =>
            $q->whereIn('mother_tongue', array_filter((array) $v, fn($i) => $i !== 'Any'))
        );
        $query->when($request->body_type, fn($q, $v) =>
            $q->whereIn('body_type', array_filter((array) $v, fn($i) => $i !== 'Any'))
        );
        $query->when($request->physical_status, fn($q, $v) =>
            $q->whereIn('physical_status', array_filter((array) $v, fn($i) => $i !== 'Any'))
        );

        // Religion filters (with cascading)
        $religions = array_filter((array) ($request->religion ?? []), fn($i) => $i !== 'Any');
        if (!empty($religions)) {
            $query->whereHas('religiousInfo', function ($q) use ($request, $religions) {
                $q->whereIn('religion', $religions);

                $denominations = array_filter((array) ($request->denomination ?? []), fn($i) => $i !== 'Any');
                if (!empty($denominations)) {
                    $q->whereIn('denomination', $denominations);
                }

                $castes = array_filter((array) ($request->caste ?? []), fn($i) => $i !== 'Any');
                if (!empty($castes)) {
                    $q->whereIn('caste', $castes);
                }
            });
        }

        // Education filter
        $education = array_filter((array) ($request->education ?? []), fn($i) => $i !== 'Any');
        if (!empty($education)) {
            $query->whereHas('educationDetail', fn($q) =>
                $q->whereIn('highest_education', $education)
            );
        }

        // Occupation filter
        $occupation = array_filter((array) ($request->occupation ?? []), fn($i) => $i !== 'Any');
        if (!empty($occupation)) {
            $query->whereHas('educationDetail', fn($q) =>
                $q->whereIn('occupation', $occupation)
            );
        }

        // Income filter
        $income = array_filter((array) ($request->annual_income ?? []), fn($i) => $i !== 'Any');
        if (!empty($income)) {
            $query->whereHas('educationDetail', fn($q) =>
                $q->whereIn('annual_income', $income)
            );
        }

        // Location filters
        $query->when($request->working_country, fn($q, $v) =>
            $q->whereHas('educationDetail', fn($q2) => $q2->where('working_country', $v))
        );
        $query->when($request->native_country, fn($q, $v) =>
            $q->whereHas('locationInfo', fn($q2) => $q2->where('native_country', $v))
        );

        // Family status
        $familyStatus = array_filter((array) ($request->family_status ?? []), fn($i) => $i !== 'Any');
        if (!empty($familyStatus)) {
            $query->whereHas('familyDetail', fn($q) =>
                $q->whereIn('family_status', $familyStatus)
            );
        }

        // Lifestyle filters
        $query->when($request->diet, function ($q, $v) {
            $filtered = array_filter((array) $v, fn($i) => $i !== 'Any');
            if (!empty($filtered)) {
                $q->whereHas('lifestyleInfo', fn($q2) => $q2->whereIn('diet', $filtered));
            }
        });
        $query->when($request->smoking, function ($q, $v) {
            $filtered = array_filter((array) $v, fn($i) => $i !== 'Any');
            if (!empty($filtered)) {
                $q->whereHas('lifestyleInfo', fn($q2) => $q2->whereIn('smoking', $filtered));
            }
        });
        $query->when($request->drinking, function ($q, $v) {
            $filtered = array_filter((array) $v, fn($i) => $i !== 'Any');
            if (!empty($filtered)) {
                $q->whereHas('lifestyleInfo', fn($q2) => $q2->whereIn('drinking', $filtered));
            }
        });

        // With photo only
        if ($request->boolean('with_photo')) {
            $query->whereHas('primaryPhoto');
        }

        // Premium/featured profiles first, then by date
        return $query
            ->orderByRaw('EXISTS(SELECT 1 FROM user_memberships WHERE user_memberships.user_id = profiles.user_id AND user_memberships.is_active = 1 AND (user_memberships.ends_at IS NULL OR user_memberships.ends_at > NOW())) DESC')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Map PartnerPreference fields to search form parameter names.
     */
    private function prefsToSearchParams($prefs): array
    {
        $params = [];

        // Multi-select arrays → search form arrays
        if ($prefs->religions) $params['religion'] = $prefs->religions;
        if ($prefs->denomination) $params['denomination'] = $prefs->denomination;
        if ($prefs->caste) $params['caste'] = $prefs->caste;
        if ($prefs->marital_status) $params['marital_status'] = $prefs->marital_status;
        if ($prefs->mother_tongues) $params['mother_tongue'] = $prefs->mother_tongues;
        if ($prefs->education_levels) $params['education'] = $prefs->education_levels;
        if ($prefs->occupations) $params['occupation'] = $prefs->occupations;
        if ($prefs->diet) $params['diet'] = $prefs->diet;
        if ($prefs->family_status) $params['family_status'] = $prefs->family_status;
        if ($prefs->physical_status) $params['physical_status'] = $prefs->physical_status;
        if ($prefs->body_type) $params['body_type'] = $prefs->body_type;

        // Single-value selects
        if ($prefs->working_countries) {
            $params['working_country'] = $prefs->working_countries[0] ?? null;
        }
        if ($prefs->native_countries) {
            $params['native_country'] = $prefs->native_countries[0] ?? null;
        }

        return $params;
    }
}
