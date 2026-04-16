<?php

namespace App\Filament\Resources\DocumentApprovalResource\Pages;

use App\Filament\Resources\DocumentApprovalResource;
use App\Models\ReligiousInfo;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDocumentApprovals extends ListRecords
{
    protected static string $resource = DocumentApprovalResource::class;

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make('Pending')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jathakam_approval_status', 'pending'))
                ->badge(fn () => ReligiousInfo::whereNotNull('jathakam_upload_url')->where('jathakam_approval_status', 'pending')->count() ?: null)
                ->badgeColor('warning'),

            'all' => Tab::make('All')
                ->icon('heroicon-o-document-text'),

            'approved' => Tab::make('Approved')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jathakam_approval_status', 'approved')),

            'rejected' => Tab::make('Rejected')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('jathakam_approval_status', 'rejected')),
        ];
    }
}
