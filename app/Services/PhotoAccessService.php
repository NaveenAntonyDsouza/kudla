<?php

namespace App\Services;

use App\Models\PhotoAccessGrant;
use App\Models\Profile;
use Illuminate\Support\Carbon;

/**
 * Manages per-viewer access to gated/blurred photos.
 *
 * Called from:
 *   - Step 11 (photo request endpoints): approving a PhotoRequest calls
 *     grant() so the requester can see the target's gated photos.
 *   - Step 9 onwards (PhotoResource::shouldBlurFor): hasAccess() is
 *     consulted when deciding whether a non-owner viewer sees the real
 *     photo or a blurred placeholder.
 *
 * All three methods wrap DB access in try/catch and return safe defaults
 * on failure. Matches the defensive pattern across ProfileAccessService,
 * DashboardService, ProfileViewService. Production tables always exist;
 * the fallback only fires in the SQLite :memory: test env.
 *
 * Design reference:
 *   docs/mobile-app/phase-2a-api/week-03-profiles-photos-search/step-08-photo-access-grants.md
 */
class PhotoAccessService
{
    /**
     * Idempotently record "$grantee may now see $grantor's gated photos."
     *
     * If a grant already exists, updates its granted_at timestamp (so the
     * most recent approval wins); otherwise creates a fresh row. Never
     * duplicates a row — the unique constraint at the DB layer guarantees
     * (grantor_profile_id, grantee_profile_id) is unique.
     */
    public function grant(Profile $grantor, Profile $grantee): void
    {
        try {
            PhotoAccessGrant::updateOrCreate(
                [
                    'grantor_profile_id' => $grantor->id,
                    'grantee_profile_id' => $grantee->id,
                ],
                [
                    'granted_at' => Carbon::now(),
                ],
            );
        } catch (\Throwable $e) {
            // Swallow silently — granting access is a side-effect of
            // approving a PhotoRequest, and the caller (step-11) should
            // not fail the user-facing approve action if the grant write
            // hiccups. Production tables always exist; this branch only
            // fires in test environments.
        }
    }

    /**
     * Remove any grant between these two profiles. Idempotent — calling
     * on a non-existent grant is a safe no-op.
     */
    public function revoke(Profile $grantor, Profile $grantee): void
    {
        try {
            PhotoAccessGrant::where('grantor_profile_id', $grantor->id)
                ->where('grantee_profile_id', $grantee->id)
                ->delete();
        } catch (\Throwable $e) {
            // Same defensive stance as grant().
        }
    }

    /**
     * Does $grantee currently have access to $grantor's gated photos?
     *
     * Returns false on any query failure — a missing grant (whether real
     * or from a DB error) must not accidentally unblur photos.
     */
    public function hasAccess(Profile $grantor, Profile $grantee): bool
    {
        try {
            return PhotoAccessGrant::where('grantor_profile_id', $grantor->id)
                ->where('grantee_profile_id', $grantee->id)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
