<?php

namespace App\Http\Resources\V1;

use App\Models\BlockedProfile;
use App\Models\Interest;
use App\Models\Profile;
use App\Models\Shortlist;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

/**
 * Lightweight Profile shape for list screens.
 *
 * Used by dashboard carousels, search results, matches list, shortlist,
 * who-viewed, discover results, interest inbox. NOT used for the full
 * Profile View screen — that's ProfileResource (bigger).
 *
 * When passed an optional $viewer, the resource adds per-viewer context:
 *   - is_shortlisted (bool)
 *   - interest_status (enum or null)
 *   - match_score (int or null, only if pre-computed in cache)
 *
 * Without a viewer, those fields are null — useful for anonymous discover
 * results.
 *
 * UI-safe API contract points this class enforces:
 *   1. Timestamps → ISO 8601 (last_active_at)
 *   2. Booleans   → real bool (is_shortlisted)
 *   3. Arrays     → [] not null (badges always array, even if empty)
 *   4. Optional   → always present with null (match_score, interest_status)
 *   5. Photo URLs → delegated to PhotoResource (absolute via model accessor)
 *
 * Design references:
 *   - docs/mobile-app/reference/ui-safe-api-checklist.md
 *   - docs/mobile-app/design/04-profile-api.md §4.2
 */
class ProfileCardResource extends JsonResource
{
    /**
     * @param  Profile  $resource
     * @param  Profile|null  $viewer  The viewing profile (for is_shortlisted,
     *         interest_status, match_score, blurred-photo logic). Pass null
     *         for anonymous / public contexts.
     */
    public function __construct($resource, public ?Profile $viewer = null)
    {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        /** @var Profile $profile */
        $profile = $this->resource;

        return [
            'matri_id'          => (string) $profile->matri_id,
            'full_name'         => (string) ($profile->full_name ?? ''),
            'age'               => $this->computeAge($profile),
            'height_cm'         => $this->computeHeightCm($profile),
            'height_label'      => $profile->height ?: null,
            'religion'          => $profile->religiousInfo?->religion,
            'caste'             => $profile->religiousInfo?->caste,
            'native_state'      => $profile->locationInfo?->native_state,
            'occupation'        => $profile->educationDetail?->occupation,
            'education_short'   => $profile->educationDetail?->educational_qualification,
            'primary_photo'     => $this->primaryPhotoShape($profile),
            'badges'            => $this->computeBadges($profile),
            'last_active_at'    => $profile->user?->last_login_at?->toIso8601String(),
            'last_active_label' => $profile->user?->last_login_at?->diffForHumans(),
            'match_score'       => $this->cachedMatchScore($profile),
            'is_shortlisted'    => $this->isShortlistedBy($profile),
            'interest_status'   => $this->interestStatusWith($profile),
            'is_blocked'        => $this->isBlockedBy($profile),
        ];
    }

    /**
     * Age in whole years. Returns null if date_of_birth is missing or
     * in the future.
     */
    private function computeAge(Profile $profile): ?int
    {
        if (! $profile->date_of_birth) {
            return null;
        }

        $age = (int) $profile->date_of_birth->age;

        return $age >= 0 ? $age : null;
    }

    /**
     * Extract numeric cm from the height string.
     * Heights are stored as strings like "170 cm - 5 ft 07 inch" or "165 cm"
     * (from config/reference_data.php height_list).
     */
    private function computeHeightCm(Profile $profile): ?int
    {
        if (! $profile->height) {
            return null;
        }

        if (preg_match('/(\d+)\s*cm/', $profile->height, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Build the primary_photo sub-object (or null if no primary photo).
     * Delegates URL + blur logic to PhotoResource so we don't duplicate.
     *
     * @return array<string,mixed>|null
     */
    private function primaryPhotoShape(Profile $profile): ?array
    {
        $photo = $profile->primaryPhoto;
        if (! $photo) {
            return null;
        }

        $full = (new PhotoResource($photo, viewer: $this->viewer))->resolve();

        // Card variant exposes just the URLs + blur flag, not the full photo payload.
        return [
            'id'            => $full['id'],
            'thumbnail_url' => $full['thumbnail_url'],
            'medium_url'    => $full['medium_url'],
            'is_blurred'    => $full['is_blurred'],
        ];
    }

    /**
     * Badges are always an array (possibly empty) — never null.
     * Order is significant: diamond/vip/featured/premium before
     * verified/new so the most visually distinguishing badge is
     * typically shown first when the UI has limited badge slots.
     *
     * @return array<int,string>
     */
    private function computeBadges(Profile $profile): array
    {
        $badges = [];

        // Paid-tier badges (mutually exclusive in practice).
        // Only call isPremium() when userMemberships is preloaded — protects
        // against N+1 in list views and keeps unit tests DB-free. Controllers
        // that render lists must eager-load ->user->userMemberships.
        if ($profile->user
            && $profile->user->relationLoaded('userMemberships')
            && $profile->user->isPremium()) {
            $badges[] = 'premium';
        }

        if ($profile->is_vip ?? false) {
            $badges[] = 'vip';
        }

        if ($profile->is_featured ?? false) {
            $badges[] = 'featured';
        }

        // Trust badges
        if ($profile->is_verified ?? false) {
            $badges[] = 'verified';
        }

        // Recency
        if ($profile->created_at && $profile->created_at->diffInDays(now()) < 7) {
            $badges[] = 'new';
        }

        return $badges;
    }

    /**
     * Only return a match_score if one is already cached. Computing
     * match scores is O(12 criteria × Eloquent reads) per profile —
     * too expensive for list views. Individual profile view (step-5)
     * computes on-demand + caches.
     *
     * Cache key pattern: `match_score:{viewer_profile_id}:{target_profile_id}`
     */
    private function cachedMatchScore(Profile $profile): ?int
    {
        if (! $this->viewer) {
            return null;
        }

        $cached = Cache::get("match_score:{$this->viewer->id}:{$profile->id}");

        return is_int($cached) ? $cached : null;
    }

    /**
     * Has the viewer shortlisted this profile?
     * Returns false if no viewer in context.
     */
    private function isShortlistedBy(Profile $profile): bool
    {
        if (! $this->viewer) {
            return false;
        }

        return Shortlist::where('profile_id', $this->viewer->id)
            ->where('shortlisted_profile_id', $profile->id)
            ->exists();
    }

    /**
     * What's the interest relationship between viewer and this profile?
     *
     * Returns one of: 'sent', 'received', 'accepted', 'declined', 'expired'
     * or null (no interest, or no viewer in context).
     *
     * 'sent' means viewer → this profile (pending).
     * 'received' means this profile → viewer (pending).
     */
    private function interestStatusWith(Profile $profile): ?string
    {
        if (! $this->viewer) {
            return null;
        }

        $interest = Interest::where(function ($q) use ($profile) {
            $q->where([
                'sender_profile_id' => $this->viewer->id,
                'receiver_profile_id' => $profile->id,
            ])->orWhere([
                'sender_profile_id' => $profile->id,
                'receiver_profile_id' => $this->viewer->id,
            ]);
        })->latest()->first();

        if (! $interest) {
            return null;
        }

        if ($interest->status === 'accepted') return 'accepted';
        if ($interest->status === 'declined') return 'declined';
        if ($interest->status === 'expired') return 'expired';

        // pending — direction-aware
        return $interest->sender_profile_id === $this->viewer->id ? 'sent' : 'received';
    }

    /**
     * Has the viewer blocked this profile (or vice versa)?
     * Flutter uses this to decide whether to show "unblock" instead of
     * "send interest" CTA. Server-side access is also gated — see
     * ProfileAccessService (step-2 of this week).
     */
    private function isBlockedBy(Profile $profile): bool
    {
        if (! $this->viewer) {
            return false;
        }

        return BlockedProfile::where(function ($q) use ($profile) {
            $q->where([
                'blocker_profile_id' => $this->viewer->id,
                'blocked_profile_id' => $profile->id,
            ])->orWhere([
                'blocker_profile_id' => $profile->id,
                'blocked_profile_id' => $this->viewer->id,
            ]);
        })->exists();
    }
}
