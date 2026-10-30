<?php

namespace App\Filament\Widgets;

use App\Support\ChartColors;
use App\Support\MemberMix;
use Filament\Widgets\ChartWidget;

/** Insights → Sign-up Sources: each source's sign-ups per month, the last 6 months (stacked). */
class SourcesByMonthChart extends ChartWidget
{
    protected ?string $heading = 'Sources by month';
    protected ?string $description = 'Sign-ups per month by their answer, the last 6 months. Unanswered and staff-added members are in the table above, not here.';
    protected static bool $isLazy = true;
    protected static bool $isDiscovered = false;
    protected ?string $maxHeight = '320px';
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return \App\Support\Permissions::can('view_engagement_reports');
    }

    protected function getData(): array
    {
        $data = MemberMix::sourcesByMonth(6, 6);
        $datasets = [];
        $slot = 0;
        foreach ($data['series'] as $source => $values) {
            // fixed order, never cycled: six named sources, then "Other answers"
            $datasets[] = ChartColors::bar($source, $values, ChartColors::SERIES[min($slot++, count(ChartColors::SERIES) - 1)], 56);
        }

        return ['datasets' => $datasets, 'labels' => $data['labels']];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        $options = ChartColors::axisOptions(stacked: true);
        $options['interaction'] = ['mode' => 'index', 'intersect' => false];

        return $options;
    }
}
