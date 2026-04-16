<?php

namespace App\Filament\Pages;

use App\Models\UserMembership;
use BackedEnum;
use Filament\Pages\Page;

class ExpiredMembers extends Page
{
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Expired Members';
    protected static \UnitEnum|string|null $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Expired & Expiring Memberships';
    protected string $view = 'filament.pages.expired-members';

    public function getExpiring()
    {
        return UserMembership::query()
            ->where('is_active', true)
            ->whereBetween('ends_at', [now(), now()->addDays(7)])
            ->with(['user.profile.primaryPhoto', 'user.profile.religiousInfo', 'plan'])
            ->orderBy('ends_at')
            ->limit(50)
            ->get();
    }

    public function getExpired()
    {
        return UserMembership::query()
            ->where(function ($q) {
                $q->where('is_active', false)->orWhere('ends_at', '<', now());
            })
            ->whereNotNull('ends_at')
            ->with(['user.profile.primaryPhoto', 'user.profile.religiousInfo', 'plan'])
            ->orderByDesc('ends_at')
            ->limit(50)
            ->get();
    }
}
