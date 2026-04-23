<?php

namespace App\Filament\Widgets;

use App\Models\CallLog;
use Filament\Widgets\ChartWidget;

class MyCallActivityChart extends ChartWidget
{
    protected ?string $heading = 'My Call Activity (Last 14 Days)';
    protected static ?int $sort = -9;
    protected static bool $isLazy = true;
    protected ?string $maxHeight = '280px';
    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        $user = auth()->user();
        if (!$user || !$user->staff_role_id) {
            return false;
        }

        return $user->hasPermission('view_lead') || $user->isSuperAdmin();
    }

    protected function getData(): array
    {
        $userId = auth()->id();

        $labels = [];
        $counts = [];

        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d M');
            $counts[] = CallLog::where('called_by_staff_id', $userId)
                ->whereDate('called_at', $date->toDateString())
                ->count();
        }

        return [
            'datasets' => [[
                'label' => 'Calls',
                'data' => $counts,
                'backgroundColor' => 'rgba(139, 29, 145, 0.7)',
                'borderColor' => '#8B1D91',
                'borderWidth' => 1,
            ]],
            'labels' => $labels,
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
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['stepSize' => 1, 'precision' => 0],
                ],
            ],
        ];
    }
}
