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

    public static function canView(): bool
    {
        return \App\Support\Permissions::can('view_user_reports');
    }

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

        // live profiles only (Oct 2026)
        $query = ReligiousInfo::query()->whereHas('profile', fn ($q) => $q->where('is_active', true));

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

        return [
            'datasets' => [
                [
                    'label' => 'Profiles',
                    'data' => $data->pluck('total')->toArray(),
                    // one series, one colour (a colour per bar suggested a meaning it didn't have)
                    'backgroundColor' => \App\Support\ChartColors::SINGLE,
                    'borderRadius' => 4,
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
