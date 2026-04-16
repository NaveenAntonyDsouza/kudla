<?php

namespace App\Services;

use App\Models\PartnerPreference;
use App\Models\Profile;
use App\Traits\ProfileQueryFilters;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MatchingService
{
    use ProfileQueryFilters;

    /**
     * Default weights for match criteria.
     */
    private const DEFAULT_WEIGHTS = [
        'religion'         => 15,
        'age'              => 15,
        'denomination'     => 10,
        'mother_tongue'    => 10,
        'education'        => 10,
        'occupation'       => 10,
        'height'           => 8,
        'native_location'  => 8,
        'working_location' => 5,
        'marital_status'   => 5,
        'diet'             => 2,
        'family_status'    => 2,
    ];

    /**
     * Get weights from admin settings, fallback to defaults.
     */
    private static function getWeights(): array
    {
        $saved = json_decode(\App\Models\SiteSetting::getValue('match_weights', '{}') ?: '{}', true);

        return array_merge(self::DEFAULT_WEIGHTS, is_array($saved) ? $saved : []);
    }

    private const LABELS = [
        'religion'         => 'Religion',
        'age'              => 'Age Range',
        'denomination'     => 'Denomination / Caste',
        'mother_tongue'    => 'Mother Tongue',
        'education'        => 'Education',
        'occupation'       => 'Occupation',
        'height'           => 'Height Range',
        'native_location'  => 'Native Location',
        'working_location' => 'Working Location',
        'marital_status'   => 'Marital Status',
        'diet'             => 'Diet',
        'family_status'    => 'Family Status',
    ];

    /**
     * Calculate match score for a candidate against partner preferences.
     *
     * @return array{score: int, breakdown: array, badge: string|null}
     */
    public function calculateScore(Profile $candidate, PartnerPreference $prefs): array
    {
        $breakdown = [];
        $earnedWeight = 0;
        $applicableWeight = 0;

        foreach (self::getWeights() as $criterion => $weight) {
            if (!$this->isPreferenceSet($prefs, $criterion)) {
                continue;
            }

            $applicableWeight += $weight;
            $matched = $this->evaluateCriterion($candidate, $prefs, $criterion);

            if ($matched) {
                $earnedWeight += $weight;
            }

            $breakdown[] = [
                'criterion' => $criterion,
                'label'     => self::LABELS[$criterion],
                'weight'    => $weight,
                'matched'   => $matched,
            ];
        }

        $score = $applicableWeight > 0
            ? (int) round(($earnedWeight / $applicableWeight) * 100)
            : 0;

        return [
            'score'     => $score,
            'breakdown' => $breakdown,
            'badge'     => $this->getBadge($score),
        ];
    }

    /**
     * Get all matches for a profile, sorted by match score.
     */
    public function getMatches(Profile $profile, int $perPage = 20): LengthAwarePaginator
    {
        $prefs = $profile->partnerPreference;
        if (!$prefs) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        $candidates = $this->baseQuery($profile)
            ->with(['familyDetail', 'lifestyleInfo'])
            ->limit(500)
            ->get();

        $scored = $candidates->map(function ($candidate) use ($prefs) {
            $result = $this->calculateScore($candidate, $prefs);
            $candidate->match_score = $result['score'];
            $candidate->match_badge = $result['badge'];
            $candidate->match_breakdown = $result['breakdown'];
            return $candidate;
        })->sortByDesc('match_score')->values();

        // Manual pagination
        $page = request()->input('page', 1);
        $offset = ($page - 1) * $perPage;
        $items = $scored->slice($offset, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $scored->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Get mutual matches — both sides score >= 40%.
     */
    public function getMutualMatches(Profile $profile, int $perPage = 20): LengthAwarePaginator
    {
        $myPrefs = $profile->partnerPreference;
        if (!$myPrefs) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        $candidates = $this->baseQuery($profile)
            ->with(['familyDetail', 'lifestyleInfo', 'partnerPreference'])
            ->limit(500)
            ->get();

        $mutualMatches = $candidates->filter(function ($candidate) use ($profile, $myPrefs) {
            // My score: how well does this candidate match MY preferences?
            $myResult = $this->calculateScore($candidate, $myPrefs);
            if ($myResult['score'] < 40) {
                return false;
            }

            // Their score: how well do I match THEIR preferences?
            $theirPrefs = $candidate->partnerPreference;
            if (!$theirPrefs) {
                return false;
            }
            $theirResult = $this->calculateScore($profile, $theirPrefs);
            if ($theirResult['score'] < 40) {
                return false;
            }

            // Attach scores
            $candidate->match_score = $myResult['score'];
            $candidate->match_badge = $myResult['badge'];
            $candidate->match_breakdown = $myResult['breakdown'];
            $candidate->their_match_score = $theirResult['score'];
            $candidate->mutual_score = (int) round(($myResult['score'] + $theirResult['score']) / 2);

            return true;
        })->sortByDesc('mutual_score')->values();

        // Manual pagination
        $page = request()->input('page', 1);
        $offset = ($page - 1) * $perPage;
        $items = $mutualMatches->slice($offset, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $mutualMatches->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Get top N recommendations for dashboard.
     */
    public function getRecommendations(Profile $profile, int $limit = 6): Collection
    {
        $prefs = $profile->partnerPreference;
        if (!$prefs) {
            return collect();
        }

        $candidates = $this->baseQuery($profile)
            ->with(['familyDetail', 'lifestyleInfo'])
            ->limit(200)
            ->get();

        return $candidates->map(function ($candidate) use ($prefs) {
            $result = $this->calculateScore($candidate, $prefs);
            $candidate->match_score = $result['score'];
            $candidate->match_badge = $result['badge'];
            return $candidate;
        })->sortByDesc('match_score')->take($limit)->values();
    }

    /**
     * Get detailed match breakdown between two specific profiles.
     */
    public function getMatchBreakdown(Profile $myProfile, Profile $otherProfile): ?array
    {
        $prefs = $myProfile->partnerPreference;
        if (!$prefs) {
            return null;
        }

        // Ensure relations are loaded
        $otherProfile->loadMissing([
            'religiousInfo', 'educationDetail', 'locationInfo',
            'familyDetail', 'lifestyleInfo',
        ]);

        return $this->calculateScore($otherProfile, $prefs);
    }

    /**
     * Check if a preference field is set (non-null, non-empty, not just "Any").
     */
    private function isPreferenceSet(PartnerPreference $prefs, string $criterion): bool
    {
        return match ($criterion) {
            'religion'         => $this->hasArrayValues($prefs->religions),
            'age'              => $prefs->age_from > 0 && $prefs->age_to > 0,
            'denomination'     => $this->hasArrayValues($prefs->denomination) || $this->hasArrayValues($prefs->caste),
            'mother_tongue'    => $this->hasArrayValues($prefs->mother_tongues),
            'education'        => $this->hasArrayValues($prefs->education_levels),
            'occupation'       => $this->hasArrayValues($prefs->occupations),
            'height'           => (int) $prefs->height_from_cm > 0 && (int) $prefs->height_to_cm > 0,
            'native_location'  => $this->hasArrayValues($prefs->native_states) || $this->hasArrayValues($prefs->native_countries),
            'working_location' => $this->hasArrayValues($prefs->working_countries),
            'marital_status'   => $this->hasArrayValues($prefs->marital_status),
            'diet'             => $this->hasArrayValues($prefs->diet),
            'family_status'    => $this->hasArrayValues($prefs->family_status),
            default            => false,
        };
    }

    /**
     * Evaluate whether a candidate matches a specific criterion.
     */
    private function evaluateCriterion(Profile $candidate, PartnerPreference $prefs, string $criterion): bool
    {
        return match ($criterion) {
            'religion' => in_array(
                $candidate->religiousInfo?->religion,
                $prefs->religions ?? [],
                true
            ),

            'age' => $candidate->age >= $prefs->age_from
                   && $candidate->age <= $prefs->age_to,

            'denomination' => in_array($candidate->religiousInfo?->denomination, $prefs->denomination ?? [], true)
                           || in_array($candidate->religiousInfo?->caste, $prefs->caste ?? [], true),

            'mother_tongue' => in_array(
                $candidate->mother_tongue,
                $prefs->mother_tongues ?? [],
                true
            ),

            'education' => in_array(
                $candidate->educationDetail?->highest_education,
                $prefs->education_levels ?? [],
                true
            ),

            'occupation' => in_array(
                $candidate->educationDetail?->occupation,
                $prefs->occupations ?? [],
                true
            ),

            'height' => (int) $candidate->height >= (int) $prefs->height_from_cm
                      && (int) $candidate->height <= (int) $prefs->height_to_cm,

            'native_location' => in_array($candidate->locationInfo?->native_state, $prefs->native_states ?? [], true)
                              || in_array($candidate->locationInfo?->native_country, $prefs->native_countries ?? [], true),

            'working_location' => in_array(
                $candidate->educationDetail?->working_country,
                $prefs->working_countries ?? [],
                true
            ),

            'marital_status' => in_array(
                $candidate->marital_status,
                $prefs->marital_status ?? [],
                true
            ),

            'diet' => in_array(
                $candidate->lifestyleInfo?->diet,
                $prefs->diet ?? [],
                true
            ),

            'family_status' => in_array(
                $candidate->familyDetail?->family_status,
                $prefs->family_status ?? [],
                true
            ),

            default => false,
        };
    }

    /**
     * Check if an array has real values (not null, empty, or "Any").
     */
    private function hasArrayValues(?array $arr): bool
    {
        if (!$arr) {
            return false;
        }

        $filtered = array_filter($arr, fn($v) => $v !== null && $v !== '' && $v !== 'Any');
        return count($filtered) > 0;
    }

    /**
     * Get badge type based on score.
     */
    private function getBadge(int $score): ?string
    {
        if ($score >= 80) return 'great';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'partial';
        return null;
    }
}
