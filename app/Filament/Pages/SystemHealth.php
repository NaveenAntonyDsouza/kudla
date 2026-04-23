<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SystemHealth extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'System Health';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 6;
    protected static ?string $title = 'System Health & Maintenance';
    protected string $view = 'filament.pages.system-health';

    public array $systemInfo = [];
    public array $logEntries = [];
    public int $logLines = 50;

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('system_health');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('system_health');
    }

    public function mount(): void
    {
        $this->loadSystemInfo();
        $this->loadRecentLogs();
    }

    protected function loadSystemInfo(): void
    {
        // PHP Info
        $phpVersion = phpversion();
        $laravelVersion = app()->version();

        // MySQL version
        $mysqlVersion = 'Unknown';
        try {
            $mysqlVersion = DB::selectOne('SELECT VERSION() as version')->version;
        } catch (\Throwable $e) {
            $mysqlVersion = 'Error: ' . $e->getMessage();
        }

        // Disk usage
        $storagePath = storage_path();
        $diskFree = @disk_free_space($storagePath);
        $diskTotal = @disk_total_space($storagePath);

        // Storage folder size
        $storageSize = $this->getDirectorySize(storage_path('app/public'));

        // Required PHP extensions
        $requiredExtensions = ['bcmath', 'ctype', 'json', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'curl', 'gd'];
        $extensions = [];
        foreach ($requiredExtensions as $ext) {
            $extensions[$ext] = extension_loaded($ext);
        }

        // Check Imagick as optional
        $extensions['imagick (optional)'] = extension_loaded('imagick');

        // File permissions
        $writablePaths = [
            'storage/' => is_writable(storage_path()),
            'storage/logs/' => is_writable(storage_path('logs')),
            'storage/app/public/' => is_writable(storage_path('app/public')),
            'bootstrap/cache/' => is_writable(base_path('bootstrap/cache')),
        ];

        // Cache & config status
        $configCached = file_exists(base_path('bootstrap/cache/config.php'));
        $routesCached = file_exists(base_path('bootstrap/cache/routes-v7.php'));
        $viewsCached = count(glob(storage_path('framework/views/*.php')) ?: []) > 0;

        // App environment
        $appEnv = config('app.env');
        $appDebug = config('app.debug');
        $appUrl = config('app.url');

        // Queue & Mail driver
        $queueDriver = config('queue.default');
        $mailDriver = config('mail.default');

        $this->systemInfo = [
            'php_version' => $phpVersion,
            'laravel_version' => $laravelVersion,
            'mysql_version' => $mysqlVersion,
            'disk_free' => $diskFree ? $this->formatBytes($diskFree) : 'N/A',
            'disk_total' => $diskTotal ? $this->formatBytes($diskTotal) : 'N/A',
            'disk_used_percent' => ($diskFree && $diskTotal) ? round((1 - $diskFree / $diskTotal) * 100, 1) : 0,
            'storage_size' => $this->formatBytes($storageSize),
            'extensions' => $extensions,
            'writable_paths' => $writablePaths,
            'config_cached' => $configCached,
            'routes_cached' => $routesCached,
            'views_cached' => $viewsCached,
            'app_env' => $appEnv,
            'app_debug' => $appDebug,
            'app_url' => $appUrl,
            'queue_driver' => $queueDriver,
            'mail_driver' => $mailDriver,
            'max_upload' => ini_get('upload_max_filesize'),
            'max_post' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . 's',
        ];
    }

    protected function loadRecentLogs(): void
    {
        $logFile = storage_path('logs/laravel.log');
        $this->logEntries = [];

        if (!file_exists($logFile)) {
            return;
        }

        // Read last N lines efficiently
        $lines = [];
        $file = new \SplFileObject($logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $start = max(0, $totalLines - $this->logLines);
        $file->seek($start);

        while (!$file->eof()) {
            $line = $file->fgets();
            if (trim($line) !== '') {
                $lines[] = $line;
            }
        }

        // Parse log entries
        $currentEntry = null;
        foreach ($lines as $line) {
            // Match log line pattern: [2026-04-16 10:30:00] production.ERROR: message
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(\w+): (.*)/', $line, $matches)) {
                if ($currentEntry) {
                    $this->logEntries[] = $currentEntry;
                }
                $currentEntry = [
                    'timestamp' => $matches[1],
                    'level' => strtolower($matches[2]),
                    'message' => $matches[3],
                ];
            } elseif ($currentEntry) {
                // Append stack trace lines to current entry (truncated)
                if (strlen($currentEntry['message']) < 500) {
                    $currentEntry['message'] .= "\n" . $line;
                }
            }
        }
        if ($currentEntry) {
            $this->logEntries[] = $currentEntry;
        }

        // Reverse so newest first
        $this->logEntries = array_reverse($this->logEntries);

        // Limit to most recent 30 entries
        $this->logEntries = array_slice($this->logEntries, 0, 30);
    }

    public function clearConfigCache(): void
    {
        try {
            Artisan::call('config:clear');
            Notification::make()->title('Config cache cleared')->success()->send();
            $this->loadSystemInfo();
        } catch (\Throwable $e) {
            Notification::make()->title('Failed: ' . $e->getMessage())->danger()->send();
        }
    }

    public function clearViewCache(): void
    {
        try {
            Artisan::call('view:clear');
            Notification::make()->title('View cache cleared')->success()->send();
            $this->loadSystemInfo();
        } catch (\Throwable $e) {
            Notification::make()->title('Failed: ' . $e->getMessage())->danger()->send();
        }
    }

    public function clearRouteCache(): void
    {
        try {
            Artisan::call('route:clear');
            Notification::make()->title('Route cache cleared')->success()->send();
            $this->loadSystemInfo();
        } catch (\Throwable $e) {
            Notification::make()->title('Failed: ' . $e->getMessage())->danger()->send();
        }
    }

    public function clearAllCaches(): void
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            Notification::make()->title('All caches cleared successfully')->success()->send();
            $this->loadSystemInfo();
        } catch (\Throwable $e) {
            Notification::make()->title('Failed: ' . $e->getMessage())->danger()->send();
        }
    }

    public function clearLogs(): void
    {
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
            $this->logEntries = [];
            Notification::make()->title('Log file cleared')->success()->send();
        }
    }

    public function refreshLogs(): void
    {
        $this->loadRecentLogs();
    }

    protected function getDirectorySize(string $path): int
    {
        $size = 0;
        if (!is_dir($path)) {
            return $size;
        }

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
