<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * ProfileNudgeService — finds members with incomplete profiles and sends in-app
 * nudges directing them to the most impactful missing section.
 *
 * Called by `engagement:send-profile-nudges` artisan command (daily 19:00 IST).
 *
 * Eligibility:
 *   - Non-staff active member
 *   - Completed onboarding (past registration steps 1-5)
 *   - Profile completion < 80% (configurable: `profile_nudges_threshold_pct`)
 *   - Not deep-inactive (logged in within 30 days)
 *   - Rate limit: 1 nudge per 7 days
 *   - Lifetime cap: max 4 nudges ever (prevents perma-spam)
 *
 * Nudge selection:
 *   - For each eligible user, pick the highest-impact missing section
 *   - Create in-app notification (Notification model) via NotificationService
 *   - Type = 'system', data['nudge_type'] = section key for differentiation
 *
 * Master kill switch: SiteSetting `profile_nudges_enabled` (default "1")
 */
class ProfileNudgeService
{
    public const TYPE = 'system';

    public function __construct(
        protected ProfileCompletionService $completionService,
        protected NotificationService $notifications,
    ) {}

    /**
     * Run one pass — identify + send nudges.
     *
     * @param  bool  $dryRun  If true: no DB writes, just report
     * @return array
     */
    public function run(bool $dryRun = false): array
    {
        if (!$this->isEnabled()) {
            return [
                'eligible' => 0, 'sent' => 0,
                'skipped_no_missing' => 0, 'skipped_other' => 0,
                'recipients' => [], 'disabled' => true,
            ];
        }

        $threshold = $this->getThreshold();
        $candidates = $this->findCandidates();

        $sent = 0;
        $skippedNoMissing = 0;
        $skippedOther = 0;
        $recipients = [];

        foreach ($candidates as $user) {
            if (!$user->canReceiveNudge()) {
                $skippedOther++;
                continue;
            }

            $profile = $user->profile;
            if (!$profile) {
                $skippedOther++;
                continue;
            }

            // Compute fresh completion
            $pct = $this->completionService->calculate($profile);
            if ($pct >= $threshold) {
                // Profile is "done enough", no nudge needed
                $skippedNoMissing++;
                continue;
            }

            // Pick the highest-impact missing section
            $section = $this->completionService->pickTopMissingSection($profile);
            if (!$section) {
                $skippedNoMissing++;
                continue;
            }

            $recipients[] = [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'completion_pct' => $pct,
                'nudge_type' => $section['key'],
                'nudge_title' => $section['nudge_title'],
                'sent_count' => ($user->nudges_sent_count ?? 0) + 1,
            ];

            if (!$dryRun) {
                try {
                    $this->createNudgeNotification($user, $section, $pct);
                    $user->update([
                        'last_nudge_sent_at' => now(),
                        'nudges_sent_count' => ($user->nudges_sent_count ?? 0) + 1,
                    ]);
                    $sent++;
                } catch (\Throwable $e) {
                    report($e);
                    $skippedOther++;
                }
            } else {
                $sent++;
            }
        }

        return [
            'eligible' => $candidates->count(),
            'sent' => $sent,
            'skipped_no_missing' => $skippedNoMissing,
            'skipped_other' => $skippedOther,
            'recipients' => $recipients,
            'dry_run' => $dryRun,
        ];
    }

    public function isEnabled(): bool
    {
        return SiteSetting::getValue('profile_nudges_enabled', '1') === '1';
    }

    public function getThreshold(): int
    {
        return (int) SiteSetting::getValue('profile_nudges_threshold_pct', '80');
    }

    /**
     * SQL pre-filter — reduces candidate set before per-user eligibility check.
     */
    protected function findCandidates(): Collection
    {
        return User::query()
            ->whereNull('staff_role_id')
            ->where('is_active', true)
            // Active within the last 30 days (else they're in re-engagement flow)
            ->where('last_login_at', '>=', now()->subDays(30))
            // Under lifetime cap
            ->where('nudges_sent_count', '<', 4)
            // Rate limit — not nudged in last 7 days
            ->where(function (Builder $q) {
                $q->whereNull('last_nudge_sent_at')
                    ->orWhere('last_nudge_sent_at', '<', now()->subDays(7));
            })
            // Must have a profile with completed onboarding
            ->whereHas('profile', fn (Builder $q) => $q->where('onboarding_completed', true))
            ->with(['profile.partnerPreference', 'profile.familyDetail', 'profile.educationDetail',
                    'profile.locationInfo', 'profile.contactInfo', 'profile.lifestyleInfo',
                    'profile.religiousInfo'])
            ->get();
    }

    /**
     * Create the Notification row via NotificationService.
     */
    protected function createNudgeNotification(User $user, array $section, int $pct): void
    {
        $title = $section['nudge_title'];
        $message = $section['nudge_message'] . " (Your profile is {$pct}% complete.)";

        $this->notifications->send(
            $user,
            self::TYPE,
            $title,
            $message,
            null, // no profile_id — this is a self-referential system nudge
            [
                'nudge_type' => $section['key'],
                'cta_url_path' => $section['cta_url_path'],
                'completion_pct' => $pct,
                'section_label' => $section['label'],
            ]
        );
    }
}
