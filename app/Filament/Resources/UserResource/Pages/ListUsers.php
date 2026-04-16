<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Profile'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Members')
                ->icon('heroicon-o-users'),

            'pending' => Tab::make('Pending Approval')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_approved', false))
                ->badge(fn () => \App\Models\Profile::whereNotNull('full_name')->where('is_approved', false)->count())
                ->badgeColor('warning'),

            'incomplete' => Tab::make('Incomplete')
                ->icon('heroicon-o-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('profile_completion_pct', '<', 60)),

            'premium' => Tab::make('Premium')
                ->icon('heroicon-o-star')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('user.userMemberships', function ($q) {
                    $q->where('is_active', true)
                        ->where(fn ($q2) => $q2->whereNull('ends_at')->orWhere('ends_at', '>', now()));
                })),

            'free' => Tab::make('Free Users')
                ->icon('heroicon-o-user')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDoesntHave('user.userMemberships', function ($q) {
                    $q->where('is_active', true)
                        ->where(fn ($q2) => $q2->whereNull('ends_at')->orWhere('ends_at', '>', now()));
                })),

            'expiring' => Tab::make('Expiring Soon')
                ->icon('heroicon-o-exclamation-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('user.userMemberships', function ($q) {
                    $q->where('is_active', true)
                        ->whereBetween('ends_at', [now(), now()->addDays(7)]);
                })),

            'recent' => Tab::make('Recent (7 days)')
                ->icon('heroicon-o-sparkles')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->subDays(7))),

            'inactive' => Tab::make('Inactive (30+ days)')
                ->icon('heroicon-o-moon')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('user', function ($q) {
                    $q->where(function ($q2) {
                        $q2->where('last_login_at', '<', now()->subDays(30))
                            ->orWhereNull('last_login_at');
                    });
                })),

            'deactivated' => Tab::make('Blocked / Deactivated')
                ->icon('heroicon-o-no-symbol')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($q) {
                    $q->where('is_active', false)->orWhere('is_hidden', true);
                })),

            'deleted' => Tab::make('Deleted')
                ->icon('heroicon-o-trash')
                ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes()->withTrashed()->whereNotNull('deleted_at')->whereNotNull('full_name'))
                ->badge(fn () => \App\Models\Profile::onlyTrashed()->whereNotNull('full_name')->count() ?: null)
                ->badgeColor('danger'),
        ];
    }
}
