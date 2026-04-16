<?php

namespace App\Filament\Resources\PhotoApprovalResource\Pages;

use App\Filament\Resources\PhotoApprovalResource;
use App\Models\ProfilePhoto;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPhotoApprovals extends ListRecords
{
    protected static string $resource = PhotoApprovalResource::class;

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make('Pending')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('approval_status', 'pending'))
                ->badge(fn () => ProfilePhoto::where('approval_status', 'pending')->count() ?: null)
                ->badgeColor('warning'),

            'all' => Tab::make('All Photos')
                ->icon('heroicon-o-photo'),

            'approved' => Tab::make('Approved')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('approval_status', 'approved')),

            'rejected' => Tab::make('Rejected')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('approval_status', 'rejected')),
        ];
    }
}
