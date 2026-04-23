<?php

namespace App\Services;

use App\Mail\WeeklyMatchSuggestionsMail;
use App\Models\Profile;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * WeeklyMatchSuggestionsService — finds members eligible for a weekly match digest
 * and emails them their top N recommendations via MatchingService::getRecommendations().
 *
 * Called by `engagement:send-weekly-matches` artisan command (weekly Sunday 10 AM).
 *
 * Logic:
 *   1. Master kill switch: `weekly_matches_enabled` SiteSetting (default "1")
 *   2. Match count: `weekly_matches_count` SiteSetting (default 5)
 *   3. Min match score: only matches with score >= 40 ('partial' badge or higher)
 *   4. For each eligible user:
 *      - Load their profile + partner preference
 *      - Call MatchingService::getRecommendations() with a limit buffer (× 2)
 *      - Filter to only matches meeting min score
 *      - Skip user if no qualifying matches (don't send empty email)
 *      - Send mail and update last_weekly_match_sent_at
 */
class WeeklyMatchSuggestionsService
{
    public function __construct(protected MatchingService $matcher)
    {
    }

    /**
     * Run one pass — send weekly match digest emails to all eligible users.
     *
     * @param  bool  $dryRun  If true, no emails sent, no DB updates
     * @return array{eligible: int, sent: int, skipped_no_matches: int, skipped_other: int, recipients: array}
     */
    public function run(bool $dryRun = false): array
    {
        if (!$this->isEnabled()) {
            return [
                'eligible' => 0, 'sent' => 0, 'skipped_no_matches' => 0,
                'skipped_other' => 0, 'recipients' => [], 'disabled' => true,
            ];
        }

        $matchCount = $this->getMatchCount();
        $minScore = $this->getMinScore();
        $candidates = $this->findCandidates();

        $sent = 0;
        $skippedNoMatches = 0;
        $skippedOther = 0;
        $recipients = [];

        foreach ($candidates as $user) {
            if (!$user->canReceiveWeeklyMatches()) {
                $skippedOther++;
                continue;
            }

            if (!$user->profile) {
                $skippedOther++;
                continue;
            }

            // Fetch more matches than needed, then filter by score threshold
            $matches = $this->matcher
                ->getRecommendations($user->profile, $matchCount * 2)
                ->filter(fn ($m) => ($m->match_score ?? 0) >= $minScore)
                ->take($matchCount)
                ->values();

            if ($matches->isEmpty()) {
                $skippedNoMatches++;
                continue;
            }

            // Pre-load photos for email embedding
            $matches->loadMissing(['primaryPhoto']);

            $recipients[] = [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'match_count' => $matches->count(),
                'top_score' => $matches->first()->match_score ?? 0,
            ];

            if (!$dryRun) {
                try {
                    Mail::to($user->email)->send(new WeeklyMatchSuggestionsMail($user, $matches));
                    $user->update(['last_weekly_match_sent_at' => now()]);
                    $sent++;
                } catch (\Throwable $e) {
                    report($e);
                    $skippedOther++;
                }
            } else {
                $sent++; // count as "would send" in dry run
            }
        }

        return [
            'eligible' => $candidates->count(),
            'sent' => $sent,
            'skipped_no_matches' => $skippedNoMatches,
            'skipped_other' => $skippedOther,
            'recipients' => $recipients,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * Master on/off switch.
     */
    public function isEnabled(): bool
    {
        return SiteSetting::getValue('weekly_matches_enabled', '1') === '1';
    }

    /**
     * How many matches per email (admin-configurable).
     */
    public function getMatchCount(): int
    {
        return max(1, (int) SiteSetting::getValue('weekly_matches_count', '5'));
    }

    /**
     * Minimum match score to include (40 = "partial" threshold).
     */
    public function getMinScore(): int
    {
        return (int) SiteSetting::getValue('weekly_matches_min_score', '40');
    }

    /**
     * Find users that MAY be eligible (SQL pre-filter). Final per-user check is
     * done via canReceiveWeeklyMatches() + partner preferences + match results.
     */
    protected function findCandidates(): Collection
    {
        return User::query()
            ->whereNull('staff_role_id')
            ->where('is_active', true)
            ->whereNotNull('email')
            // Logged in within the last 60 days (else they're in re-engagement flow)
            ->where(function (Builder $q) {
                $q->where('last_login_at', '>=', now()->subDays(60))
                    ->orWhereNull('last_login_at'); // Allow users who never logged in
            })
            // Not recently sent (5-day rate limit)
            ->where(function (Builder $q) {
                $q->whereNull('last_weekly_match_sent_at')
                    ->orWhere('last_weekly_match_sent_at', '<', now()->subDays(5));
            })
            // Must have a profile with a partner preference (otherwise no matches)
            ->whereHas('profile.partnerPreference')
            ->with(['profile.partnerPreference'])
            ->get();
    }
}
