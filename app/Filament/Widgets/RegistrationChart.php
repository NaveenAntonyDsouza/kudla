<?php

namespace App\Filament\Widgets;

use App\Models\Profile;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RegistrationChart extends ChartWidget
{
    protected ?string $heading = 'Registrations';
    protected static ?int $sort = 2;
    protected static bool $isLazy = true;
    protected ?string $maxHeight = '300px';
    protected int|string|array $columnSpan = 1;

    public ?string $filter = '30d';

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
        return Cache::remember("admin_reg_chart_{$this->filter}", 300, function () {
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

            $from = $days ? Carbon::today()->subDays($days) : Profile::min('created_at');
            if (!$from) $from = Carbon::today()->subDays(30);
            $from = Carbon::parse($from)->startOfDay();

            if ($groupBy === 'day') {
                $counts = Profile::select(DB::raw('DATE(created_at) as period'), DB::raw('COUNT(*) as total'))
                    ->where('created_at', '>=', $from)
                    ->groupBy('period')
                    ->pluck('total', 'period');

                $data = [];
                $labels = [];
                $totalDays = (int) $from->diffInDays(today());
                for ($i = $totalDays; $i >= 0; $i--) {
                    $date = Carbon::today()->subDays($i);
                    $labels[] = $date->format('d M');
                    $data[] = $counts[$date->toDateString()] ?? 0;
                }
            } elseif ($groupBy === 'week') {
                $counts = Profile::select(DB::raw('YEARWEEK(created_at, 1) as period'), DB::raw('COUNT(*) as total'))
                    ->where('created_at', '>=', $from)
                    ->groupBy('period')
                    ->pluck('total', 'period');

                $data = [];
                $labels = [];
                $current = $from->copy()->startOfWeek();
                while ($current <= today()) {
                    $yw = $current->format('oW');
                    // YEARWEEK returns YYYYWW format
                    $key = $current->year . str_pad($current->weekOfYear, 2, '0', STR_PAD_LEFT);
                    $labels[] = $current->format('d M');
                    $data[] = $counts[$key] ?? 0;
                    $current->addWeek();
                }
            } else {
                $counts = Profile::select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as period"), DB::raw('COUNT(*) as total'))
                    ->where('created_at', '>=', $from)
                    ->groupBy('period')
                    ->pluck('total', 'period');

                $data = [];
                $labels = [];
                $current = $from->copy()->startOfMonth();
                while ($current <= today()) {
                    $key = $current->format('Y-m');
                    $labels[] = $current->format('M Y');
                    $data[] = $counts[$key] ?? 0;
                    $current->addMonth();
                }
            }

            return [
                'datasets' => [[
                    'label' => 'Registrations',
                    'data' => $data,
                    'borderColor' => '#8B1D91',
                    'backgroundColor' => 'rgba(139, 29, 145, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ]],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'line';
    }
}
