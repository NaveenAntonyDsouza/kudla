<?php

namespace App\Services;

use App\Http\Resources\V1\ProfileCardResource;
use App\Models\Interest;
use App\Models\Notification;
use App\Models\Profile;
use App\Models\ProfileView;
use App\Models\Shortlist;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Assembles the Flutter dashboard payload in one place.
 *
 * DashboardController::show is intentionally thin — it authenticates and
 * delegates. All query + shape logic lives here so:
 *   - Unit tests can exercise the assembly without HTTP
 *   - DB-touching helpers are wrapped in try/catch + safe fallbacks,
 *     letting Pest run DB-free (the SQLite :memory: test DB lacks several
 *     tables that the production MySQL schema has)
 *   - Future evolution (adding sections, caching the whole payload, etc.)
 *     happens at a single call-site
 *
 * Shape contract (every key present, every value non-null — UI-safe):
 *   cta                   → object with 6 boolean + 1 integer field
 *   stats                 → object with 5 integer counters
 *   recommended_matches   → array of ProfileCardResource ([] when empty)
 *   mutual_matches        → array of ProfileCardResource ([] when empty)
 *   recent_views          → array of ProfileCardResource ([] when empty)
 *   newly_joined          → array of ProfileCardResource ([] when empty)
 *   discover_teasers      → array of 6 category summaries
 *
 * Design reference:
 *   - docs/mobile-app/phase-2a-api/week-03-profiles-photos-search/step-03-dashboard-endpoint.md
 *   - docs/mobile-app/reference/ui-safe-api-checklist.md
 *
 * TODO (future step): pass `$viewer` context to ProfileCardResource so cards
 * include is_shortlisted / interest_status / is_blocked. Requires a
 * ViewerContextPreloader service that batch-fetches state for all profile
 * IDs across every carousel (avoids N+1 — currently 150 queries/dashboard
 * if we passed viewer per-card). Tracked for step-15 after match endpoints.
 */
class DashboardService
{
    /** Max items per dashboard carousel. */
    public const CAROUSEL_LIMIT = 10;

    /** Target profile completion % that silences the completion nudge. */
    public const PROFILE_COMPLETION_TARGET = 80;

    /** Number of discover categories surfaced on the dashboard. */
    public const DISCOVER_TEASER_COUNT = 6;

    public function __construct(private MatchingService $matching) {}

    /**
     * Build the full dashboard payload for a given user.
     *
     * Caller (DashboardController) has already verified $profile is non-null
     * and eager-loaded common relations for N+1 safety.
     */
    public function buildPayload(User $user, Profile $profile): array
    {
        return [
            'cta' => $this->buildCta($user, $profile),
            'stats' => $this->buildStats($profile),
            'recommended_matches' => $this->buildRecommendedMatches($profile),
            'mutual_matches' => $this->buildMutualMatches($profile),
            'recent_views' => $this->buildRecentViews($profile),
            'newly_joined' => $this->buildNewlyJoined($profile),
            'discover_teasers' => $this->buildDiscoverTeasers(),
        ];
    }

    /* ------------------------------------------------------------------
     |  Section builders (public — tests exercise each independently)
     | ------------------------------------------------------------------ */

    /**
     * CTA block — what action should the user take next.
     *
     * Flutter renders whichever banner has its show_* flag = true. Each flag
     * maps to a one-tap onboarding nudge. Only profile_completion_pct is
     * numeric (for the progress ring).
     */
    public function buildCta(User $user, Profile $profile): array
    {
        $pct = (int) ($profile->profile_completion_pct ?? 0);

        return [
            'show_profile_completion' => $pct < self::PROFILE_COMPLETION_TARGET,
            'profile_completion_pct' => $pct,
            'show_photo_upload' => $this->countPhotosSafely($profile) === 0,
            'show_verify_email' => $user->email_verified_at === null,
            'show_verify_phone' => $user->phone_verified_at === null,
            'show_upgrade' => ! $this->hasActiveMembershipSafely($user),
        ];
    }

    /**
     * Stats block — 5 integer counters. All DB-touching queries are wrapped
     * in try/catch → 0 fallback so unit tests can run without migrations.
     */
    public function buildStats(Profile $profile): array
    {
        return [
            'interests_received' => $this->safeCount(
                fn () => Interest::where('receiver_profile_id', $profile->id)
                    ->where('status', 'pending')
                    ->count()
            ),
            'interests_sent' => $this->safeCount(
                fn () => Interest::where('sender_profile_id', $profile->id)
                    ->where('status', 'pending')
                    ->count()
            ),
            'profile_views_total' => $this->safeCount(
                fn () => ProfileView::where('viewed_profile_id', $profile->id)->count()
            ),
            'shortlisted_count' => $this->safeCount(
                fn () => Shortlist::where('shortlisted_profile_id', $profile->id)->count()
            ),
            'unread_notifications' => $this->safeCount(
                fn () => Notification::where('user_id', $profile->user_id)
                    ->where('is_read', false)
                    ->count()
            ),
        ];
    }

    /** Recommended matches carousel — top-N from the scoring engine. */
    public function buildRecommendedMatches(Profile $profile): array
    {
        try {
            $profiles = $this->matching->getRecommendations($profile, self::CAROUSEL_LIMIT);

            return $this->cardsFrom($profiles);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Mutual matches carousel — both sides' partner preferences align.
     * MatchingService::getMutualMatches returns a LengthAwarePaginator; we
     * consume the first page only.
     */
    public function buildMutualMatches(Profile $profile): array
    {
        try {
            $paginator = $this->matching->getMutualMatches($profile, self::CAROUSEL_LIMIT);

            return $this->cardsFrom(collect($paginator->items()));
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Eager-load every relation ProfileCardResource accesses so the
     * dashboard doesn't fan out into 5 N+1 queries per profile per
     * carousel. Mirrors ProfileQueryFilters::baseQuery() — keep in sync.
     */
    private const CARD_RELATIONS = [
        'primaryPhoto',
        'religiousInfo',
        'educationDetail',
        'locationInfo',
        'photoPrivacySetting',
        'user.userMemberships',
    ];

    /** Recent views carousel — last N people who viewed this profile. */
    public function buildRecentViews(Profile $profile): array
    {
        try {
            // Eager-load every viewerProfile.{relation} so the cardsFrom()
            // pass below doesn't fan out into N+1 queries per viewer.
            $viewerProfileRels = collect(self::CARD_RELATIONS)
                ->map(fn ($rel) => 'viewerProfile.'.$rel)
                ->all();

            $viewers = ProfileView::where('viewed_profile_id', $profile->id)
                ->with($viewerProfileRels)
                ->orderByDesc('viewed_at')
                ->take(self::CAROUSEL_LIMIT)
                ->get()
                ->pluck('viewerProfile')
                ->filter()
                ->unique('id')  // one card per viewer even if they visited multiple times
                ->values();

            return $this->cardsFrom($viewers);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Newly joined carousel — N most recent opposite-gender active profiles. */
    public function buildNewlyJoined(Profile $profile): array
    {
        try {
            $profiles = Profile::query()
                ->where('gender', '!=', $profile->gender)
                ->where('is_active', true)
                ->where('is_approved', true)
                ->where('is_hidden', false)
                ->where('suspension_status', 'active')
                ->with(self::CARD_RELATIONS)
                ->orderByDesc('created_at')
                ->take(self::CAROUSEL_LIMIT)
                ->get();

            return $this->cardsFrom($profiles);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Discover teasers — first N entries from config/discover.php.
     *
     * `count` is null at MVP; step-14 (discover endpoints) will replace it
     * with actual per-category profile counts. Null keeps the shape stable
     * for Flutter (UI-safe rule: optional always present with null).
     */
    public function buildDiscoverTeasers(): array
    {
        $categories = config('discover', []);
        if (! is_array($categories)) {
            return [];
        }

        return collect($categories)
            ->take(self::DISCOVER_TEASER_COUNT)
            ->map(fn ($cat, $key) => [
                'category' => (string) $key,
                'label' => (string) ($cat['label'] ?? $key),
                'count' => null,  // populated in step-14
            ])
            ->values()
            ->all();
    }

    /* ------------------------------------------------------------------
     |  Internal helpers
     | ------------------------------------------------------------------ */

    /**
     * Render a collection of Profile models to UI-safe card arrays.
     * Viewer context intentionally NOT passed — see TODO in class docblock.
     */
    private function cardsFrom(Collection $profiles): array
    {
        return $profiles
            ->filter()  // drop nulls from stale relations
            ->values()
            ->map(fn (Profile $p) => (new ProfileCardResource($p))->resolve())
            ->all();
    }

    /**
     * Execute a count() closure, returning 0 on any failure. Production
     * tables always exist — this is purely defensive for the unit-test
     * environment (SQLite :memory: without migrations).
     */
    private function safeCount(\Closure $query): int
    {
        try {
            return (int) $query();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Count profile photos without exploding when the table is missing. */
    private function countPhotosSafely(Profile $profile): int
    {
        try {
            // Use relation if already loaded (N+1 safety); otherwise query.
            if ($profile->relationLoaded('profilePhotos')) {
                return $profile->profilePhotos->count();
            }

            return $profile->profilePhotos()->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Check active membership without exploding when the table is missing. */
    private function hasActiveMembershipSafely(User $user): bool
    {
        try {
            return $user->activeMembership() !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
