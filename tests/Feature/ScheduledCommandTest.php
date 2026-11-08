<?php

use App\Support\ScheduledCommand;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;

/*
| 7 Nov 2026: Hostinger's new server disabled proc_open, so every Schedule::command()
| failed. ScheduledCommand::add() runs the command inside the scheduler's process there.
*/

afterEach(fn () => ScheduledCommand::$inProcess = null);

it('starts commands as their own process where the server allows it', function () {
    ScheduledCommand::$inProcess = false;
    $event = ScheduledCommand::add('inspire');

    expect($event)->not->toBeInstanceOf(CallbackEvent::class)
        ->and($event->command)->toContain('inspire');
});

it('runs the command inside the scheduler where proc_open is disabled, with its options', function () {
    $seen = null;
    Artisan::command('test:sched-probe {--scheduled} {--delay=}', function () use (&$seen) {
        $seen = ['scheduled' => $this->option('scheduled'), 'delay' => $this->option('delay')];
    });

    ScheduledCommand::$inProcess = true;
    $event = ScheduledCommand::add('test:sched-probe', ['--scheduled', '--delay' => 3])->dailyAt('10:30')->withoutOverlapping();

    expect($event)->toBeInstanceOf(CallbackEvent::class)
        ->and($event->expression)->toBe('30 10 * * *')
        ->and($event->description)->toBe('test:sched-probe --scheduled --delay=3');

    $event->run(app());
    expect($seen)->toBe(['scheduled' => true, 'delay' => '3']);
});

it('reports a failing command as a failed task', function () {
    Artisan::command('test:sched-fails', fn () => 1);
    ScheduledCommand::$inProcess = true;
    $event = ScheduledCommand::add('test:sched-fails');

    expect(fn () => $event->run(app()))->toThrow(RuntimeException::class, 'test:sched-fails');
});

it('keeps every scheduled job in the schedule', function () {
    $names = collect(app(Schedule::class)->events())->map(fn ($e) => $e->command ?? $e->description)->implode("\n");
    foreach (['db:backup', 'alerts:site-errors', 'uptime:watch', 'members:erase-deleted', 'engagement:send-weekly-matches'] as $job) {
        expect($names)->toContain($job);
    }
});
