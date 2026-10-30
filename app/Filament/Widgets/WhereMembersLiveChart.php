<?php

namespace App\Filament\Widgets;

use App\Support\ChartColors;
use App\Support\MemberMix;
use Filament\Widgets\ChartWidget;

/** Insights → Members Overview: the country members live in, or their home district. */
class WhereMembersLiveChart extends ChartWidget
{
    protected ?string $heading = 'Where members live';
    protected static ?int $sort = 4;
    protected static bool $isLazy = true;
    protected static bool $isDiscovered = false;
    protected ?string $maxHeight = '320px';
    protected int|string|array $columnSpan = 1;

    public ?string $filter = 'country';

    public static function canView(): bool
    {
        return \App\Support\Permissions::can('view_user_reports');
    }

    public function getDescription(): ?string
    {
        return $this->filter === 'district'
            ? 'Live profiles by home (native) district.'
            : 'Live profiles by the country they live in now.';
    }

    protected function getFilters(): ?array
    {
        return ['country' => 'Country they live in', 'district' => 'Home district'];
    }

    protected function getData(): array
    {
        $counts = $this->filter === 'district' ? MemberMix::homeDistricts() : MemberMix::countries();

        return [
            'datasets' => [
                ChartColors::bar('Live profiles', array_values($counts), ChartColors::SINGLE, 22),
            ],
            'labels' => array_keys($counts),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        $options = ChartColors::axisOptions(horizontal: true);
        $options['plugins']['legend']['display'] = false; // one series: the heading names it

        return $options;
    }
}
