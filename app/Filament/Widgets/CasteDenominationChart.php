<?php

namespace App\Filament\Widgets;

use App\Models\ReligiousInfo;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CasteDenominationChart extends ChartWidget
{
    protected ?string $heading = 'Caste / Denomination Distribution';
    protected static ?int $sort = 7;
    protected static bool $isLazy = true;
    protected ?string $maxHeight = '400px';
    protected int|string|array $columnSpan = 'full';

    public ?string $filter = 'all';

    protected function getFilters(): ?array
    {
        return [
            'all' => 'All Religions',
            'Hindu' => 'Hindu',
            'Christian' => 'Christian',
            'Muslim' => 'Muslim',
            'Jain' => 'Jain',
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter;

        $query = ReligiousInfo::query();

        if ($filter === 'all') {
            // Combined: show caste/denomination across all religions
            $data = $query
                ->select(DB::raw("COALESCE(NULLIF(denomination, ''), NULLIF(caste, ''), NULLIF(muslim_sect, ''), NULLIF(jain_sect, ''), 'Not Specified') as community"), DB::raw('COUNT(*) as total'))
                ->groupBy('community')
                ->orderByDesc('total')
                ->limit(15)
                ->get();
        } elseif ($filter === 'Christian') {
            $data = $query->where('religion', 'Christian')
                ->select(DB::raw("COALESCE(NULLIF(denomination, ''), 'Not Specified') as community"), DB::raw('COUNT(*) as total'))
                ->groupBy('community')
                ->orderByDesc('total')
                ->limit(15)
                ->get();
        } elseif ($filter === 'Muslim') {
            $data = $query->where('religion', 'Muslim')
                ->select(DB::raw("COALESCE(NULLIF(muslim_sect, ''), NULLIF(caste, ''), 'Not Specified') as community"), DB::raw('COUNT(*) as total'))
                ->groupBy('community')
                ->orderByDesc('total')
                ->limit(15)
                ->get();
        } elseif ($filter === 'Jain') {
            $data = $query->where('religion', 'Jain')
                ->select(DB::raw("COALESCE(NULLIF(jain_sect, ''), NULLIF(caste, ''), 'Not Specified') as community"), DB::raw('COUNT(*) as total'))
                ->groupBy('community')
                ->orderByDesc('total')
                ->limit(15)
                ->get();
        } else {
            // Hindu and others — use caste
            $data = $query->where('religion', $filter)
                ->select(DB::raw("COALESCE(NULLIF(caste, ''), 'Not Specified') as community"), DB::raw('COUNT(*) as total'))
                ->groupBy('community')
                ->orderByDesc('total')
                ->limit(15)
                ->get();
        }

        $colors = [
            '#8B1D91', '#3B82F6', '#10B981', '#F59E0B', '#EF4444',
            '#6366F1', '#EC4899', '#14B8A6', '#F97316', '#84CC16',
            '#06B6D4', '#A855F7', '#D946EF', '#0EA5E9', '#64748B',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Profiles',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $data->count()),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $data->pluck('community')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => ['beginAtZero' => true],
            ],
        ];
    }
}
