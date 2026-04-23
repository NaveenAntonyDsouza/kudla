<?php

namespace App\Services;

use App\Mail\Reengagement14DayMail;
use App\Mail\Reengagement30DayMail;
use App\Mail\Reengagement7DayMail;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * ReengagementService — finds inactive members and emails them the appropriate
 * escalation level (7-day, 14-day, or 30-day).
 *
 * Called by `engagement:send-reengagement` artisan command.
 *
 * Logic summary:
 *   1. Master kill switch: `reengagement_enabled` SiteSetting (default "1")
 *   2. Thresholds configurable via SiteSetting: `reengagement_threshold_days_1` (7),
 *      `..._2` (14), `..._3` (30).
 *   3. For each eligible user:
 *      - Determine which level they qualify for (highest matching)
 *      - Skip if they've already received that level
 *      - Send the corresponding mail
 *      - Update last_reengagement_sent_at + reengagement_level
 *   4. Eligibility already filtered by User::canReceiveReengagement()
 */
class ReengagementService
{
    /**
     * Run one pass — find eligible users and send them a re-engagement email.
     *
     * @param  bool  $dryRun  If true, doesn't send emails or update DB — just returns who would be sent
     * @return array{eligible: int, sent_by_level: array<int, int>, skipped: int, recipients: array}
     */
    public function run(bool $dryRun = false): array
    {
        if (!$this->isEnabled()) {
            return [
                'eligible' => 0,
                'sent_by_level' => [1 => 0, 2 => 0, 3 => 0],
                'skipped' => 0,
                'recipients' => [],
                'disabled' => true,
            ];
        }

        $thresholds = $this->getThresholds();
        $candidates = $this->findCandidates($thresholds);

        $sentByLevel = [1 => 0, 2 => 0, 3 => 0];
        $skipped = 0;
        $recipients = [];

        foreach ($candidates as $user) {
            if (!$user->canReceiveReengagement()) {
                $skipped++;
                continue;
            }

            $daysInactive = $user->daysInactive();
            $targetLevel = $this->levelForDays($daysInactive, $thresholds);

            // Don't send the same (or lower) level again
            if ($targetLevel === null || $targetLevel <= ($user->reengagement_level ?? 0)) {
                $skipped++;
                continue;
            }

            $recipients[] = [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'days_inactive' => $daysInactive,
                'level' => $targetLevel,
            ];

            if (!$dryRun) {
                try {
                    Mail::to($user->email)->send($this->mailForLevel($targetLevel, $user));

                    $user->update([
                        'last_reengagement_sent_at' => now(),
                        'reengagement_level' => $targetLevel,
                    ]);

                    $sentByLevel[$targetLevel]++;
                } catch (\Throwable $e) {
                    report($e);
                    $skipped++;
                }
            } else {
                $sentByLevel[$targetLevel]++;
            }
        }

        return [
            'eligible' => count($candidates),
            'sent_by_level' => $sentByLevel,
            'skipped' => $skipped,
            'recipients' => $recipients,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * Is the feature enabled? Controlled by SiteSetting for easy admin toggle.
     */
    public function isEnabled(): bool
    {
        return SiteSetting::getValue('reengagement_enabled', '1') === '1';
    }

    /**
     * Read thresholds from SiteSetting with sensible defaults.
     */
    public function getThresholds(): array
    {
        return [
            1 => (int) SiteSetting::getValue('reengagement_threshold_days_1', '7'),
            2 => (int) SiteSetting::getValue('reengagement_threshold_days_2', '14'),
            3 => (int) SiteSetting::getValue('reengagement_threshold_days_3', '30'),
        ];
    }

    /**
     * Given days inactive, which level email should they receive?
     * Returns the HIGHEST matching level (e.g., 35 days → level 3, not 1).
     * Returns null if inactive days below all thresholds.
     */
    public function levelForDays(int $daysInactive, array $thresholds): ?int
    {
        if ($daysInactive >= $thresholds[3]) return 3;
        if ($daysInactive >= $thresholds[2]) return 2;
        if ($daysInactive >= $thresholds[1]) return 1;
        return null;
    }

    /**
     * Find users who are CANDIDATES for re-engagement (before per-user eligibility check).
     * Pre-filters in SQL for performance; final canReceiveReengagement() check is per-user.
     */
    protected function findCandidates(array $thresholds): Collection
    {
        $minThreshold = min($thresholds);

        return User::query()
            ->whereNull('staff_role_id')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where(function (Builder $q) use ($minThreshold) {
                $q->where('last_login_at', '<=', now()->subDays($minThreshold))
                    ->orWhereNull('last_login_at');
            })
            // Skip users who received an email recently (rate limit — 6 days)
            ->where(function (Builder $q) {
                $q->whereNull('last_reengagement_sent_at')
                    ->orWhere('last_reengagement_sent_at', '<', now()->subDays(6));
            })
            ->get();
    }

    /**
     * Map a level (1/2/3) to the appropriate Mailable for a specific user.
     */
    protected function mailForLevel(int $level, User $user)
    {
        return match ($level) {
            1 => new Reengagement7DayMail($user),
            2 => new Reengagement14DayMail($user),
            3 => new Reengagement30DayMail($user),
            default => throw new \InvalidArgumentException("Unknown level: $level"),
        };
    }
}
