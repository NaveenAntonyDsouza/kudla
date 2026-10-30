<?php

namespace App\Filament\Widgets;

use App\Models\Interest;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserMembership;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected static bool $isLazy = true;

    public static function canView(): bool
    {
        return \App\Support\Permissions::can('view_user_reports');
    }

    protected function getStats(): array
    {
        // v2 (Oct 2026): "Active Users" only meant not hidden — now Live Profiles, plus real activity
        $stats = Cache::remember('admin_dashboard_stats_v2', 300, function () {
            return [
                'totalUsers' => Profile::count(),
                'activeUsers' => Profile::where('is_active', true)->where(fn($q) => $q->where('is_hidden', false)->orWhereNull('is_hidden'))->count(),
                'activeMembers30' => Profile::where('is_active', true)->whereHas('user', fn ($q) => $q->whereNull('staff_role_id')->where('last_login_at', '>=', now()->subDays(30)))->count(),
                'newToday' => Profile::whereDate('created_at', today())->count(),
                'pendingIdProofs' => \App\Models\IdProof::where('verification_status', 'pending')->count(),
                'activeSubscriptions' => UserMembership::where('is_active', true)->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))->count(),
                'totalInterests' => Interest::count(),
                'revenueThisMonth' => Subscription::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('payment_status', 'paid')->sum('amount') / 100,
                'pendingApprovals' => Profile::where('is_approved', false)->where('is_active', true)->count(),
            ];
        });

        $totalUsers = $stats['totalUsers'];
        $activeUsers = $stats['activeUsers'];
        $activeMembers30 = $stats['activeMembers30'];
        $newToday = $stats['newToday'];
        $pendingIdProofs = $stats['pendingIdProofs'];
        $activeSubscriptions = $stats['activeSubscriptions'];
        $totalInterests = $stats['totalInterests'];
        $revenueThisMonth = $stats['revenueThisMonth'];
        $pendingApprovals = $stats['pendingApprovals'];

        return [
            Stat::make('Total Profiles', number_format($totalUsers))
                ->description('All registered profiles')
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Live Profiles', number_format($activeUsers))
                ->description('Shown to members: not hidden or deleted')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Active Members', number_format($activeMembers30))
                ->description('Used the site in the last 30 days')
                ->icon('heroicon-o-bolt')
                ->color('primary'),

            Stat::make('New Today', number_format($newToday))
                ->description('Registered today')
                ->icon('heroicon-o-user-plus')
                ->color('info'),

            Stat::make('Revenue This Month', '₹' . number_format($revenueThisMonth))
                ->description(now()->format('F Y'))
                ->icon('heroicon-o-currency-rupee')
                ->color('success'),

            Stat::make('Pending Approvals', number_format($pendingApprovals))
                ->description('Profiles awaiting review')
                ->icon('heroicon-o-clock')
                ->color($pendingApprovals > 0 ? 'warning' : 'success'),

            Stat::make('Pending ID Proofs', number_format($pendingIdProofs))
                ->description('Awaiting review')
                ->icon('heroicon-o-identification')
                ->color($pendingIdProofs > 0 ? 'warning' : 'success'),

            Stat::make('Active Subscriptions', number_format($activeSubscriptions))
                ->description('Paid members')
                ->icon('heroicon-o-credit-card')
                ->color('success'),

            Stat::make('Total Interests', number_format($totalInterests))
                ->description('All time')
                ->icon('heroicon-o-heart')
                ->color('danger'),
        ];
    }
}
