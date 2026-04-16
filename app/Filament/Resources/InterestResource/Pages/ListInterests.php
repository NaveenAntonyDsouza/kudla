<?php

namespace App\Filament\Resources\InterestResource\Pages;

use App\Filament\Resources\InterestResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInterests extends ListRecords
{
    protected static string $resource = InterestResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => \App\Models\Interest::where('status', 'pending')->count() ?: null)
                ->badgeColor('warning'),
            'accepted' => Tab::make('Accepted')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'accepted'))
                ->badge(fn () => \App\Models\Interest::where('status', 'accepted')->count() ?: null)
                ->badgeColor('success'),
            'declined' => Tab::make('Declined')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'declined')),
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),
        ];
    }
}
