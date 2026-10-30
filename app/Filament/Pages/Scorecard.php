<?php

namespace App\Filament\Pages;

use App\Support\Permissions;
use Filament\Pages\Page;

/**
 * Insights → Scorecard: the owner's weekly numbers, this week against last
 * week (App\Support\Scorecard). Money rows only for people who may see
 * payments.
 */
class Scorecard extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Scorecard';
    protected static ?string $title = 'Weekly Scorecard';
    protected static \UnitEnum|string|null $navigationGroup = 'Insights';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.scorecard';

    public static function canAccess(): bool
    {
        return Permissions::can('view_engagement_reports');
    }

    /** Three small charts under the table: the last 12 weeks, each measure on its own scale. */
    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\WeeklyTrendChart::make(['metric' => 'signups']),
            \App\Filament\Widgets\WeeklyTrendChart::make(['metric' => 'interests']),
            \App\Filament\Widgets\WeeklyTrendChart::make(['metric' => 'connections']),
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return ['default' => 1, 'lg' => 3];
    }

    protected function getViewData(): array
    {
        $showMoney = Permissions::can('view_payment_history');

        return [
            'rows' => array_values(array_filter(\App\Support\Scorecard::rows(), fn ($r) => $showMoney || ! $r['money'])),
            'freeMode' => \App\Support\Scorecard::freeMode(),
            'from' => now()->subDays(7),
        ];
    }
}
