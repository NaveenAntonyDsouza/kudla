<?php

namespace App\Support;

/**
 * Colours for the admin charts (Insights, Oct 2026). One validated set for
 * both the light and the dark admin theme (checked for colour-blind
 * separation and contrast against white and against the dark card colour).
 * Use the slots in this order and never cycle them: a series beyond the
 * seventh folds into "Other". Men and women keep the same colours on every
 * chart.
 */
class ChartColors
{
    public const SERIES = ['#3987e5', '#d95926', '#199e70', '#c98500', '#d55181', '#008300', '#9085e9'];

    public const MEN = '#3987e5';

    public const WOMEN = '#d95926';

    /** Single-series charts. */
    public const SINGLE = '#3987e5';

    /**
     * Bar datasets: no outline (Filament otherwise outlines every bar in the
     * panel's purple, which muddies the fills), rounded ends.
     */
    public static function bar(string $label, array $data, string $color, int $maxThickness = 40): array
    {
        return ['label' => $label, 'data' => $data, 'backgroundColor' => $color, 'borderWidth' => 0, 'borderRadius' => 4, 'maxBarThickness' => $maxThickness];
    }

    /** Grid lines and axis ticks that stay in the background in both themes. */
    public const GRID = 'rgba(127,127,127,0.15)';

    /** Chart.js options shared by the bar and line charts: quiet grid, whole-number axis. */
    public static function axisOptions(bool $horizontal = false, bool $stacked = false): array
    {
        $value = ['beginAtZero' => true, 'ticks' => ['precision' => 0], 'grid' => ['color' => self::GRID], 'stacked' => $stacked];
        $category = ['grid' => ['display' => false], 'stacked' => $stacked];

        return [
            'indexAxis' => $horizontal ? 'y' : 'x',
            'scales' => $horizontal ? ['x' => $value, 'y' => $category] : ['x' => $category, 'y' => $value],
            'plugins' => ['legend' => ['display' => true, 'position' => 'bottom']],
            'maintainAspectRatio' => false,
        ];
    }
}
