<?php

namespace App\Filament\Pages;

use App\Models\Profile;
use BackedEnum;
use Filament\Pages\Page;

class DeleteRequests extends Page
{
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Delete Requests';
    protected static \UnitEnum|string|null $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 6;
    protected static ?string $title = 'Deleted & Deactivated Users';
    protected string $view = 'filament.pages.delete-requests';

    public static function getNavigationBadge(): ?string
    {
        $count = Profile::onlyTrashed()->whereNotNull('full_name')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public function getDeletedProfiles()
    {
        return Profile::onlyTrashed()
            ->whereNotNull('full_name')
            ->with(['user', 'religiousInfo', 'locationInfo', 'primaryPhoto'])
            ->orderByDesc('deleted_at')
            ->limit(50)
            ->get();
    }

    public function getDeactivatedProfiles()
    {
        return Profile::query()
            ->whereNotNull('full_name')
            ->where('is_active', false)
            ->with(['user', 'religiousInfo', 'locationInfo', 'primaryPhoto'])
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();
    }
}
