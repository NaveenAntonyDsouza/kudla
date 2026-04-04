<?php

namespace App\Traits;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Builder;

trait ProfileQueryFilters
{
    /**
     * Base query with all common filters: active, hidden, blocked, visibility prefs.
     * Used by SearchController, DashboardController, MatchingService.
     */
    protected function baseQuery(Profile $profile): Builder
    {
        return Profile::query()
            ->where('id', '!=', $profile->id)
            ->where('is_active', true)
            ->where(fn($q) => $q->where('is_hidden', false)->orWhereNull('is_hidden'))
            ->where('gender', '!=', $profile->gender)
            ->whereDoesntHave('blockedByOthers', fn($q) => $q->where('profile_id', $profile->id))
            ->whereDoesntHave('blockedProfiles', fn($q) => $q->where('blocked_profile_id', $profile->id))
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
            ->with(['primaryPhoto', 'religiousInfo', 'educationDetail', 'locationInfo']);
    }
}
