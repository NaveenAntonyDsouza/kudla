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

    public static function canView(): bool
    {
        return \App\Support\Permissions::can('view_user_reports');
    }

    protected function getData(): array
    {
        // live profiles only (deleted members' rows used to count too), validated colours (Oct 2026)
        return Cache::remember('admin_religion_dist_v2', 300, function () {
            $religions = ReligiousInfo::select('religion', DB::raw('COUNT(*) as total'))
                ->whereHas('profile', fn ($q) => $q->where('is_active', true))
                ->whereNotNull('religion')->where('religion', '!=', '')
                ->groupBy('religion')
                ->orderByDesc('total')
                ->get();

            // seven colours in a fixed order; an eighth religion and beyond fold into "Other"
            if ($religions->count() > 7) {
                $rest = $religions->slice(6)->sum('total');
                $religions = $religions->take(6)->push((object) ['religion' => 'Other', 'total' => $rest]);
            }
            $colors = \App\Support\ChartColors::SERIES;

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
