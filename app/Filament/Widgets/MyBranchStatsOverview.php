<?php

namespace App\Filament\Widgets;

use App\Models\CallLog;
use App\Models\Lead;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Branch-level stats overview for Branch Manager / Branch Staff.
 * Shows their entire branch's metrics (not just personal).
 *
 * Visibility: any user who has a branch_id assignment (excludes Super Admin / HO Manager).
 */
class MyBranchStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -7; // Right after personal stats
    protected static bool $isLazy = true;
    protected ?string $heading = 'My Branch';

    public static function canView(): bool
    {
        $user = auth()->user();
        if (!$user || !$user->staff_role_id) {
            return false;
        }
        // Show only to branch-bound staff (not Super Admin, not HO Manager)
        return $user->branch_id !== null && !$user->isSuperAdmin();
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $branchId = $user->branch_id;
        $branchName = $user->branch?->name ?? 'My Branch';

        $monthStart = now()->startOfMonth();

        // Total members (profiles) in this branch
        $totalMembers = Profile::where('branch_id', $branchId)->count();

        // Registrations this month
        $registrationsThisMonth = Profile::where('branch_id', $branchId)
            ->where('created_at', '>=', $monthStart)
            ->count();

        // Open leads in branch
        $openLeads = Lead::where('branch_id', $branchId)
            ->whereNotIn('status', ['registered', 'lost', 'not_interested'])
            ->count();

        // Revenue this month (paise → rupees)
        $revenuePaise = (int) Subscription::where('branch_id', $branchId)
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $monthStart)
            ->sum('amount');
        $revenueRupees = $revenuePaise / 100;

        // Calls this month — by branch staff
        $branchStaffIds = User::where('branch_id', $branchId)
            ->whereNotNull('staff_role_id')
            ->pluck('id');
        $callsThisMonth = CallLog::whereIn('called_by_staff_id', $branchStaffIds)
            ->where('called_at', '>=', $monthStart)
            ->count();

        // Conversion rate this month — leads converted / leads created
        $leadsCreated = Lead::where('branch_id', $branchId)
            ->where('created_at', '>=', $monthStart)
            ->count();
        $leadsConverted = Lead::where('branch_id', $branchId)
            ->where('converted_at', '>=', $monthStart)
            ->count();
        $conversionRate = $leadsCreated > 0
            ? round(($leadsConverted / $leadsCreated) * 100, 1)
            : 0;

        return [
            Stat::make('Members', number_format($totalMembers))
                ->description("$branchName total profiles")
                ->descriptionIcon('heroicon-o-user-group')
                ->color('primary'),

            Stat::make('New This Month', number_format($registrationsThisMonth))
                ->description($monthStart->format('F') . ' registrations')
                ->descriptionIcon('heroicon-o-user-plus')
                ->color($registrationsThisMonth > 0 ? 'success' : 'gray'),

            Stat::make('Open Leads', number_format($openLeads))
                ->description('Active leads in branch')
                ->descriptionIcon('heroicon-o-megaphone')
                ->color($openLeads > 0 ? 'info' : 'gray'),

            Stat::make('Revenue (Month)', '₹' . number_format($revenueRupees))
                ->description('Subscription revenue this month')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color($revenueRupees > 0 ? 'success' : 'gray'),

            Stat::make('Calls (Month)', number_format($callsThisMonth))
                ->description('Calls by branch staff')
                ->descriptionIcon('heroicon-o-phone')
                ->color($callsThisMonth > 0 ? 'success' : 'gray'),

            Stat::make('Conversion Rate', $conversionRate . '%')
                ->description("$leadsConverted of $leadsCreated leads converted")
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color($conversionRate >= 20 ? 'success' : ($conversionRate >= 10 ? 'warning' : 'gray')),
        ];
    }
}
