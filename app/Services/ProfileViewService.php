<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\ProfileView;
use Illuminate\Support\Carbon;

/**
 * Records "viewer saw target" events, backing the dashboard's
 * recent_views carousel + the "Who viewed me?" screen.
 *
 * Dedupes within a 24-hour window per (viewer, target) pair so a
 * user refreshing a profile page doesn't pollute the view feed.
 *
 * The underlying ProfileView model uses:
 *   viewer_profile_id + viewed_profile_id + viewed_at
 *
 * (Note: $timestamps = false — we set viewed_at manually.)
 *
 * Design reference:
 *   - docs/mobile-app/phase-2a-api/week-03-profiles-photos-search/step-05-view-other-profile.md
 */
class ProfileViewService
{
    /** Dedup window: one view per viewer-target pair per 24h. */
    public const DEDUP_WINDOW_HOURS = 24;

    /**
     * Record a view. Skips:
     *   - Self-views (viewer.id === target.id)
     *   - Duplicate views within the dedup window
     *
     * DB failures are swallowed — view tracking is a best-effort signal,
     * it must never break the /profiles/{id} endpoint if the table is
     * unreachable (migration drift, test env, etc.).
     */
    public function track(Profile $viewer, Profile $target): void
    {
        if ($viewer->id === $target->id) {
            return;  // don't log self-views
        }

        try {
            $existing = ProfileView::where('viewer_profile_id', $viewer->id)
                ->where('viewed_profile_id', $target->id)
                ->where('viewed_at', '>', Carbon::now()->subHours(self::DEDUP_WINDOW_HOURS))
                ->exists();

            if ($existing) {
                return;
            }

            ProfileView::create([
                'viewer_profile_id' => $viewer->id,
                'viewed_profile_id' => $target->id,
                'viewed_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            // Table missing (test env) or transient DB error — silently swallow.
            // Production tables are present via migration; this branch never fires there.
        }
    }
}
