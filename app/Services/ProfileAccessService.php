<?php

namespace App\Services;

use App\Models\BlockedProfile;
use App\Models\Interest;
use App\Models\Profile;

/**
 * Centralized privacy-gate checks for "can viewer see target's profile?"
 *
 * Every endpoint that returns another user's data goes through
 * `check(viewer, target)`. Returns one of the REASON_* constants.
 *
 * Gate order (important — some reasons mask others intentionally):
 *   1. Self     — viewer == target (allowed; same as OK)
 *   2. Gender   — same-gender view is never allowed (403)
 *   3. Unavailable — target deactivated, not approved or deleted (404)
 *   4. Suspension — target suspended/banned (404 to viewer — don't reveal state)
 *   5. Blocked  — either party has blocked the other (404 — don't reveal the block)
 *   6. Hidden   — target.is_hidden = true AND no pre-existing interest (404)
 *   7. Visibility — the limits the target chose (App\Support\ProfileVisibility),
 *      waived when the two already share an interest (either way):
 *        show_profile_to 'premium' → viewer is premium (REASON_VISIBILITY_PREMIUM)
 *        show_profile_to 'matches' → the VIEWER matches the TARGET's partner
 *          preferences (REASON_VISIBILITY_MATCHES; until 4 Nov 2026 it scored the
 *          target against the viewer's preferences, the wrong way round)
 *        same religion / denomination / caste / sect / mother tongue only
 *          (REASON_VISIBILITY_COMMUNITY, 4 Nov 2026)
 *   8. OK       — all gates clear
 *
 * Controllers map reasons to envelope errors:
 *   REASON_SAME_GENDER        → 403 GENDER_MISMATCH
 *   REASON_BLOCKED | HIDDEN |
 *   SUSPENDED                 → 404 NOT_FOUND (anti-enumeration)
 *   REASON_VISIBILITY_*       → 403 UNAUTHORIZED (hint: PREMIUM_REQUIRED)
 *   REASON_SELF | REASON_OK   → 200 (allow)
 *
 * UI-safe API contract points enforced indirectly (via controllers calling
 * this service):
 *   6. Error responses — every REASON_* has a stable mapping to an error code
 *
 * Design references:
 *   - docs/mobile-app/reference/ui-safe-api-checklist.md
 *   - docs/mobile-app/design/04-profile-api.md §4.4
 */
class ProfileAccessService
{
    public const REASON_OK = 'ok';
    public const REASON_SELF = 'self';
    public const REASON_SAME_GENDER = 'same_gender';
    public const REASON_BLOCKED = 'blocked';
    public const REASON_HIDDEN = 'hidden';
    public const REASON_SUSPENDED = 'suspended';
    public const REASON_VISIBILITY_PREMIUM = 'visibility_premium';
    public const REASON_VISIBILITY_MATCHES = 'visibility_matches';
    public const REASON_VISIBILITY_COMMUNITY = 'visibility_community';
    public const REASON_UNAVAILABLE = 'unavailable';

    /** Match-score threshold for target.show_profile_to = 'matches' gate. */
    public const MATCH_GATE_THRESHOLD = \App\Support\ProfileVisibility::MATCH_THRESHOLD;

    public function __construct(private MatchingService $matching) {}

    /**
     * Run every gate in order. Returns the first failing reason, or
     * REASON_OK / REASON_SELF if everything passes.
     */
    public function check(Profile $viewer, Profile $target): string
    {
        if ($viewer->id === $target->id) {
            return self::REASON_SELF;
        }

        if ($this->sameGender($viewer, $target)) {
            return self::REASON_SAME_GENDER;
        }

        // deactivated / awaiting deletion, not approved yet, or deleted (4 Nov 2026:
        // the web profile page opened these for anyone who typed the Matri ID)
        if ($target->is_active === false || $target->is_approved === false || $target->trashed()) {
            return self::REASON_UNAVAILABLE;
        }

        if ($this->isSuspended($target)) {
            return self::REASON_SUSPENDED;
        }

        if ($this->isBlocked($viewer, $target)) {
            return self::REASON_BLOCKED;
        }

        // an interest either way (asked once, below) lets the two see each other
        $connected = null;
        $hasInterest = function () use (&$connected, $viewer, $target) {
            return $connected ??= $this->hasAnyInterest($viewer, $target);
        };

        if ($this->isHidden($target) && ! $hasInterest()) {
            return self::REASON_HIDDEN;
        }

        // the limits the target chose under Settings → "Show my profile to"
        $limit = \App\Support\ProfileVisibility::limitsAllow($viewer, $target);
        if ($limit !== null && ! $hasInterest()) {
            return $limit;
        }

        return self::REASON_OK;
    }

    /**
     * Boolean convenience: was access granted? Treats SELF the same as OK.
     */
    public function canAccess(Profile $viewer, Profile $target): bool
    {
        $reason = $this->check($viewer, $target);

        return in_array($reason, [self::REASON_OK, self::REASON_SELF], true);
    }

    /**
     * Contact section visibility — stricter than profile-view access.
     *
     * Rules (in evaluation order):
     *   1. Viewer must pass every profile-view gate (canAccess).
     *   2. Self-view always allowed (return own contact).
     *   3. Target on a plan with `exposes_contact_to_free=true` →
     *      always visible to ANY viewer (Shaadi-Plus convention).
     *      Bypasses both the premium check and the accepted-interest
     *      check below — Plus-tier members are explicitly opting in
     *      to free-tier discoverability.
     *   4. Otherwise: viewer must be premium AND there must be an
     *      ACCEPTED interest between the two profiles.
     *
     * This is called by ProfileController::show to decide whether to
     * populate the `sections.contact` block in the response.
     */
    public function canViewContact(Profile $viewer, Profile $target): bool
    {
        if (! $this->canAccess($viewer, $target)) {
            return false;
        }

        if ($viewer->id === $target->id) {
            return true;  // own profile — always see own contact
        }

        // Plus-tier flag on TARGET — opens contact to any viewer
        // (including free members) regardless of premium / interest status.
        if ($this->targetExposesContactToFree($target)) {
            return true;
        }

        if (! $this->isPremium($viewer)) {
            return false;
        }

        return $this->hasAcceptedInterest($viewer, $target);
    }

    /**
     * Does the target hold an active membership with the
     * `exposes_contact_to_free` flag set? Defensive — returns false
     * silently if the user relation or the membership query fails.
     *
     * `protected` so tests can stub it via subclassing and exercise the
     * canViewContact branch without setting up the full membership +
     * plan tables.
     */
    protected function targetExposesContactToFree(Profile $target): bool
    {
        try {
            return (bool) ($target->user?->activePlanExposesContactToFree() ?? false);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Can viewer send an interest to target?
     * Pre-flight check before hitting InterestService (which has its own
     * rate limits, duplicate-check, etc.).
     */
    public function canSendInterest(Profile $viewer, Profile $target): bool
    {
        if ($viewer->id === $target->id) {
            return false;  // can't send interest to self
        }

        return $this->canAccess($viewer, $target);
    }

    /**
     * Can viewer shortlist target?
     */
    public function canShortlist(Profile $viewer, Profile $target): bool
    {
        if ($viewer->id === $target->id) {
            return false;
        }

        return $this->canAccess($viewer, $target);
    }

    /**
     * Should the target's photos be blurred to this viewer?
     * Used by PhotoResource::shouldBlurFor (full logic in week-3 step-7).
     *
     * Rule: blur if target.photo_privacy.blur_non_premium = true AND
     * viewer is not premium AND no photo_access_grant exists.
     */
    public function shouldBlurPhotos(Profile $viewer, Profile $target): bool
    {
        if ($viewer->id === $target->id) {
            return false;  // own photos never blurred
        }

        $privacy = $target->photoPrivacySetting;
        if (! $privacy || ! ($privacy->blur_non_premium ?? false)) {
            return false;  // no privacy row or not enforcing blur
        }

        return ! $this->isPremium($viewer);
    }

    /* ------------------------------------------------------------------
     |  Individual gate helpers (public so tests can exercise each)
     | ------------------------------------------------------------------ */

    public function sameGender(Profile $a, Profile $b): bool
    {
        if (! $a->gender || ! $b->gender) {
            return false;  // missing data → don't block; matching system guards this elsewhere
        }

        return strtolower((string) $a->gender) === strtolower((string) $b->gender);
    }

    public function isSuspended(Profile $profile): bool
    {
        $status = $profile->suspension_status ?? 'active';

        return $status !== 'active';
    }

    public function isHidden(Profile $profile): bool
    {
        return (bool) ($profile->is_hidden ?? false);
    }

    public function isBlocked(Profile $a, Profile $b): bool
    {
        try {
            return BlockedProfile::where(fn ($q) => self::eitherWay($q, 'profile_id', 'blocked_profile_id', $a->id, $b->id))->exists();
        } catch (\Throwable $e) {
            // Defensive: missing table in test DB → treat as "no block exists"
            // In production the table always exists (migration), so this branch never fires.
            return false;
        }
    }

    public function hasAnyInterest(Profile $a, Profile $b): bool
    {
        try {
            return Interest::where(fn ($q) => self::eitherWay($q, 'sender_profile_id', 'receiver_profile_id', $a->id, $b->id))->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function hasAcceptedInterest(Profile $a, Profile $b): bool
    {
        try {
            return Interest::where('status', 'accepted')
                ->where(fn ($q) => self::eitherWay($q, 'sender_profile_id', 'receiver_profile_id', $a->id, $b->id))
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * (from = a AND to = b) OR (from = b AND to = a).
     *
     * Never an array inside orWhere: Laravel joins the pairs of an orWhere
     * ARRAY with OR, so the second half read "from = b OR to = a".
     * That opened a member's phone to every member once she had sent one
     * accepted interest (Kudla, 25 Oct – 4 Nov 2026: 135 contact views).
     */
    public static function eitherWay($query, string $from, string $to, int $a, int $b)
    {
        return $query->where(fn ($w) => $w->where($from, $a)->where($to, $b))
            ->orWhere(fn ($w) => $w->where($from, $b)->where($to, $a));
    }

    /**
     * Premium check. Operates on a Profile's owning User.
     * Returns false if user missing or the membership table is unreachable
     * (defensive — no premium gate triggered in either case).
     */
    public function isPremium(Profile $profile): bool
    {
        try {
            return (bool) ($profile->user?->isPremium() ?? false);
        } catch (\Throwable $e) {
            // Missing user_memberships table (test env) → treat as not-premium.
            return false;
        }
    }

}
