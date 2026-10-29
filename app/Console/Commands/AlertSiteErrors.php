<?php

namespace App\Console\Commands;

use App\Models\SiteSetting;
use App\Support\AdminLoginTrail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Silent-failure alarm (audit, 29 Oct 2026: ID upload failed on all six
 * sites for four months and nobody knew). Once an hour, reads the error
 * log written since the last run and emails the owner one summary of any
 * error not already reported in the last 24 hours, plus staff accounts with
 * 5+ wrong passwords in the hour, and (at most daily) a site whose last
 * off-site backup is over 3 days old. Quiet when nothing is new. Goes to
 * `error_alert_email`, else the uptime alarm's `uptime_alert_email`.
 */
class AlertSiteErrors extends Command
{
    protected $signature = 'alerts:site-errors {--dry-run : print the email instead of sending it}';

    protected $description = 'Email the owner a summary of new errors in the site log';

    /** Read at most this much of the end of the log per run. */
    private const TAIL_BYTES = 2_000_000;

    private const REPEAT_AFTER_HOURS = 24;

    private const FAILED_LOGINS_ALERT = 5;

    public const BACKUP_STAMP = 'offsite-backup-at.txt';

    private const BACKUP_STALE_AFTER_DAYS = 3;

    /** Tests point this at their own file. */
    public static ?string $logPath = null;

    public function handle(): int
    {
        $to = trim((string) (SiteSetting::getValue('error_alert_email', '') ?: SiteSetting::getValue('uptime_alert_email', '')));

        $since = Cache::get('site-errors:last-scan') ?? now()->subHour()->format('Y-m-d H:i:s');
        $scannedTo = now()->format('Y-m-d H:i:s');

        $errors = collect(self::errorsSince($since))
            ->reject(fn ($e) => Cache::has('site-errors:told:' . $e['signature']));
        $failedLogins = $this->failedStaffLogins();
        $backupAt = Cache::has('site-errors:told:backup') ? null : self::staleBackupAt();

        if ($errors->isEmpty() && $failedLogins === [] && $backupAt === null) {
            Cache::forever('site-errors:last-scan', $scannedTo);
            $this->line('nothing new');

            return self::SUCCESS;
        }

        $site = SiteSetting::getValue('site_name', config('app.name'));
        $subject = $site . ': ' . collect([
            $errors->isNotEmpty() ? $errors->count() . ' new ' . str('error')->plural($errors->count()) : null,
            $failedLogins !== [] ? 'wrong admin passwords' : null,
            $backupAt !== null ? 'no backup since ' . $backupAt->format('j M') : null,
        ])->filter()->join(' and ');
        $body = $this->body($site, $errors->all(), $failedLogins, $backupAt);

        if ($this->option('dry-run') || $to === '') {
            $this->line($to === '' ? '(no alert email set)' : "To: {$to}");
            $this->line("Subject: {$subject}\n\n{$body}");
            if ($to === '' && ! $this->option('dry-run')) {
                Cache::forever('site-errors:last-scan', $scannedTo);
            }

            return self::SUCCESS;
        }

        try {
            Mail::raw($body, fn ($m) => $m->to($to)->subject($subject));
        } catch (\Throwable $e) {
            // warning, not error: this alarm must not report its own failure as a new error;
            // the scan point stays put so the next run tries again
            logger()->warning('Error alert not sent: ' . $e->getMessage());

            return self::FAILURE;
        }

        foreach ($errors as $e) {
            Cache::put('site-errors:told:' . $e['signature'], 1, now()->addHours(self::REPEAT_AFTER_HOURS));
        }
        if ($backupAt !== null) {
            Cache::put('site-errors:told:backup', 1, now()->addHours(self::REPEAT_AFTER_HOURS));
        }
        Cache::forever('site-errors:last-scan', $scannedTo);
        $this->line("sent to {$to}: {$subject}");

        return self::SUCCESS;
    }

    /**
     * ERROR-or-worse log entries after $since ("Y-m-d H:i:s", the log's own
     * clock), grouped by message with numbers blanked out, so 40 timeouts
     * make one line: [signature, message, count, first, last].
     */
    public static function errorsSince(string $since): array
    {
        $file = self::logFile();
        if ($file === null) {
            return [];
        }

        $handle = fopen($file, 'r');
        $size = filesize($file);
        if ($size > self::TAIL_BYTES) {
            fseek($handle, $size - self::TAIL_BYTES);
            fgets($handle); // skip the partial first line
        }

        $groups = [];
        while (($line = fgets($handle)) !== false) {
            if (! preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY): (.*)/', $line, $m) || $m[1] <= $since) {
                continue;
            }
            // the message, without the exception context Laravel appends
            $message = trim(preg_replace('/ \{"(exception|userId)".*$/s', '', $m[3]));
            $message = mb_strimwidth($message, 0, 300, '…');
            $signature = md5(preg_replace('/\d+/', '#', mb_substr($message, 0, 160)));

            $groups[$signature] ??= ['signature' => $signature, 'message' => $message, 'count' => 0, 'first' => $m[1], 'last' => $m[1]];
            $groups[$signature]['count']++;
            $groups[$signature]['last'] = $m[1];
        }
        fclose($handle);

        return array_values($groups);
    }

    /** The log being written to now: laravel.log, or the newest daily file. */
    private static function logFile(): ?string
    {
        if (self::$logPath !== null) {
            return is_file(self::$logPath) ? self::$logPath : null;
        }
        $files = glob(storage_path('logs/laravel*.log')) ?: [];
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        return $files[0] ?? null;
    }

    /** Staff accounts with 5+ wrong passwords in the last hour: [name => count]. */
    private function failedStaffLogins(): array
    {
        try {
            return DB::table('login_history')
                ->join('users', 'users.id', '=', 'login_history.user_id')
                ->where('login_method', AdminLoginTrail::WRONG_PASSWORD)
                ->where('logged_in_at', '>=', now()->subHour())
                ->groupBy('users.id', 'users.name', 'users.email')
                ->havingRaw('count(*) >= ?', [self::FAILED_LOGINS_ALERT])
                ->selectRaw('users.name, users.email, count(*) as n')
                ->get()
                ->mapWithKeys(fn ($r) => ["{$r->name} ({$r->email})" => (int) $r->n])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * When the last off-site backup reached the owner's PC, if over 3 days ago.
     * The daily backup (D:\matrimony\backups\auto\backup-all.sh) stamps
     * storage/app/offsite-backup-at.txt after each whole database copy; a site
     * without the stamp isn't backed up that way and says nothing.
     */
    public static function staleBackupAt(): ?\Illuminate\Support\Carbon
    {
        $stamp = storage_path('app/' . self::BACKUP_STAMP);
        if (! is_file($stamp)) {
            return null;
        }
        try {
            $at = \Illuminate\Support\Carbon::parse(trim((string) file_get_contents($stamp)))->timezone(config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }

        return $at->lt(now()->subDays(self::BACKUP_STALE_AFTER_DAYS)) ? $at : null;
    }

    private function body(string $site, array $errors, array $failedLogins, ?\Illuminate\Support\Carbon $backupAt = null): string
    {
        $lines = [];
        if ($errors !== []) {
            $lines[] = "New errors on {$site} (" . config('app.url') . "):";
            foreach ($errors as $e) {
                $when = $e['count'] > 1 ? "{$e['count']} times, {$e['first']} to {$e['last']}" : $e['first'];
                $lines[] = "\n- {$e['message']}\n  {$when}";
            }
            $lines[] = "\nThe full details are in the admin panel: Settings → System Health → recent log. The same error is reported again only after "
                . self::REPEAT_AFTER_HOURS . ' hours.';
        }
        if ($failedLogins !== []) {
            $lines[] = ($errors !== [] ? "\n\n" : '') . 'Wrong passwords typed for staff accounts in the last hour:';
            foreach ($failedLogins as $who => $n) {
                $lines[] = "- {$who}: {$n} times";
            }
            $lines[] = "\nIf that wasn't them, change the password. Settings → Login History shows where the attempts came from.";
        }
        if ($backupAt !== null) {
            $lines[] = ($lines !== [] ? "\n\n" : '') . 'The last off-site backup of this site reached your PC on ' . $backupAt->format('j M Y, h:i A') . '.'
                . "\nThe daily backup runs on your PC at 2 pm, or when the PC is next switched on. Check that the PC is on, and see D:\\matrimony\\backups\\auto\\backup-log.txt.";
        }

        return implode("\n", $lines);
    }
}
