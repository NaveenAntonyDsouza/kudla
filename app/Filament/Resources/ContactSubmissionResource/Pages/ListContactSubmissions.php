<?php

namespace App\Filament\Resources\ContactSubmissionResource\Pages;

use App\Filament\Resources\ContactSubmissionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListContactSubmissions extends ListRecords
{
    protected static string $resource = ContactSubmissionResource::class;

    public function getTabs(): array
    {
        return [
            'new' => Tab::make('New')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'new'))
                ->badge(fn () => \App\Models\ContactSubmission::where('status', 'new')->count() ?: null)
                ->badgeColor('danger'),
            'all' => Tab::make('All'),
            'replied' => Tab::make('Replied')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'replied')),
            'closed' => Tab::make('Closed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'closed')),
        ];
    }
}
