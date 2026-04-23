<?php

namespace App\Filament\Widgets;

use App\Models\Profile;
use Filament\Widgets\ChartWidget;

class GenderDistribution extends ChartWidget
{
    protected ?string $heading = 'Gender Distribution';
    protected static ?int $sort = 5;
    protected static bool $isLazy = true;
    protected ?string $maxHeight = '250px';
    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return \App\Support\Permissions::can('view_user_reports');
    }

    protected function getData(): array
    {
        $male = Profile::where('is_active', true)->where('gender', 'male')->count();
        $female = Profile::where('is_active', true)->where('gender', 'female')->count();

        return [
            'datasets' => [
                [
                    'data' => [$male, $female],
                    'backgroundColor' => ['#3B82F6', '#EC4899'],
                ],
            ],
            'labels' => ["Male ($male)", "Female ($female)"],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
