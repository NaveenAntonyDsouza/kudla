<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PlanSalesChart extends ChartWidget
{
    protected ?string $heading = 'Membership Plan Sales';
    protected static ?int $sort = 4;
    protected static bool $isLazy = true;
    protected ?string $maxHeight = '300px';
    protected int|string|array $columnSpan = 'full';

    public ?string $filter = 'all';

    protected function getFilters(): ?array
    {
        return [
            'all' => 'All Time',
            '1year' => 'Last 1 Year',
            '6months' => 'Last 6 Months',
            '3months' => 'Last 3 Months',
            '1month' => 'Last 1 Month',
            '1week' => 'Last 1 Week',
        ];
    }

    protected function getData(): array
    {
        $query = Subscription::where('payment_status', 'paid');

        // Apply date filter
        $query = match ($this->filter) {
            '1year' => $query->where('created_at', '>=', now()->subYear()),
            '6months' => $query->where('created_at', '>=', now()->subMonths(6)),
            '3months' => $query->where('created_at', '>=', now()->subMonths(3)),
            '1month' => $query->where('created_at', '>=', now()->subMonth()),
            '1week' => $query->where('created_at', '>=', now()->subWeek()),
            default => $query, // all time
        };

        $plans = $query
            ->select('plan_name', DB::raw('COUNT(*) as total_sales'), DB::raw('SUM(amount) as total_revenue'))
            ->groupBy('plan_name')
            ->orderByDesc('total_sales')
            ->get();

        $colors = [
            '#8B1D91', '#3B82F6', '#10B981', '#F59E0B', '#EF4444',
            '#6366F1', '#EC4899',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Sales Count',
                    'data' => $plans->pluck('total_sales')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $plans->count()),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $plans->map(fn($p) => "{$p->plan_name} ({$p->total_sales} sold, ₹" . number_format($p->total_revenue / 100) . ")")->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]],
            ],
        ];
    }
}
