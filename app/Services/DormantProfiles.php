<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Members who stopped using the site. Profiles of people who married or gave
 * up never answer interests, which drags everyone's reply rate down (kudla,
 * Oct 2026: 259 interests in 30 days, 26 accepted). So:
 *
 *  - after CHECKIN_AFTER_DAYS without activity: one "Are you still looking?"
 *    email (still looking / found my match / hide my profile);
 *  - after HIDE_AFTER_DAYS, and at least HIDE_GRACE_DAYS after that email with
 *    no answer: hidden from search and matches (is_hidden, hidden_reason
 *    "dormant"). Any visit brings the profile back (TrackMemberActivity).
 *
 * "Last seen" is the latest of: last login / visit, login history, profiles
 * they viewed, interests they sent or answered, and signing up. users.last_login_at
 * alone is not enough: before 2 Oct 2026 staying logged in didn't update it.
 */
final class DormantProfiles
{
    public const CHECKIN_AFTER_DAYS = 90;

    public const HIDE_AFTER_DAYS = 180;

    public const HIDE_GRACE_DAYS = 14;

    public const REASON = 'dormant';

    /** Members due the "are you still looking?" email. */
    public function dueForCheckin(): Collection
    {
        return $this->inactiveSince(now()->subDays(self::CHECKIN_AFTER_DAYS))
            ->filter(fn (User $u) => $u->dormant_checkin_at === null
                || $u->dormant_checkin_at->lt($this->lastSeen($u)))  // asked before, came back, went quiet again
            ->values();
    }

    /** Members to hide: long inactive, asked, and no answer since. */
    public function dueForHiding(): Collection
    {
        return $this->inactiveSince(now()->subDays(self::HIDE_AFTER_DAYS))
            ->filter(fn (User $u) => $u->dormant_checkin_at !== null
                && $u->dormant_checkin_at->lte(now()->subDays(self::HIDE_GRACE_DAYS))
                && $u->dormant_checkin_at->gte($this->lastSeen($u)))
            ->values();
    }

    public function hide(User $user): void
    {
        DB::table('profiles')->where('user_id', $user->id)->where(fn ($q) => $q->where('is_hidden', false)->orWhereNull('is_hidden'))
            ->update(['is_hidden' => true, 'hidden_reason' => self::REASON, 'updated_at' => now()]);
    }

    /** Bring back a profile WE hid for inactivity (never one the member hid). */
    public static function restore(int $userId): void
    {
        try {
            DB::table('profiles')->where('user_id', $userId)->where('is_hidden', true)->where('hidden_reason', self::REASON)
                ->update(['is_hidden' => false, 'hidden_reason' => null, 'updated_at' => now()]);
        } catch (\Throwable) {
            // never let this break the activity tracking that calls it
        }
    }

    /**
     * Completed, visible members of good standing whose last activity of any
     * kind is before $cutoff. Each gets ->last_seen (Carbon).
     */
    public function inactiveSince(Carbon $cutoff): Collection
    {
        $users = User::query()
            ->whereNull('staff_role_id')
            ->where(fn ($q) => $q->where('role', '!=', 'admin')->orWhereNull('role'))
            ->where('is_active', true)
            ->where('created_at', '<', $cutoff)
            ->where(fn ($q) => $q->whereNull('last_login_at')->orWhere('last_login_at', '<', $cutoff))
            ->whereHas('profile', fn ($q) => $q->where('onboarding_completed', true)->where('is_active', true)
                ->where(fn ($h) => $h->where('is_hidden', false)->orWhereNull('is_hidden')))
            ->with('profile') // whole row: blockedStatus() reads is_active / suspension_status
            ->get();

        if ($users->isEmpty()) {
            return $users;
        }

        $profileIds = $users->pluck('profile.id')->filter()->all();
        $userIds = $users->pluck('id')->all();
        $latest = fn ($table, $column, $key, $ids, $extra = null) => DB::table($table)->whereIn($key, $ids)
            ->when($extra, $extra)->groupBy($key)->selectRaw("{$key} as k, MAX({$column}) as m")->pluck('m', 'k');

        $viewed = $latest('profile_views', 'viewed_at', 'viewer_profile_id', $profileIds);
        $sent = $latest('interests', 'created_at', 'sender_profile_id', $profileIds);
        $answered = $latest('interests', 'updated_at', 'receiver_profile_id', $profileIds, fn ($q) => $q->whereIn('status', ['accepted', 'declined']));
        $logins = $latest('login_history', 'logged_in_at', 'user_id', $userIds);

        return $users->each(function (User $u) use ($viewed, $sent, $answered, $logins) {
            $pid = $u->profile?->id;
            $u->last_seen = collect([$u->created_at, $u->last_login_at, $viewed[$pid] ?? null, $sent[$pid] ?? null, $answered[$pid] ?? null, $logins[$u->id] ?? null])
                ->filter()->map(fn ($d) => Carbon::parse($d))->max();
        })->filter(fn (User $u) => $u->last_seen->lt($cutoff))->values();
    }

    private function lastSeen(User $user): Carbon
    {
        return $user->last_seen;
    }
}
