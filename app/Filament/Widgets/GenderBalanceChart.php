<?php

namespace App\Filament\Widgets;

use App\Support\ChartColors;
use App\Support\MemberMix;
use Filament\Widgets\ChartWidget;

/** Insights → Members Overview: men and women, live and active. */
class GenderBalanceChart extends ChartWidget
{
    protected ?string $heading = 'Men and women';
    protected ?string $description = 'Live profiles, and those who used the site in the last 30 days.';
    protected static ?int $sort = 2;
    protected static bool $isLazy = true;
    protected static bool $isDiscovered = false;
    protected ?string $maxHeight = '260px';
    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return \App\Support\Permissions::can('view_user_reports');
    }

    protected function getData(): array
    {
        $b = MemberMix::genderBalance();

        return [
            'datasets' => [
                ChartColors::bar('Men', [$b['live']['male'], $b['active']['male']], ChartColors::MEN, 48),
                ChartColors::bar('Women', [$b['live']['female'], $b['active']['female']], ChartColors::WOMEN, 48),
            ],
            'labels' => ['Live profiles', 'Active, last 30 days'],
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
