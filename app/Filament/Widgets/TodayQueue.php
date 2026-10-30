<?php

namespace App\Filament\Widgets;

use App\Support\WorkQueue;
use Filament\Widgets\Widget;

/**
 * "Waiting for someone" on the Today page: one line per kind of work, with how
 * long the oldest item has waited (App\Support\WorkQueue).
 */
class TodayQueue extends Widget
{
    protected string $view = 'filament.widgets.today-queue';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    /** Only on the Today page, not discovered onto other dashboards. */
    protected static bool $isDiscovered = false;

    protected function getViewData(): array
    {
        $items = WorkQueue::items();

        return [
            'waiting' => array_values(array_filter($items, fn ($i) => $i['count'] > 0)),
            'clear' => array_values(array_filter($items, fn ($i) => $i['count'] === 0)),
        ];
    }
}
