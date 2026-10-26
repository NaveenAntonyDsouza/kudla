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
            $this->line($host . ': ' . ($problem ?? 'ok'));
        }

        return self::SUCCESS;
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
