<?php

namespace App\Console\Commands;

use App\Models\SiteSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/**
 * Uptime alarm (weekly review, Oct 2026: kudla was down 45 minutes on 14 Oct
 * and nobody knew). Loads each homepage in `uptime_watch_urls`; one that fails
 * twice in a row (20 s apart) emails `uptime_alert_email`, and a second email
 * says when it is back. Run from a site on the OTHER server, so a server that
 * goes down can't silence its own alarm. Nothing to do when unset.
 */
class WatchSitesUp extends Command
{
    protected $signature = 'uptime:watch {--retry-after=20 : seconds before re-checking a failure}';

    protected $description = 'Email the owner when a watched site stops loading, and when it is back';

    public function handle(): int
    {
        $urls = preg_split('/[\s,]+/', (string) SiteSetting::getValue('uptime_watch_urls', ''), -1, PREG_SPLIT_NO_EMPTY);
        $to = trim((string) SiteSetting::getValue('uptime_alert_email', ''));
        if ($urls === [] || $to === '') {
            return self::SUCCESS;
        }
        // run from the scheduler and/or its own cron line: at most once in 4 minutes
        if (! Cache::lock('uptime-watch', 240)->get()) {
            return self::SUCCESS;
        }
        Cache::put('uptime-watch:last-run', now()->toIso8601String(), now()->addDay());

        foreach ($urls as $url) {
            $problem = $this->problem($url);
            if ($problem !== null) {
                sleep((int) $this->option('retry-after'));
                $problem = $this->problem($url);
            }

            $key = 'uptime-down:' . md5($url);
            $downSince = Cache::get($key);
            $host = parse_url($url, PHP_URL_HOST) ?: $url;

            if ($problem !== null && ! $downSince) {
                Cache::forever($key, now()->toIso8601String());
                $this->notifyOwner($to, "{$host} is DOWN", "{$url} is not loading ({$problem}).\n\nChecked at " . now()->format('d M Y, h:i A') . ". You'll get another email when it is back.");
            } elseif ($problem === null && $downSince) {
                Cache::forget($key);
                $minutes = (int) \Illuminate\Support\Carbon::parse($downSince)->diffInMinutes(now());
                $this->notifyOwner($to, "{$host} is back up", "{$url} is loading again (down for about {$minutes} minutes).");
            }
            $jobs = null;
            if ($problem === null) { // a site that is down can't say anything about its jobs
                $jobs = $this->jobsStoppedSince($url);
                $this->watchJobs($to, $url, $host, $jobs);
            }

            $this->line($host . ': ' . ($problem ?? 'ok') . ($jobs ? ", jobs stopped since {$jobs}" : ''));
        }

        return self::SUCCESS;
    }

    /** Hours of silence before scheduled jobs count as stopped (Bluehost's cron runs every 15 min). */
    private const JOBS_STOPPED_AFTER_HOURS = 2;

    /**
     * When the site's scheduled jobs (emails, reminders, error alerts) last ran,
     * if that was more than 2 hours ago; null when they're running, the site
     * never had them (no cron, e.g. GCM) or it can't say.
     */
    private function jobsStoppedSince(string $url): ?string
    {
        try {
            $origin = preg_replace('#^(https?://[^/]+).*$#', '$1', $url);
            $lastRun = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36 uptime-watch'])
                ->timeout(15)->get($origin . '/health/jobs')->json('last_run');
        } catch (\Throwable) {
            return null;
        }
        if (! is_string($lastRun) || $lastRun === '') {
            return null;
        }
        $at = \Illuminate\Support\Carbon::parse($lastRun);

        return $at->lt(now()->subHours(self::JOBS_STOPPED_AFTER_HOURS)) ? $at->timezone(config('app.timezone'))->format('d M, h:i A') : null;
    }

    private function watchJobs(string $to, string $url, string $host, ?string $stoppedSince): void
    {
        $key = 'uptime-jobs-stopped:' . md5($url);
        if ($stoppedSince !== null && ! Cache::get($key)) {
            Cache::forever($key, now()->toIso8601String());
            $this->notifyOwner($to, "{$host}: scheduled jobs stopped", "{$host} loads, but its scheduled jobs (emails, reminders, error alerts) last ran {$stoppedSince}.\n\nUsually the cron job on the hosting account was removed or is failing. You'll get another email when they run again.");
        } elseif ($stoppedSince === null && Cache::get($key)) {
            Cache::forget($key);
            $this->notifyOwner($to, "{$host}: scheduled jobs running again", "{$host}'s scheduled jobs are running again.");
        }
    }

    /** Null when the page loads, else a short reason. */
    private function problem(string $url): ?string
    {
        try {
            // a full browser User-Agent: Bluehost's mod_security refuses plain clients (406)
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36 uptime-watch',
                'Accept' => 'text/html',
            ])->timeout(25)->get($url);
        } catch (\Throwable $e) {
            return 'no answer: ' . class_basename($e);
        }

        if (! $response->successful()) {
            return 'error ' . $response->status();
        }

        return str_contains($response->body(), '</html>') ? null : 'page incomplete';
    }

    private function notifyOwner(string $to, string $subject, string $body): void
    {
        try {
            Mail::raw($body, fn ($m) => $m->to($to)->subject($subject));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
