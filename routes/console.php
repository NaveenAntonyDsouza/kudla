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

// "Finish your registration": at most 3 per member — an hour after they stop,
// then 4 and 10 days later. Hourly in the daytime only; only where
// registration_reminders_enabled = 1, max registration_reminders_daily_cap a day
Schedule::command('members:remind-incomplete', ['--scheduled', '--delay' => 3])->hourly()->between('8:00', '21:00')->withoutOverlapping();

// Read bounce notices in the site mailbox; stop emailing dead addresses
// (only where SiteSetting bounce_processing_enabled = 1)
Schedule::command('email:process-bounces')->dailyAt('02:45')->withoutOverlapping();

// Profile completion nudges — daily 19:00 (evening, when users check phones)
// Master switch: SiteSetting `profile_nudges_enabled`. Threshold: `profile_nudges_threshold_pct` (default 80).
Schedule::command('engagement:send-profile-nudges')->dailyAt('19:00')->withoutOverlapping();
