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
        // Who this member may see: active, approved, not hidden or suspended, other
        // gender, no block either way, not ignored, and every limit the others chose
        // under Settings → "Show my profile to" (App\Support\ProfileVisibility, 4 Nov
        // 2026: "matches only" was listed to everyone, "premium only" followed the
        // signed-in user instead of this member, an empty caste counted as the same).
        $query = \App\Support\ProfileVisibility::forMember(Profile::query(), $profile)
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
            $same = \App\Support\GotraBari::sameAs($profile->religiousInfo?->caste, (string) $profile->religiousInfo?->gotra);
            $caste = \App\Support\GotraBari::matchKey($profile->religiousInfo?->caste);
        } catch (\Throwable) {
            return;
        }
        if ($same === [] || $caste === null) {
            return;
        }

        // the bari itself, plus its published pair (GotraBari::SAME_BARI)
        $marks = implode(', ', array_fill(0, count($same), '?'));
        $query->whereDoesntHave('religiousInfo', fn ($q) => $q->whereRaw("LOWER(TRIM(gotra)) IN ({$marks})", $same)
            ->when($caste === 'brahmin',
                fn ($q) => $q->whereRaw('LOWER(caste) LIKE ?', ['%brahmin%']),
                fn ($q) => $q->whereRaw('LOWER(TRIM(caste)) = ?', [$caste])));
    }
}
