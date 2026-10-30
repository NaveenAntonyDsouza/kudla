<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Insights → Site Overview: the totals and charts that were the admin home page
 * until Oct 2026 (the home page is now "Today", the work queue).
 */
class SiteOverview extends BaseDashboard
{
    protected static string $routePath = 'overview';
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static \UnitEnum|string|null $navigationGroup = 'Insights';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Site Overview';
    protected static ?string $title = 'Site Overview';

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return null;
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverview::class,
            \App\Filament\Widgets\RegistrationChart::class,
            \App\Filament\Widgets\RevenueChart::class,
            \App\Filament\Widgets\PlanSalesChart::class,
            \App\Filament\Widgets\GenderDistribution::class,
            \App\Filament\Widgets\ReligionDistribution::class,
            \App\Filament\Widgets\CasteDenominationChart::class,
            \App\Filament\Widgets\RecentRegistrations::class,
            \App\Filament\Widgets\RecentPayments::class,
            // branch / telecaller dashboards (shown only when those tools are switched on)
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
}
