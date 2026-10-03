<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Membership expiry reminders — run daily at 8 AM
Schedule::command('membership:expiry-reminders')->dailyAt('08:00');

// Re-engagement emails to inactive members — run daily at 9 AM
// Master switch lives in SiteSetting `reengagement_enabled` so admin can pause.
Schedule::command('engagement:send-reengagement')->dailyAt('09:00')->withoutOverlapping();

// Weekly match suggestions — Sunday 10 AM (weekends are peak browse time for matrimony)
// Master switch: SiteSetting `weekly_matches_enabled`. Count: `weekly_matches_count` (default 5).
Schedule::command('engagement:send-weekly-matches')->weeklyOn(0, '10:00')->withoutOverlapping();

// Interests waiting for a reply — daily 11:00 (unanswered 3+ days; each member at most weekly)
// Master switch: SiteSetting `interest_reminders_enabled`. Cap: `interest_reminders_daily_cap` (default 30).
Schedule::command('engagement:send-interest-reminders')->dailyAt('11:00')->withoutOverlapping();

// Unfinished payments — hourly, 09:00–21:00 (started 2–72 h ago; each member at most weekly)
// Master switch: SiteSetting `payment_reminders_enabled`. Cap: `payment_reminders_run_cap` (default 20).
Schedule::command('engagement:send-payment-reminders')->hourly()->between('9:00', '21:00')->withoutOverlapping();

// Email Activity log: keep 180 days (EmailLog::prunable)
Schedule::command('model:prune', ['--model' => [\App\Models\EmailLog::class]])->dailyAt('03:30');

// Profile completion nudges — daily 19:00 (evening, when users check phones)
// Master switch: SiteSetting `profile_nudges_enabled`. Threshold: `profile_nudges_threshold_pct` (default 80).
Schedule::command('engagement:send-profile-nudges')->dailyAt('19:00')->withoutOverlapping();
