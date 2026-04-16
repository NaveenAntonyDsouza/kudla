<?php

namespace App\Filament\Resources\ProfileReportResource\Pages;

use App\Filament\Resources\ProfileReportResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProfileReports extends ListRecords
{
    protected static string $resource = ProfileReportResource::class;

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => \App\Models\ProfileReport::where('status', 'pending')->count() ?: null)
                ->badgeColor('warning'),
            'all' => Tab::make('All'),
            'resolved' => Tab::make('Resolved')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['reviewed', 'action_taken', 'dismissed'])),
        ];
    }
}
