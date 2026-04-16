<?php

namespace App\Filament\Pages;

use App\Models\Profile;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;

class ActiveToPaid extends Page
{
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Active to Paid';
    protected static \UnitEnum|string|null $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Active to Paid — Conversion Targets';
    protected string $view = 'filament.pages.active-to-paid';

    public function getProfiles()
    {
        return Profile::query()
            ->whereNotNull('full_name')
            ->where('is_active', true)
            ->where('is_approved', true)
            ->whereDoesntHave('user.userMemberships', function ($q) {
                $q->where('is_active', true)
                    ->where(fn ($q2) => $q2->whereNull('ends_at')->orWhere('ends_at', '>', now()));
            })
            ->with(['user', 'religiousInfo', 'locationInfo', 'primaryPhoto'])
            ->orderByDesc('user.last_login_at')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->sortByDesc(fn ($p) => $p->user?->last_login_at);
    }
}
