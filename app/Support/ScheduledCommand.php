<?php

namespace App\Support;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use RuntimeException;

/**
 * Schedules an artisan command so it runs even where PHP may not start programs.
 *
 * Laravel's Schedule::command() starts each command as a separate `php artisan …`
 * process, which needs proc_open. On 7 Nov 2026 the Hostinger account moved to a
 * server that disables proc_open, so every scheduled command there failed ("The
 * Process class relies on proc_open") — no backups, no reminder emails. Where
 * proc_open is missing, the command runs inside the scheduler's own PHP process
 * instead (Artisan::call), with the same timing, overlap lock and error reporting.
 */
class ScheduledCommand
{
    /** null = decide from the server; tests set true/false */
    public static ?bool $inProcess = null;

    public static function add(string $command, array $parameters = []): Event
    {
        if (! (static::$inProcess ?? ! function_exists('proc_open'))) {
            return Schedule::command($command, $parameters);
        }

        $arguments = static::arguments($parameters);
        $name = trim($command . ' ' . implode(' ', array_map(
            fn ($k, $v) => is_int($k) ? $v : $k . '=' . (is_array($v) ? implode(',', $v) : $v),
            array_keys($parameters),
            $parameters,
        )));

        return Schedule::call(function () use ($command, $arguments, $name) {
            $code = Artisan::call($command, $arguments);
            if ($code !== 0) {
                // the scheduler reports it like any failed task → laravel.log → error alert
                throw new RuntimeException("Scheduled command failed (exit {$code}): {$name}");
            }
        })->name($name);
    }

    /** Schedule-style parameters (['--scheduled', '--delay' => 3]) → Artisan::call() style */
    public static function arguments(array $parameters): array
    {
        $out = [];
        foreach ($parameters as $key => $value) {
            if (is_int($key)) {
                $out[$value] = true;
            } else {
                // as text, the way they arrive from a command line
                $out[$key] = is_array($value) ? array_map('strval', $value) : (string) $value;
            }
        }

        return $out;
    }
}
