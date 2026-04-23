<?php

namespace App\Filament\Widgets;

use App\Models\CallLog;
use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MyStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -10; // Show before admin widgets (negative sort = higher priority)
    protected static bool $isLazy = true;

    public static function canView(): bool
    {
        $user = auth()->user();
        if (!$user || !$user->staff_role_id) {
            return false;
        }

        // Show for any staff user with view_lead permission
        return $user->hasPermission('view_lead') || $user->isSuperAdmin();
    }

    protected function getStats(): array
    {
        $userId = auth()->id();

        // Open leads (not registered, lost, not_interested)
        $openLeads = Lead::where('assigned_to_staff_id', $userId)
            ->whereNotIn('status', ['registered', 'lost', 'not_interested'])
            ->count();

        // Calls this week
        $callsThisWeek = CallLog::where('called_by_staff_id', $userId)
            ->where('called_at', '>=', now()->startOfWeek())
            ->count();

        // Conversions this month
        $conversionsThisMonth = Lead::where('converted_by_staff_id', $userId)
            ->where('converted_at', '>=', now()->startOfMonth())
            ->count();

        // Total leads assigned to me (for conversion rate)
        $totalAssigned = Lead::where('assigned_to_staff_id', $userId)->count();
        $totalConverted = Lead::where('converted_by_staff_id', $userId)->count();
        $conversionRate = $totalAssigned > 0
            ? round(($totalConverted / $totalAssigned) * 100, 1)
            : 0;

        // Overdue follow-ups
        $overdue = Lead::where('assigned_to_staff_id', $userId)->overdue()->count();

        return [
            Stat::make('My Open Leads', number_format($openLeads))
                ->description('Active leads assigned to you')
                ->descriptionIcon('heroicon-o-user-group')
                ->color($openLeads > 0 ? 'primary' : 'gray'),

            Stat::make('Calls This Week', number_format($callsThisWeek))
                ->description('Calls you made in the last 7 days')
                ->descriptionIcon('heroicon-o-phone')
                ->color($callsThisWeek > 0 ? 'success' : 'gray'),

            Stat::make('Conversions This Month', number_format($conversionsThisMonth))
                ->description('Leads you converted to members')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color($conversionsThisMonth > 0 ? 'success' : 'gray'),

            Stat::make('Conversion Rate', $conversionRate . '%')
                ->description("{$totalConverted} of {$totalAssigned} leads converted")
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color($conversionRate >= 20 ? 'success' : ($conversionRate >= 10 ? 'warning' : 'gray')),

            Stat::make('Overdue Follow-ups', number_format($overdue))
                ->description('Leads needing follow-up now')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($overdue > 0 ? 'danger' : 'gray'),
        ];
    }
}
