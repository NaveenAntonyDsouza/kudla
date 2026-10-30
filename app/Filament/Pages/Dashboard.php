<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * The admin home page: "Today", the work waiting for someone (admin audit,
 * Oct 2026). The totals and charts that used to be here are under
 * Insights (Scorecard, Members Overview).
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
            // branch / telecaller work (shown only when those tools are switched on)
            \App\Filament\Widgets\MyStatsOverview::class,
            \App\Filament\Widgets\MyTargetProgress::class,
            \App\Filament\Widgets\MyCallActivityChart::class,
            \App\Filament\Widgets\MyLeadsFunnel::class,
            \App\Filament\Widgets\MyUpcomingFollowups::class,
            \App\Filament\Widgets\MyBranchStatsOverview::class,
            \App\Filament\Widgets\MyBranchRevenueChart::class,
            \App\Filament\Widgets\MyBranchLeadsFunnel::class,
            \App\Filament\Widgets\MyBranchStaffPerformance::class,
            \App\Filament\Widgets\MyBranchAffiliateStats::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
