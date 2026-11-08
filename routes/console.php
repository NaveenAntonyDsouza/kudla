<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Support\ScheduledCommand;

// Commands are added through ScheduledCommand::add(): on servers that disable
// proc_open (Hostinger since 7 Nov 2026) it runs them inside this PHP process.

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Membership expiry reminders — run daily at 8 AM
ScheduledCommand::add('membership:expiry-reminders')->dailyAt('08:00');

// Re-engagement emails to inactive members — run daily at 9 AM
// Master switch lives in SiteSetting `reengagement_enabled` so admin can pause.
ScheduledCommand::add('engagement:send-reengagement')->dailyAt('09:00')->withoutOverlapping();

// Weekly match suggestions — Sunday 10 AM (weekends are peak browse time for matrimony)
// Master switch: SiteSetting `weekly_matches_enabled`. Count: `weekly_matches_count` (default 5).
ScheduledCommand::add('engagement:send-weekly-matches')->weeklyOn(0, '10:00')->withoutOverlapping();

// Interests waiting for a reply — daily 11:00 (unanswered 3+ days; each member at most weekly)
// Master switch: SiteSetting `interest_reminders_enabled`. Cap: `interest_reminders_daily_cap` (default 30).
ScheduledCommand::add('engagement:send-interest-reminders')->dailyAt('11:00')->withoutOverlapping();

// Unfinished payments — hourly, 09:00–21:00 (started 2–72 h ago; each member at most weekly)
// Master switch: SiteSetting `payment_reminders_enabled`. Cap: `payment_reminders_run_cap` (default 20).
ScheduledCommand::add('engagement:send-payment-reminders')->hourly()->between('9:00', '21:00')->withoutOverlapping();

// Email Activity log: keep 180 days (EmailLog::prunable)
ScheduledCommand::add('model:prune', ['--model' => [\App\Models\EmailLog::class]])->dailyAt('03:30');

// "Finish your registration": at most 3 per member — an hour after they stop,
// then 4 and 10 days later. Hourly in the daytime only; only where
// registration_reminders_enabled = 1, max registration_reminders_daily_cap a day
ScheduledCommand::add('members:remind-incomplete', ['--scheduled', '--delay' => 3])->hourly()->between('8:00', '21:00')->withoutOverlapping();

// "Are you still looking?" to members inactive 90+ days; hide from search those
// inactive 180+ days who didn't answer (only where dormant_profiles_enabled = 1)
ScheduledCommand::add('members:dormant', ['--scheduled'])->dailyAt('10:30')->withoutOverlapping();

// Read bounce notices in the site mailbox; stop emailing dead addresses
// (only where SiteSetting bounce_processing_enabled = 1)
ScheduledCommand::add('email:process-bounces')->dailyAt('02:45')->withoutOverlapping();

// Profile completion nudges — daily 19:00 (evening, when users check phones)
// Master switch: SiteSetting `profile_nudges_enabled`. Threshold: `profile_nudges_threshold_pct` (default 80).
ScheduledCommand::add('engagement:send-profile-nudges')->dailyAt('19:00')->withoutOverlapping();

// Weekly "N members viewed your profile" — Wednesdays 19:00 (weekly matches go Sundays);
// only where profile_views_digest_enabled = 1
ScheduledCommand::add('engagement:send-profile-views-digest', ['--scheduled'])->weeklyOn(3, '19:00')->withoutOverlapping();

// "Add a photo": at most 3 per member — 2, 7 and 21 days apart; daily 18:30;
// only where photo_reminders_enabled = 1, max photo_reminders_daily_cap a day
ScheduledCommand::add('members:remind-photo', ['--scheduled', '--delay' => 3])->dailyAt('18:30')->withoutOverlapping();

// Interests unanswered for 30 days expire; each sender gets one in-app note
// (only where interest_expiry_enabled = 1)
ScheduledCommand::add('interests:expire', ['--scheduled'])->dailyAt('07:30')->withoutOverlapping();
// privacy policy: personal data erased 90 days after "Delete Account" (App\Services\MemberErasure)
ScheduledCommand::add('members:erase-deleted')->dailyAt('03:15')->withoutOverlapping();

// Nightly database backup to storage/backups (14 daily + 6 monthly), before the erasure
// above; copied to the other hosting account at 04:00 (db:offsite-copy, below).
// A failure or a missing backup is logged as an error → the hourly error alert emails.
ScheduledCommand::add('db:backup')->dailyAt('02:30')->withoutOverlapping(60);
ScheduledCommand::add('db:backup', ['--check'])->dailyAt('09:15');
// copies them to the other hosting account; runs only where offsite_backup_script is set
ScheduledCommand::add('db:offsite-copy')->dailyAt('04:00')->withoutOverlapping(60);

// Uptime alarm: emails the owner when a site on the other server stops loading
// (site settings uptime_watch_urls + uptime_alert_email; nothing when unset)
ScheduledCommand::add('uptime:watch')->everyFiveMinutes()->withoutOverlapping(10);

// Homepage "Browse profiles by" links (Garland / Harmony designs): rebuilt in the
// background so no visitor waits for the ~600 member counts behind them
Schedule::call(fn () => \App\Support\HomeBrowse::refresh())->hourly()->name('home-browse-refresh')->withoutOverlapping();

// Silent-failure alarm: new errors in the log (and 5+ wrong staff passwords in an hour)
// → one email to error_alert_email / uptime_alert_email; the same error at most daily
ScheduledCommand::add('alerts:site-errors')->hourly()->withoutOverlapping();

// "Scheduled jobs are running" stamp, read by /health/jobs: the uptime alarm on the
// other server warns when it stops moving (cron removed, PHP broken, account suspended)
Schedule::call(fn () => \Illuminate\Support\Facades\Cache::forever('scheduler:last-run', now()->toIso8601String()))->everyFiveMinutes()->name('scheduler-heartbeat');
