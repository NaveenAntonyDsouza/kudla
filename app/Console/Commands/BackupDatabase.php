<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Nightly copy of the site's database (7 Nov 2026: there was no backup any site could be
 * restored from; a deleted chat could not be recovered). Plain PHP, no mysqldump: Hostinger
 * runs only the site's own scheduler and allows no shell commands from PHP.
 *
 * Writes storage/backups/db-YYYY-MM-DD.sql.gz (no web address reaches storage/), keeps the
 * last 14 days and the first of each month for 6 months. A failure, or no backup in the last
 * 26 hours (--check), is logged as an error, so the hourly error alert emails the owner.
 * The files are copied to the other hosting account every night (db:offsite-copy).
 */
class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--check : only check that a backup was made in the last 26 hours}';

    protected $description = 'Back up the database to storage/backups (gzip SQL), keeping 14 daily and 6 monthly copies';

    public const KEEP_DAILY = 14;

    public const KEEP_MONTHLY = 6;

    public const ROWS_PER_INSERT = 200;

    public static function directory(): string
    {
        return storage_path('backups');
    }

    public function handle(): int
    {
        return $this->option('check') ? $this->check() : $this->backup();
    }

    private function backup(): int
    {
        $dir = self::directory();
        $final = $dir . '/db-' . now()->format('Y-m-d') . '.sql.gz';
        $part = $final . '.part';

        try {
            if (! is_dir($dir) && ! mkdir($dir, 0700, true) && ! is_dir($dir)) {
                throw new \RuntimeException("cannot create {$dir}");
            }
            @file_put_contents($dir . '/.htaccess', "Require all denied\n"); // in case the folder is ever exposed

            $tables = $this->dump($part);
            if (! rename($part, $final)) {
                throw new \RuntimeException('cannot move the finished backup into place');
            }
            @chmod($final, 0600);
            $this->prune($dir);

            $this->info(sprintf('Backed up %d tables to %s (%s KB)', $tables, basename($final), number_format(filesize($final) / 1024)));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            @unlink($part);
            Log::error('Database backup failed: ' . $e->getMessage());
            $this->error('Database backup failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    /** Writes the dump; returns the number of tables. */
    private function dump(string $path): int
    {
        $pdo = DB::connection()->getPdo();
        $gz = gzopen($path, 'wb6');
        if ($gz === false) {
            throw new \RuntimeException("cannot write {$path}");
        }

        $write = function (string $sql) use ($gz): void {
            if (gzwrite($gz, $sql) === false) {
                throw new \RuntimeException('write failed (disk full?)');
            }
        };

        $database = DB::connection()->getDatabaseName();
        $write("-- Database backup of {$database}, " . now()->toDateTimeString() . ' (' . config('app.url') . ")\n");
        $write("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET UNIQUE_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

        $list = $pdo->query('SHOW FULL TABLES')->fetchAll(\PDO::FETCH_NUM);
        $tables = array_values(array_map(fn ($r) => $r[0], array_filter($list, fn ($r) => strtoupper($r[1]) === 'BASE TABLE')));
        $views = array_values(array_map(fn ($r) => $r[0], array_filter($list, fn ($r) => strtoupper($r[1]) === 'VIEW')));

        foreach ($tables as $table) {
            $q = '`' . str_replace('`', '``', $table) . '`';
            $create = $pdo->query("SHOW CREATE TABLE {$q}")->fetch(\PDO::FETCH_NUM)[1];
            $write("DROP TABLE IF EXISTS {$q};\n{$create};\n");

            $rows = $pdo->query("SELECT * FROM {$q}", \PDO::FETCH_NUM);
            $batch = [];
            foreach ($rows as $row) {
                $batch[] = '(' . implode(',', array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row)) . ')';
                if (count($batch) >= self::ROWS_PER_INSERT) {
                    $write("INSERT INTO {$q} VALUES\n" . implode(",\n", $batch) . ";\n");
                    $batch = [];
                }
            }
            if ($batch !== []) {
                $write("INSERT INTO {$q} VALUES\n" . implode(",\n", $batch) . ";\n");
            }
            $write("\n");
        }

        foreach ($views as $view) {
            $q = '`' . str_replace('`', '``', $view) . '`';
            $create = $pdo->query("SHOW CREATE VIEW {$q}")->fetch(\PDO::FETCH_NUM)[1];
            // without DEFINER, so it restores under any database user
            $create = preg_replace('/DEFINER=`[^`]*`@`[^`]*`\s*/', '', $create);
            $write("DROP VIEW IF EXISTS {$q};\n{$create};\n\n");
        }

        $write("SET FOREIGN_KEY_CHECKS=1;\nSET UNIQUE_CHECKS=1;\n-- Dump completed: " . count($tables) . " tables\n");
        gzclose($gz);

        return count($tables);
    }

    /** Keeps the newest KEEP_DAILY backups, plus the first-of-month backup of the last KEEP_MONTHLY months. */
    private function prune(string $dir): void
    {
        $files = glob($dir . '/db-????-??-??.sql.gz') ?: [];
        rsort($files); // newest first (the date is in the name)
        $keep = array_slice($files, 0, self::KEEP_DAILY);
        $monthly = array_values(array_filter($files, fn ($f) => str_ends_with(basename($f, '.sql.gz'), '-01')));
        $keep = array_merge($keep, array_slice($monthly, 0, self::KEEP_MONTHLY));

        foreach (array_diff($files, $keep) as $old) {
            @unlink($old);
        }
    }

    private function check(): int
    {
        $files = glob(self::directory() . '/db-????-??-??.sql.gz') ?: [];
        $newest = $files ? max(array_map('filemtime', $files)) : null;

        if ($newest === null || $newest < now()->subHours(26)->timestamp) {
            Log::error('Database backup missing: no backup in the last 26 hours' . ($newest ? ' (newest ' . date('Y-m-d H:i', $newest) . ')' : ''));
            $this->error('No database backup in the last 26 hours.');

            return self::FAILURE;
        }

        $this->info('Newest backup: ' . date('Y-m-d H:i', $newest));

        return self::SUCCESS;
    }
}
