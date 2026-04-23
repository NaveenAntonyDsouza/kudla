<?php

namespace App\Filament\Widgets;

use App\Models\AffiliateClick;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Affiliate funnel stats for the Branch Manager / Branch Staff dashboard.
 * Shows clicks → registrations → conversions for this month.
 */
class MyBranchAffiliateStats extends StatsOverviewWidget
{
    protected static ?int $sort = -5; // After branch overview, before staff performance
    protected static bool $isLazy = true;
    protected ?string $heading = 'Affiliate Performance (This Month)';

    public static function canView(): bool
    {
        $user = auth()->user();
        if (!$user || !$user->staff_role_id) {
            return false;
        }
        return $user->branch_id !== null && !$user->isSuperAdmin();
    }

    protected function getStats(): array
    {
        $branchId = auth()->user()->branch_id;
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        // Clicks this month
        $clicks = AffiliateClick::where('branch_id', $branchId)
            ->whereBetween('visited_at', [$monthStart, $monthEnd])
            ->count();

        // Unique visitors (by IP hash)
        $uniqueVisitors = AffiliateClick::where('branch_id', $branchId)
            ->whereBetween('visited_at', [$monthStart, $monthEnd])
            ->distinct('ip_hash')
            ->count('ip_hash');

        // Registrations from clicks this month
        $registrations = AffiliateClick::where('branch_id', $branchId)
            ->whereNotNull('registered_user_id')
            ->whereBetween('registered_at', [$monthStart, $monthEnd])
            ->count();

        // Paid conversions this month
        $conversions = AffiliateClick::where('branch_id', $branchId)
            ->whereNotNull('converted_at')
            ->whereBetween('converted_at', [$monthStart, $monthEnd])
            ->count();

        // Conversion rate (paid / registrations)
        $convRate = $registrations > 0 ? round(($conversions / $registrations) * 100, 1) : 0;

        // Click-through rate (registrations / unique visitors)
        $signupRate = $uniqueVisitors > 0 ? round(($registrations / $uniqueVisitors) * 100, 1) : 0;

        return [
            Stat::make('Clicks', number_format($clicks))
                ->description("$uniqueVisitors unique visitors")
                ->descriptionIcon('heroicon-o-cursor-arrow-rays')
                ->color($clicks > 0 ? 'info' : 'gray'),

            Stat::make('Registrations', number_format($registrations))
                ->description("$signupRate% of unique visitors signed up")
                ->descriptionIcon('heroicon-o-user-plus')
                ->color($registrations > 0 ? 'success' : 'gray'),

            Stat::make('Paid Conversions', number_format($conversions))
                ->description("$convRate% of registrations paid")
                ->descriptionIcon('heroicon-o-currency-rupee')
                ->color($conversions > 0 ? 'success' : 'gray'),
        ];
    }
}
