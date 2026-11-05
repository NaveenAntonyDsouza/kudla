<?php

namespace App\Filament\Pages;

use App\Models\Profile;
use BackedEnum;
use Filament\Pages\Page;

class DeleteRequests extends Page
{
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Delete Requests';
    protected static \UnitEnum|string|null $navigationGroup = 'Today';
    protected static ?int $navigationSort = 8;
    protected static ?string $title = 'Deleted & Deactivated Users';
    protected string $view = 'filament.pages.delete-requests';

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('view_member');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('view_member');
    }

    // deletions in the last 7 days, for information, as on the Work Queue (6 Nov 2026: the
    // badge counted every deletion ever, 66 on Kudla, in red in the Today group, though
    // there is nothing to do for them)
    public static function getNavigationBadge(): ?string
    {
        $count = Profile::onlyTrashed()->whereNotNull('full_name')->where('deleted_at', '>=', now()->subDays(7))->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'gray';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Accounts deleted in the last 7 days';
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
