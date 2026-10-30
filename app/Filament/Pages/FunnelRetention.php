<?php

namespace App\Filament\Pages;

use App\Support\MemberFunnel;
use App\Support\Permissions;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

/**
 * Insights → Funnel & Retention: where new members drop off between signing
 * up and connecting, and how many each month are still around 1 and 3
 * months later (App\Support\MemberFunnel).
 */
class FunnelRetention extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Funnel & Retention';
    protected static ?string $title = 'Funnel & Retention';
    protected static \UnitEnum|string|null $navigationGroup = 'Insights';
    protected static ?int $navigationSort = 2;
    protected string $view = 'filament.pages.funnel-retention';

    /** Funnel period in days: 30, 90 or 365. */
    #[Url]
    public int $days = 90;

    public static function canAccess(): bool
    {
        return Permissions::can('view_engagement_reports');
    }

    protected function getViewData(): array
    {
        $days = in_array($this->days, [30, 90, 365], true) ? $this->days : 90;

        return [
            'days' => $days,
            'steps' => MemberFunnel::funnel(now()->subDays($days), now()),
            'months' => MemberFunnel::retention(12),
        ];
    }
}
