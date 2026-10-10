<?php

namespace App\Traits;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Builder;

trait ProfileQueryFilters
{
    /**
     * Base query with all common filters: active, hidden, blocked, visibility prefs.
     * Used by SearchController, DashboardController, MatchingService.
     *
     * $respectLineagePreference: honour "Don't show profiles with my gotra /
     * bari" — off for a search by Matri ID, where the member asked for that
     * one profile.
     */
    protected function baseQuery(Profile $profile, bool $respectLineagePreference = true): Builder
    {
        $query = Profile::query()
            ->where('id', '!=', $profile->id)
            ->where('is_active', true)
            ->approved()
            ->where(fn($q) => $q->where('is_hidden', false)->orWhereNull('is_hidden'))
            ->where('gender', '!=', $profile->gender)
            ->whereDoesntHave('blockedByOthers', fn($q) => $q->where('profile_id', $profile->id))
            ->whereDoesntHave('blockedProfiles', fn($q) => $q->where('blocked_profile_id', $profile->id))
            // Exclude ignored profiles
            ->whereDoesntHave('ignoredByOthers', fn($q) => $q->where('profile_id', $profile->id))
            // Enforce show_profile_to setting
            ->where(function ($q) use ($profile) {
                $q->where('show_profile_to', 'all')
                    ->orWhereNull('show_profile_to')
                    // "premium" — show only if I am a premium member
                    ->orWhere(function ($q2) use ($profile) {
                        $q2->where('show_profile_to', 'premium')
                            ->whereRaw('? = 1', [auth()->user()?->isPremium() ? 1 : 0]);
                    })
                    // "matches" — ideally show only if I match their partner preferences.
                    // Full reverse-matching is expensive (load each target's prefs + score).
                    // Partially enforced via only_same_religion/denomination/mother_tongue toggles below.
                    // Full enforcement deferred to v2 (use match_scores table for pre-calculated reverse matches).
                    ->orWhere('show_profile_to', 'matches');
            })
            // Visibility preferences (religion, denomination, mother tongue)
            ->where(function ($q) use ($profile) {
                $q->where(function ($q2) use ($profile) {
                    $q2->where('only_same_religion', false)->orWhereNull('only_same_religion')
                        ->orWhereHas('religiousInfo', fn($q3) => $q3->where('religion', $profile->religiousInfo?->religion));
                });
                $q->where(function ($q2) use ($profile) {
                    $q2->where('only_same_denomination', false)->orWhereNull('only_same_denomination')
                        ->orWhereHas('religiousInfo', fn($q3) => $q3->where('denomination', $profile->religiousInfo?->denomination)->orWhere('caste', $profile->religiousInfo?->caste));
                });
                $q->where(function ($q2) use ($profile) {
                    $q2->where('only_same_mother_tongue', false)->orWhereNull('only_same_mother_tongue')
                        ->orWhere('mother_tongue', $profile->mother_tongue);
                });
            })
            ->with(['primaryPhoto', 'religiousInfo', 'educationDetail', 'locationInfo', 'user.userMemberships', 'photoPrivacySetting']);

        if ($respectLineagePreference) {
            $this->excludeSameGotra($query, $profile);
        }

        return $query;
    }

    /**
     * "Don't show profiles with my gotra / bari" (partner preference, Oct 2026):
     * the same gotra or bari counts as brother and sister. Only when the
     * member chose it and has a real gotra / bari and caste of their own,
     * and only within the same caste (every Brahmin community counts as one),
     * as GotraBari::shared().
     */
    protected function excludeSameGotra(Builder $query, Profile $profile): void
    {
        try {
            if (! $profile->partnerPreference?->exclude_same_gotra) {
                return;
            }
            $mine = \App\Support\GotraBari::normalize($profile->religiousInfo?->gotra);
            $caste = \App\Support\GotraBari::matchKey($profile->religiousInfo?->caste);
        } catch (\Throwable) {
            return;
        }
        if ($mine === null || $caste === null) {
            return;
        }

        $query->whereDoesntHave('religiousInfo', fn ($q) => $q->whereRaw('LOWER(TRIM(gotra)) = ?', [$mine])
            ->when($caste === 'brahmin',
                fn ($q) => $q->whereRaw('LOWER(caste) LIKE ?', ['%brahmin%']),
                fn ($q) => $q->whereRaw('LOWER(TRIM(caste)) = ?', [$caste])));
    }
}
