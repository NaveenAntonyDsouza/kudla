<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\UserMembership;
use Illuminate\Support\Facades\DB;

/**
 * The contact-view limits each plan advertises (Oct 2026): MembershipPlan
 * view_contacts_limit (per membership period) and daily_contact_views (per
 * calendar day); 0 = unlimited. A "view" is the first time a member's phone /
 * WhatsApp opens to the viewer — opening the same member again never counts
 * twice. Members without an active plan (free-membership sites, or contact a
 * target opened to everyone) have no limit. Any failure lets the contact show:
 * a counting problem must never hide a contact someone has paid for.
 */
class ContactViewQuota
{
    /** Already opened before? (never uses up a view) */
    public function alreadyViewed(Profile $viewer, Profile $target): bool
    {
        try {
            return DB::table('contact_views')->where('viewer_profile_id', $viewer->id)->where('target_profile_id', $target->id)->exists();
        } catch (\Throwable) {
            return true;
        }
    }

    /** May this contact be shown now? Records the view when it's a new one. */
    public function allow(Profile $viewer, Profile $target): bool
    {
        if ($viewer->id === $target->id || $this->alreadyViewed($viewer, $target)) {
            return true;
        }

        try {
            [$membership, $plan] = $this->planFor($viewer);
            if ($plan && $this->limitReason($viewer, $membership, $plan) !== null) {
                return false;
            }
            DB::table('contact_views')->insertOrIgnore([
                'viewer_profile_id' => $viewer->id,
                'target_profile_id' => $target->id,
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // fail open
        }

        return true;
    }

    /** Sentence for the member who has used up their views, or null when not limited. */
    public function reason(Profile $viewer): ?string
    {
        try {
            [$membership, $plan] = $this->planFor($viewer);

            return $plan ? $this->limitReason($viewer, $membership, $plan) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function limitReason(Profile $viewer, UserMembership $membership, $plan): ?string
    {
        $name = $plan->plan_name ?? 'your';
        $total = (int) ($plan->view_contacts_limit ?? 0);
        $daily = (int) ($plan->daily_contact_views ?? 0);

        if ($total > 0) {
            $used = DB::table('contact_views')->where('viewer_profile_id', $viewer->id)
                ->when($membership->starts_at, fn ($q, $from) => $q->where('created_at', '>=', $from))
                ->count();
            if ($used >= $total) {
                return "You've opened {$used} contacts — all {$total} included in your {$name} plan. Upgrade to see more phone numbers.";
            }
        }
        if ($daily > 0) {
            $today = DB::table('contact_views')->where('viewer_profile_id', $viewer->id)->where('created_at', '>=', now()->startOfDay())->count();
            if ($today >= $daily) {
                return "You've opened {$daily} contacts today, the daily limit on your {$name} plan. More open tomorrow, or upgrade for more.";
            }
        }

        return null;
    }

    /** @return array{0: ?UserMembership, 1: ?\App\Models\MembershipPlan} */
    private function planFor(Profile $viewer): array
    {
        $membership = $viewer->user?->activeMembership();

        return [$membership, $membership?->plan];
    }
}
