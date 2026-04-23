<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Widgets\ChartWidget;

/**
 * Branch revenue trend — last 6 months.
 * Shows the branch's monthly subscription revenue.
 */
class MyBranchRevenueChart extends ChartWidget
{
    protected ?string $heading = 'Branch Revenue (Last 6 Months)';
    protected static ?int $sort = -6;
    protected static bool $isLazy = true;
    protected ?string $maxHeight = '280px';
    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        $user = auth()->user();
        if (!$user || !$user->staff_role_id) {
            return false;
        }
        return $user->branch_id !== null && !$user->isSuperAdmin();
    }

    protected function getData(): array
    {
        $branchId = auth()->user()->branch_id;

        $labels = [];
        $data = [];

        // Walk back 6 months (oldest first)
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $monthEnd = $month->copy()->endOfMonth();

            $revenuePaise = (int) Subscription::where('branch_id', $branchId)
                ->where('payment_status', 'paid')
                ->whereBetween('created_at', [$month, $monthEnd])
                ->sum('amount');

            $labels[] = $month->format('M Y');
            $data[] = round($revenuePaise / 100); // rupees
        }

        return [
            'datasets' => [[
                'label' => 'Revenue (₹)',
                'data' => $data,
                'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                'borderColor' => '#10B981',
                'borderWidth' => 2,
                'fill' => true,
                'tension' => 0.3,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "₹" + value.toLocaleString("en-IN"); }',
                    ],
                ],
            ],
        ];
    }
}
