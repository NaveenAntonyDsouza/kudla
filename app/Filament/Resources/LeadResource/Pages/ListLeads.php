<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Models\Lead;
use App\Support\Permissions;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Lead')
                ->visible(fn () => Permissions::can('add_lead')),
        ];
    }

    public function getTabs(): array
    {
        $userId = auth()->id();
        $user = auth()->user();
        $scope = $user?->permissionScope('view_lead');
        $isAll = $user?->isSuperAdmin() || $scope === 'all';

        $tabs = [];

        if ($isAll) {
            $tabs['all'] = Tab::make('All Leads')
                ->icon('heroicon-o-list-bullet');
        }

        $tabs['mine'] = Tab::make('My Leads')
            ->icon('heroicon-o-user')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('assigned_to_staff_id', $userId))
            ->badge(fn () => Lead::where('assigned_to_staff_id', $userId)->count() ?: null);

        if ($isAll) {
            $tabs['unassigned'] = Tab::make('Unassigned')
                ->icon('heroicon-o-question-mark-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('assigned_to_staff_id'))
                ->badge(fn () => Lead::whereNull('assigned_to_staff_id')->count() ?: null)
                ->badgeColor('warning');
        }

        $tabs['follow_up_today'] = Tab::make('Follow-up Today')
            ->icon('heroicon-o-calendar')
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->whereDate('follow_up_date', today())
                ->whereNotIn('status', ['registered', 'lost'])
            );

        $tabs['overdue'] = Tab::make('Overdue')
            ->icon('heroicon-o-exclamation-triangle')
            ->modifyQueryUsing(fn (Builder $query) => $query->overdue())
            ->badgeColor('danger');

        $tabs['converted'] = Tab::make('Converted')
            ->icon('heroicon-o-check-badge')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'registered'));

        $tabs['lost'] = Tab::make('Lost')
            ->icon('heroicon-o-x-circle')
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['lost', 'not_interested']));

        return $tabs;
    }
}
