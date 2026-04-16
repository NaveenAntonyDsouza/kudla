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

    protected function getStats(): array
    {
        $stats = Cache::remember('admin_dashboard_stats', 300, function () {
            return [
                'totalUsers' => Profile::count(),
                'activeUsers' => Profile::where('is_active', true)->where(fn($q) => $q->where('is_hidden', false)->orWhereNull('is_hidden'))->count(),
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
        $newToday = $stats['newToday'];
        $pendingIdProofs = $stats['pendingIdProofs'];
        $activeSubscriptions = $stats['activeSubscriptions'];
        $totalInterests = $stats['totalInterests'];
        $revenueThisMonth = $stats['revenueThisMonth'];
        $pendingApprovals = $stats['pendingApprovals'];

        return [
            Stat::make('Total Users', number_format($totalUsers))
                ->description('All registered profiles')
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Active Users', number_format($activeUsers))
                ->description('Not hidden or deleted')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

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
