<?php

namespace App\Filament\Resources\IdProofResource\Pages;

use App\Filament\Resources\IdProofResource;
use App\Models\IdProof;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListIdProofs extends ListRecords
{
    protected static string $resource = IdProofResource::class;

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make('Pending')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('verification_status', 'pending'))
                ->badge(fn () => IdProof::where('verification_status', 'pending')->count() ?: null)
                ->badgeColor('warning'),

            'all' => Tab::make('All')
                ->icon('heroicon-o-identification'),

            'approved' => Tab::make('Verified')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('verification_status', 'approved')),

            'rejected' => Tab::make('Rejected')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('verification_status', 'rejected')),
        ];
    }
}
