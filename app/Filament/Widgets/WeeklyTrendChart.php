<?php

namespace App\Filament\Widgets;

use App\Support\ChartColors;
use App\Support\MemberMix;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

/**
 * Insights → Scorecard: one measure over the last 12 weeks. Three of these
 * side by side (sign-ups, interests, connections), each on its own scale.
 */
class WeeklyTrendChart extends ChartWidget
{
    protected static bool $isLazy = true;
    protected static bool $isDiscovered = false;
    protected ?string $maxHeight = '200px';
    protected int|string|array $columnSpan = 1;

    /** signups | interests | connections */
    public string $metric = 'signups';

    private const TITLES = [
        'signups' => ['Sign-ups per week', 'New member accounts'],
        'interests' => ['Interests per week', 'Sent, not withdrawn'],
        'connections' => ['Connections per week', 'Interests accepted'],
    ];

    public static function canView(): bool
    {
        return \App\Support\Permissions::can('view_engagement_reports');
    }

    public function getHeading(): ?string
    {
        return self::TITLES[$this->metric][0] ?? null;
    }

    public function getDescription(): ?string
    {
        return (self::TITLES[$this->metric][1] ?? '') . ', last 12 weeks';
    }

    protected function getData(): array
    {
        $trend = Cache::remember('insights_weekly_trend', 600, fn () => MemberMix::weeklyTrend(12));

        return [
            'datasets' => [[
                'label' => self::TITLES[$this->metric][0] ?? $this->metric,
                'data' => $trend[$this->metric] ?? [],
                'borderColor' => ChartColors::SINGLE,
                'backgroundColor' => ChartColors::SINGLE,
                'borderWidth' => 2,
                'pointRadius' => 3,
                'pointHoverRadius' => 5,
                'tension' => 0.25,
                'fill' => false,
            ]],
            'labels' => $trend['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        $options = ChartColors::axisOptions();
        $options['plugins']['legend']['display'] = false; // one series: the heading names it
        $options['interaction'] = ['mode' => 'index', 'intersect' => false];
        // a third of the page wide: fewer dates, kept level
        $options['scales']['x']['ticks'] = ['autoSkip' => true, 'maxTicksLimit' => 6, 'maxRotation' => 0];

        return $options;
    }
}
