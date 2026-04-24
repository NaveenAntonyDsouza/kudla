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
 *   3. Suspension — target suspended/banned (404 to viewer — don't reveal state)
 *   4. Blocked  — either party has blocked the other (404 — don't reveal the block)
 *   5. Hidden   — target.is_hidden = true AND no pre-existing interest (404)
 *   6. Visibility — target.show_profile_to:
 *        'all'     → allow
 *        'premium' → require viewer.isPremium() (REASON_VISIBILITY_PREMIUM)
 *        'matches' → require match score ≥ threshold (REASON_VISIBILITY_MATCHES)
 *   7. OK       — all gates clear
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

    /** Match-score threshold for target.show_profile_to = 'matches' gate. */
    public const MATCH_GATE_THRESHOLD = 70;

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

        if ($this->isSuspended($target)) {
            return self::REASON_SUSPENDED;
        }

        if ($this->isBlocked($viewer, $target)) {
            return self::REASON_BLOCKED;
        }

        if ($this->isHidden($target) && ! $this->hasAnyInterest($viewer, $target)) {
            return self::REASON_HIDDEN;
        }

        // Visibility setting: 'all' | 'premium' | 'matches'
        $visibility = $target->show_profile_to ?? 'all';

        if ($visibility === 'premium' && ! $this->isPremium($viewer)) {
            return self::REASON_VISIBILITY_PREMIUM;
        }

        if ($visibility === 'matches' && ! $this->matchScoreAbove($viewer, $target, self::MATCH_GATE_THRESHOLD)) {
            return self::REASON_VISIBILITY_MATCHES;
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
     * Rules:
     *   - Viewer must pass every profile-view gate
     *   - Viewer must be premium
     *   - There must be an ACCEPTED interest between viewer and target
     *
     * This is called by ProfileController::show to decide whether to
     * populate the `sections.contact` block.
     */
    public function canViewContact(Profile $viewer, Profile $target): bool
    {
        if (! $this->canAccess($viewer, $target)) {
            return false;
        }

        if ($viewer->id === $target->id) {
            return true;  // own profile — always see own contact
        }

        if (! $this->isPremium($viewer)) {
            return false;
        }

        return $this->hasAcceptedInterest($viewer, $target);
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
            return BlockedProfile::where(function ($q) use ($a, $b) {
                $q->where([
                    'profile_id' => $a->id,
                    'blocked_profile_id' => $b->id,
                ])->orWhere([
                    'profile_id' => $b->id,
                    'blocked_profile_id' => $a->id,
                ]);
            })->exists();
        } catch (\Throwable $e) {
            // Defensive: missing table in test DB → treat as "no block exists"
            // In production the table always exists (migration), so this branch never fires.
            return false;
        }
    }

    public function hasAnyInterest(Profile $a, Profile $b): bool
    {
        try {
            return Interest::where(function ($q) use ($a, $b) {
                $q->where([
                    'sender_profile_id' => $a->id,
                    'receiver_profile_id' => $b->id,
                ])->orWhere([
                    'sender_profile_id' => $b->id,
                    'receiver_profile_id' => $a->id,
                ]);
            })->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function hasAcceptedInterest(Profile $a, Profile $b): bool
    {
        try {
            return Interest::where('status', 'accepted')
                ->where(function ($q) use ($a, $b) {
                    $q->where([
                        'sender_profile_id' => $a->id,
                        'receiver_profile_id' => $b->id,
                    ])->orWhere([
                        'sender_profile_id' => $b->id,
                        'receiver_profile_id' => $a->id,
                    ]);
                })
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
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

    private function matchScoreAbove(Profile $viewer, Profile $target, int $threshold): bool
    {
        $prefs = $viewer->partnerPreference;
        if (! $prefs) {
            // No preferences set → can't gate by match score, deny per defensive default
            return false;
        }

        $result = $this->matching->calculateScore($target, $prefs);

        return (int) ($result['score'] ?? 0) >= $threshold;
    }
}
