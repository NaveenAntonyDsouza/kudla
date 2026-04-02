<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;

class SearchController extends Controller
{
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

        // If search was submitted, show results page
        if ($request->has('search')) {
            $results = $this->buildSearchQuery($request, $profile)->paginate(20)->withQueryString();
            return view('search.results', compact('profile', 'results'));
        }

        // Search by ID
        $idResult = null;
        if ($request->filled('matri_id')) {
            $idResult = Profile::where('matri_id', strtoupper($request->matri_id))
                ->where('id', '!=', $profile->id)
                ->where('is_active', true)
                ->with(['primaryPhoto', 'religiousInfo', 'educationDetail', 'locationInfo'])
                ->first();
        }

        return view('search.index', compact(
            'profile', 'prefs', 'defaults', 'idResult', 'activeTab'
        ));
    }

    private function buildSearchQuery(Request $request, Profile $profile)
    {
        $query = Profile::query()
            ->where('id', '!=', $profile->id)
            ->where('is_active', true)
            ->where('gender', '!=', $profile->gender)
            ->whereDoesntHave('blockedByOthers', fn($q) => $q->where('profile_id', $profile->id))
            ->whereDoesntHave('blockedProfiles', fn($q) => $q->where('blocked_profile_id', $profile->id))
            ->with(['primaryPhoto', 'religiousInfo', 'educationDetail', 'locationInfo']);

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

        return $query->orderBy('created_at', 'desc');
    }
}
