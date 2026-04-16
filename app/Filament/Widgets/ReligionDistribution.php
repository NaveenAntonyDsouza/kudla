<?php

namespace App\Filament\Widgets;

use App\Models\ReligiousInfo;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReligionDistribution extends ChartWidget
{
    protected ?string $heading = 'Religion Distribution';
    protected static ?int $sort = 6;
    protected static bool $isLazy = true;
    protected ?string $maxHeight = '250px';
    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        return Cache::remember('admin_religion_dist', 300, function () {
            $religions = ReligiousInfo::select('religion', DB::raw('COUNT(*) as total'))
                ->whereNotNull('religion')
                ->groupBy('religion')
                ->orderByDesc('total')
                ->get();

            $colors = [
                '#8B1D91', '#3B82F6', '#10B981', '#F59E0B',
                '#EF4444', '#6366F1', '#EC4899', '#14B8A6',
            ];

            return [
                'datasets' => [
                    [
                        'data' => $religions->pluck('total')->toArray(),
                        'backgroundColor' => array_slice($colors, 0, $religions->count()),
                    ],
                ],
                'labels' => $religions->map(fn($r) => "{$r->religion} ({$r->total})")->toArray(),
            ];
        });
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
