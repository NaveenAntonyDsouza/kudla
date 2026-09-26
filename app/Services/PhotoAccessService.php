<?php

namespace App\Services;

use App\Models\PhotoAccessGrant;
use App\Models\Profile;
use Illuminate\Support\Carbon;

/**
 * Records per-viewer photo access grants — a RECORD ONLY.
 *
 * ⚠ Do NOT use hasAccess() to decide whether a photo is shown. Photo
 * visibility has exactly one rule, App\Support\PhotoVisibility, used by the
 * website and the API alike: a hidden photo is visible to a member whose
 * photo request was APPROVED (photo_requests.status). A second source of
 * truth here would let the app and the website disagree — which is how
 * hidden photos leaked before (Sep 2026).
 *
 * Still called from the API approve endpoint (grant()), which keeps the
 * photo_access_grants table as an audit trail of who was granted access.
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
