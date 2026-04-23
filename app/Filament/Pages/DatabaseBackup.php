<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseBackup extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Database Backup';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 9;
    protected static ?string $title = 'Database Backup';
    protected string $view = 'filament.pages.database-backup';

    public string $databaseName = '';
    public string $databaseSize = '';
    public array $tables = [];

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('database_backup');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('database_backup');
    }

    public function mount(): void
    {
        $this->loadDatabaseInfo();
    }

    protected function loadDatabaseInfo(): void
    {
        $this->databaseName = config('database.connections.mysql.database');

        // Get database size
        try {
            $result = DB::selectOne(
                "SELECT SUM(data_length + index_length) as size FROM information_schema.TABLES WHERE table_schema = ?",
                [$this->databaseName]
            );
            $this->databaseSize = $this->formatBytes((int) ($result->size ?? 0));
        } catch (\Throwable $e) {
            $this->databaseSize = 'Unknown';
        }

        // Get table list with row counts
        try {
            $tables = DB::select(
                "SELECT table_name, table_rows, ROUND((data_length + index_length)) as size
                 FROM information_schema.TABLES
                 WHERE table_schema = ?
                 ORDER BY table_name",
                [$this->databaseName]
            );

            $this->tables = array_map(fn ($t) => [
                'name' => $t->table_name ?? $t->TABLE_NAME,
                'rows' => (int) ($t->table_rows ?? $t->TABLE_ROWS ?? 0),
                'size' => $this->formatBytes((int) ($t->size ?? 0)),
            ], $tables);
        } catch (\Throwable $e) {
            $this->tables = [];
        }
    }

    public function downloadBackup(): StreamedResponse
    {
        $dbName = $this->databaseName;
        $filename = "backup_{$dbName}_" . date('Y-m-d_His') . '.sql';

        return response()->streamDownload(function () use ($dbName) {
            set_time_limit(300);

            echo "-- Database Backup: {$dbName}\n";
            echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            echo "-- Server: " . config('app.url') . "\n";
            echo "-- ------------------------------------------------\n\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n";
            echo "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";

            // Get all tables
            $tables = DB::select('SHOW TABLES');
            $tableKey = "Tables_in_{$dbName}";

            foreach ($tables as $table) {
                $tableName = $table->$tableKey ?? array_values((array) $table)[0];

                // CREATE TABLE statement
                $createResult = DB::selectOne("SHOW CREATE TABLE `{$tableName}`");
                $createSql = $createResult->{'Create Table'} ?? '';

                echo "-- ------------------------------------------------\n";
                echo "-- Table: {$tableName}\n";
                echo "-- ------------------------------------------------\n\n";
                echo "DROP TABLE IF EXISTS `{$tableName}`;\n";
                echo $createSql . ";\n\n";

                // INSERT statements (chunked to avoid memory issues)
                $offset = 0;
                $chunkSize = 500;

                while (true) {
                    $rows = DB::select("SELECT * FROM `{$tableName}` LIMIT {$chunkSize} OFFSET {$offset}");

                    if (empty($rows)) {
                        break;
                    }

                    foreach ($rows as $row) {
                        $values = [];
                        foreach ((array) $row as $value) {
                            if (is_null($value)) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = DB::getPdo()->quote($value);
                            }
                        }

                        $columns = implode('`, `', array_keys((array) $row));
                        echo "INSERT INTO `{$tableName}` (`{$columns}`) VALUES (" . implode(', ', $values) . ");\n";
                    }

                    $offset += $chunkSize;

                    // Flush output buffer
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }

                echo "\n";
            }

            echo "SET FOREIGN_KEY_CHECKS=1;\n";
            echo "\n-- Backup complete.\n";
        }, $filename, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }
}
