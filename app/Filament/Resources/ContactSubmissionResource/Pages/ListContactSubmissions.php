<?php

namespace App\Filament\Resources\ContactSubmissionResource\Pages;

use App\Filament\Resources\ContactSubmissionResource;
use App\Models\ContactSubmission;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListContactSubmissions extends ListRecords
{
    protected static string $resource = ContactSubmissionResource::class;

    public function getTabs(): array
    {
        return [
            // one row per sender: their newest waiting message, with how many wait (6 Nov 2026)
            'new' => Tab::make('New')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'new')->newestPerSender(['new']))
                ->badge(fn () => ContactSubmission::sendersWaiting() ?: null)
                ->badgeColor('danger'),
            'all' => Tab::make('All'),
            'replied' => Tab::make('Replied')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'replied')),
            'closed' => Tab::make('Closed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'closed')),
        ];
    }
}
