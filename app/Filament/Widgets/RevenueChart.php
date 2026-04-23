<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Revenue';
    protected static ?int $sort = 3;
    protected static bool $isLazy = true;
    protected ?string $maxHeight = '300px';
    protected int|string|array $columnSpan = 1;

    public ?string $filter = '30d';

    public static function canView(): bool
    {
        return \App\Support\Permissions::can('view_revenue_reports');
    }

    protected function getFilters(): ?array
    {
        return [
            '7d' => 'Last 7 Days',
            '30d' => 'Last 30 Days',
            '3m' => 'Last 3 Months',
            '6m' => 'Last 6 Months',
            '1y' => 'Last 1 Year',
            'all' => 'All Time',
        ];
    }

    protected function getData(): array
    {
        return Cache::remember("admin_rev_chart_{$this->filter}", 300, function () {
            $groupBy = match ($this->filter) {
                '7d', '30d' => 'day',
                '3m', '6m' => 'week',
                '1y', 'all' => 'month',
            };

            $days = match ($this->filter) {
                '7d' => 7,
                '30d' => 30,
                '3m' => 90,
                '6m' => 180,
                '1y' => 365,
                'all' => null,
            };

            $from = $days ? Carbon::today()->subDays($days) : Subscription::where('payment_status', 'paid')->min('created_at');
            if (!$from) $from = Carbon::today()->subDays(30);
            $from = Carbon::parse($from)->startOfDay();

            $baseQuery = Subscription::where('payment_status', 'paid')->where('created_at', '>=', $from);

            if ($groupBy === 'day') {
                $amounts = (clone $baseQuery)
                    ->select(DB::raw('DATE(created_at) as period'), DB::raw('SUM(amount) as total'))
                    ->groupBy('period')
                    ->pluck('total', 'period');

                $data = [];
                $labels = [];
                $totalDays = (int) $from->diffInDays(today());
                for ($i = $totalDays; $i >= 0; $i--) {
                    $date = Carbon::today()->subDays($i);
                    $labels[] = $date->format('d M');
                    $data[] = ($amounts[$date->toDateString()] ?? 0) / 100;
                }
            } elseif ($groupBy === 'week') {
                $amounts = (clone $baseQuery)
                    ->select(DB::raw('YEARWEEK(created_at, 1) as period'), DB::raw('SUM(amount) as total'))
                    ->groupBy('period')
                    ->pluck('total', 'period');

                $data = [];
                $labels = [];
                $current = $from->copy()->startOfWeek();
                while ($current <= today()) {
                    $key = $current->year . str_pad($current->weekOfYear, 2, '0', STR_PAD_LEFT);
                    $labels[] = $current->format('d M');
                    $data[] = ($amounts[$key] ?? 0) / 100;
                    $current->addWeek();
                }
            } else {
                $amounts = (clone $baseQuery)
                    ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as period"), DB::raw('SUM(amount) as total'))
                    ->groupBy('period')
                    ->pluck('total', 'period');

                $data = [];
                $labels = [];
                $current = $from->copy()->startOfMonth();
                while ($current <= today()) {
                    $key = $current->format('Y-m');
                    $labels[] = $current->format('M Y');
                    $data[] = ($amounts[$key] ?? 0) / 100;
                    $current->addMonth();
                }
            }

            return [
                'datasets' => [[
                    'label' => 'Revenue (₹)',
                    'data' => $data,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.8)',
                    'borderColor' => '#10B981',
                    'borderWidth' => 1,
                ]],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
