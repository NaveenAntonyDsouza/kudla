<?php

namespace App\Filament\Pages;

use App\Models\Subscription;
use App\Models\UserMembership;
use App\Services\CsvExportService;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueReports extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Revenue Reports';
    protected static \UnitEnum|string|null $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Revenue Reports';
    protected string $view = 'filament.pages.revenue-reports';

    public array $stats = [];
    public array $revenueChart = [];
    public array $planData = [];
    public array $recentPayments = [];
    public array $failedPayments = [];
    public array $expiringSubscriptions = [];

    public function mount(): void
    {
        $this->loadStats();
        $this->loadRevenueChart();
        $this->loadPlanData();
        $this->loadRecentPayments();
        $this->loadFailedPayments();
        $this->loadExpiringSubscriptions();
    }

    private function loadStats(): void
    {
        $totalRevenue = Subscription::where('payment_status', 'paid')->sum('amount') / 100;
        $thisMonth = Subscription::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount') / 100;
        $activeSubscriptions = UserMembership::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->count();
        $failedCount = Subscription::where('payment_status', 'failed')->count();
        $paidUsers = Subscription::where('payment_status', 'paid')->distinct('user_id')->count('user_id');

        $this->stats = [
            'total_revenue' => $totalRevenue,
            'this_month' => $thisMonth,
            'active_subscriptions' => $activeSubscriptions,
            'failed_payments' => $failedCount,
            'arpu' => $paidUsers > 0 ? round($totalRevenue / $paidUsers, 0) : 0,
        ];
    }

    private function loadRevenueChart(): void
    {
        $data = Subscription::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as period"),
            DB::raw('SUM(amount) / 100 as total')
        )
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('total', 'period')
            ->toArray();

        $this->revenueChart = [
            'labels' => array_map(fn ($k) => Carbon::createFromFormat('Y-m', $k)->format('M Y'), array_keys($data)),
            'values' => array_map(fn ($v) => round((float) $v, 0), array_values($data)),
        ];
    }

    private function loadPlanData(): void
    {
        $this->planData = Subscription::select('plan_name', DB::raw('SUM(amount) / 100 as total'))
            ->where('payment_status', 'paid')
            ->groupBy('plan_name')
            ->orderByDesc('total')
            ->pluck('total', 'plan_name')
            ->toArray();
    }

    private function loadRecentPayments(): void
    {
        $this->recentPayments = Subscription::with('user')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn (Subscription $s) => [
                'user' => $s->user?->name,
                'email' => $s->user?->email,
                'plan' => $s->plan_name,
                'amount' => number_format($s->amount / 100, 0),
                'status' => $s->payment_status,
                'date' => $s->created_at?->format('d M Y'),
            ])
            ->toArray();
    }

    private function loadFailedPayments(): void
    {
        $this->failedPayments = Subscription::with('user')
            ->where('payment_status', 'failed')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (Subscription $s) => [
                'user' => $s->user?->name,
                'email' => $s->user?->email,
                'plan' => $s->plan_name,
                'amount' => number_format($s->amount / 100, 0),
                'date' => $s->created_at?->format('d M Y'),
            ])
            ->toArray();
    }

    private function loadExpiringSubscriptions(): void
    {
        $this->expiringSubscriptions = UserMembership::with(['user.profile', 'plan'])
            ->where('is_active', true)
            ->whereBetween('ends_at', [now(), now()->addDays(7)])
            ->orderBy('ends_at')
            ->limit(20)
            ->get()
            ->map(fn ($m) => [
                'matri_id' => $m->user?->profile?->matri_id,
                'name' => $m->user?->name,
                'plan' => $m->plan?->plan_name,
                'expires' => $m->ends_at?->format('d M Y'),
                'days_left' => $m->ends_at?->diffInDays(now()),
            ])
            ->toArray();
    }

    public function exportCsv()
    {
        return CsvExportService::exportPayments();
    }
}
