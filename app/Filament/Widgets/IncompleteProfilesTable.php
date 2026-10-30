<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\UserResource;
use App\Models\Profile;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Insights → Members Overview: live profiles less than half complete, emptiest first. */
class IncompleteProfilesTable extends TableWidget
{
    protected static ?string $heading = 'Profiles less than half complete';
    protected static ?int $sort = 9;
    protected static bool $isLazy = true;
    protected static bool $isDiscovered = false;
    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return \App\Support\Permissions::can('view_user_reports');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Profile::query()
                    ->whereNotNull('full_name')
                    ->where('is_active', true)
                    ->where('profile_completion_pct', '<', 50)
                    ->orderBy('profile_completion_pct')
            )
            ->columns([
                Tables\Columns\TextColumn::make('matri_id')->label('Matri ID')->weight('bold')->color('primary')
                    ->url(fn (Profile $record) => UserResource::getUrl('view', ['record' => $record])),
                Tables\Columns\TextColumn::make('full_name')->label('Name')->limit(22),
                Tables\Columns\TextColumn::make('profile_completion_pct')->label('Complete')->suffix('%'),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
