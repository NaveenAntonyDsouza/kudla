<?php

namespace App\Filament\Pages;

use App\Models\ProfileNote;
use BackedEnum;
use Filament\Pages\Page;

class FollowUpReport extends Page
{
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Follow-up Report';
    protected static \UnitEnum|string|null $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 7;
    protected static ?string $title = 'Follow-up Report';
    protected string $view = 'filament.pages.follow-up-report';

    public static function getNavigationBadge(): ?string
    {
        $count = ProfileNote::whereNotNull('follow_up_date')
            ->where('follow_up_date', '<=', today())
            ->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public function getOverdueFollowUps()
    {
        return ProfileNote::query()
            ->whereNotNull('follow_up_date')
            ->where('follow_up_date', '<', today())
            ->with(['profile.primaryPhoto', 'profile.user', 'adminUser'])
            ->orderBy('follow_up_date')
            ->get();
    }

    public function getTodayFollowUps()
    {
        return ProfileNote::query()
            ->whereNotNull('follow_up_date')
            ->whereDate('follow_up_date', today())
            ->with(['profile.primaryPhoto', 'profile.user', 'adminUser'])
            ->orderBy('follow_up_date')
            ->get();
    }

    public function getUpcomingFollowUps()
    {
        return ProfileNote::query()
            ->whereNotNull('follow_up_date')
            ->where('follow_up_date', '>', today())
            ->where('follow_up_date', '<=', today()->addDays(7))
            ->with(['profile.primaryPhoto', 'profile.user', 'adminUser'])
            ->orderBy('follow_up_date')
            ->get();
    }
}
