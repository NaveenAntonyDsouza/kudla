<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Weekly "who viewed your profile" email. One of the strongest reasons
 * members come back on matrimony sites (kudla: ~640 views a week across
 * ~210 members). Same visibility rule as the website's "Viewed me" page:
 * members with full access see who it was; others see how many, with an
 * upgrade link.
 */
final class ProfileViewsDigest
{
    public const DAYS = 7;

    public const MAX_CARDS = 4;

    /**
     * Members viewed by at least one other visible member in the last week.
     *
     * @return Collection<int, array{user: User, viewers: Collection<int, Profile>, count: int}>
     */
    public function due(): Collection
    {
        $since = now()->subDays(self::DAYS);

        $viewsPerProfile = DB::table('profile_views as pv')
            ->join('profiles as viewer', 'viewer.id', '=', 'pv.viewer_profile_id')
            ->where('pv.viewed_at', '>=', $since)
            ->whereColumn('pv.viewer_profile_id', '!=', 'pv.viewed_profile_id')
            ->whereNull('viewer.deleted_at')->where('viewer.is_active', true)
            ->where(fn ($q) => $q->where('viewer.is_hidden', false)->orWhereNull('viewer.is_hidden'))
            ->groupBy('pv.viewed_profile_id')
            ->selectRaw('pv.viewed_profile_id as profile_id, COUNT(DISTINCT pv.viewer_profile_id) as viewers')
            ->pluck('viewers', 'profile_id');

        if ($viewsPerProfile->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereNull('staff_role_id')
            ->where(fn ($q) => $q->where('role', '!=', 'admin')->orWhereNull('role'))
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('last_views_digest_at')->orWhere('last_views_digest_at', '<', now()->subDays(self::DAYS - 1)))
            ->whereHas('profile', fn ($q) => $q->whereIn('id', $viewsPerProfile->keys())->where('onboarding_completed', true))
            ->with('profile')
            ->get()
            ->filter(fn (User $u) => $u->blockedStatus() === null && $u->wantsNotification('email_views'))
            ->map(fn (User $u) => [
                'user' => $u,
                'count' => (int) $viewsPerProfile[$u->profile->id],
                'viewers' => $this->recentViewers($u->profile, $since),
            ])
            ->values();
    }

    /** The most recent distinct viewers, minus anyone blocked either way. */
    private function recentViewers(Profile $profile, $since): Collection
    {
        $ids = DB::table('profile_views')
            ->where('viewed_profile_id', $profile->id)->where('viewed_at', '>=', $since)
            ->where('viewer_profile_id', '!=', $profile->id)
            ->groupBy('viewer_profile_id')
            ->orderByRaw('MAX(viewed_at) DESC')
            ->limit(self::MAX_CARDS * 3)
            ->pluck('viewer_profile_id');

        $blocked = $this->blockedIds($profile->id);

        return Profile::query()->whereIn('id', $ids->diff($blocked))
            ->whereNull('deleted_at')->where('is_active', true)
            ->where(fn ($q) => $q->where('is_hidden', false)->orWhereNull('is_hidden'))
            ->with(['locationInfo', 'educationDetail'])
            ->get()
            ->sortBy(fn (Profile $p) => $ids->search($p->id))
            ->take(self::MAX_CARDS)
            ->values();
    }

    private function blockedIds(int $profileId): array
    {
        try {
            return DB::table('blocked_profiles')
                ->where('profile_id', $profileId)->pluck('blocked_profile_id')
                ->merge(DB::table('blocked_profiles')->where('blocked_profile_id', $profileId)->pluck('profile_id'))
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
