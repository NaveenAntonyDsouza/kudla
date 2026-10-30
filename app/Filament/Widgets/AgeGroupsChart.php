<?php

namespace App\Filament\Widgets;

use App\Support\ChartColors;
use App\Support\MemberMix;
use Filament\Widgets\ChartWidget;

/** Insights → Members Overview: age groups, men and women apart. */
class AgeGroupsChart extends ChartWidget
{
    protected ?string $heading = 'Age groups';
    protected ?string $description = 'Men and women in each age group.';
    protected static ?int $sort = 3;
    protected static bool $isLazy = true;
    protected static bool $isDiscovered = false;
    protected ?string $maxHeight = '260px';
    protected int|string|array $columnSpan = 1;

    public ?string $filter = 'active';

    public static function canView(): bool
    {
        return \App\Support\Permissions::can('view_user_reports');
    }

    protected function getFilters(): ?array
    {
        return ['active' => 'Active, last 30 days', 'live' => 'All live profiles'];
    }

    protected function getData(): array
    {
        $groups = MemberMix::ageGroups($this->filter !== 'live');

        return [
            'datasets' => [
                ChartColors::bar('Men', array_column($groups, 'male'), ChartColors::MEN, 32),
                ChartColors::bar('Women', array_column($groups, 'female'), ChartColors::WOMEN, 32),
            ],
            'labels' => array_keys($groups),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return ChartColors::axisOptions();
    }
}
