<?php

namespace App\Filament\Widgets;

use App\Models\ProfileNote;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingFollowups extends TableWidget
{
    protected static ?string $heading = 'Upcoming Follow-ups';
    protected static ?int $sort = 10;
    protected static bool $isLazy = true;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProfileNote::query()
                    ->whereNotNull('follow_up_date')
                    ->where('follow_up_date', '<=', today())
                    ->with(['profile', 'adminUser'])
                    ->orderBy('follow_up_date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('profile.matri_id')
                    ->label('Matri ID')
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('profile.full_name')
                    ->label('Name')
                    ->limit(20),

                Tables\Columns\TextColumn::make('note')
                    ->label('Note')
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('follow_up_date')
                    ->label('Follow-up')
                    ->date('d M Y')
                    ->badge()
                    ->color(fn($state): string => $state < today() ? 'danger' : 'warning'),

                Tables\Columns\TextColumn::make('adminUser.name')
                    ->label('Added By')
                    ->limit(15),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Note Date')
                    ->since(),
            ])
            ->emptyStateHeading('No follow-ups due')
            ->emptyStateDescription('Add notes with follow-up dates on user profiles to see them here.')
            ->paginated(false);
    }
}
