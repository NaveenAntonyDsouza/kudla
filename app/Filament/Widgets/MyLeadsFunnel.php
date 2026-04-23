<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\ChartWidget;

class MyLeadsFunnel extends ChartWidget
{
    protected ?string $heading = 'My Leads by Status';
    protected static ?int $sort = -8;
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

        $statusMap = Lead::statuses();
        $labels = [];
        $data = [];
        $colors = [];

        $colorMap = [
            'gray' => '#9CA3AF',
            'info' => '#3B82F6',
            'success' => '#10B981',
            'danger' => '#EF4444',
            'warning' => '#F59E0B',
        ];

        foreach ($statusMap as $key => $meta) {
            $count = Lead::where('assigned_to_staff_id', $userId)
                ->where('status', $key)
                ->count();

            if ($count > 0) {
                $labels[] = $meta['label'];
                $data[] = $count;
                $colors[] = $colorMap[$meta['color']] ?? '#9CA3AF';
            }
        }

        if (empty($data)) {
            // Show at least one placeholder to avoid empty chart
            $labels[] = 'No Leads';
            $data[] = 1;
            $colors[] = '#E5E7EB';
        }

        return [
            'datasets' => [[
                'data' => $data,
                'backgroundColor' => $colors,
                'borderWidth' => 0,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
