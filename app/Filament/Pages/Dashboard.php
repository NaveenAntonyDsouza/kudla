<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * The admin home page: "Today", the work waiting for someone (admin audit,
 * Oct 2026). The totals and charts that used to be here are under
 * Insights → Site Overview.
 */
class Dashboard extends BaseDashboard
{
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static \UnitEnum|string|null $navigationGroup = 'Today';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Work Queue';
    protected static ?string $title = 'Today';

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return null;
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\TodayQueue::class,
            \App\Filament\Widgets\UpcomingFollowups::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
