<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class MyUpcomingFollowups extends TableWidget
{
    protected static ?string $heading = 'My Follow-ups (Next 7 Days + Overdue)';
    protected static ?int $sort = -7;
    protected static bool $isLazy = true;
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        if (!$user || !$user->staff_role_id) {
            return false;
        }

        return $user->hasPermission('view_lead') || $user->isSuperAdmin();
    }

    public function table(Table $table): Table
    {
        $userId = auth()->id();

        return $table
            ->query(
                Lead::query()
                    ->where('assigned_to_staff_id', $userId)
                    ->whereNotNull('follow_up_date')
                    ->whereDate('follow_up_date', '<=', now()->addDays(7))
                    ->whereNotIn('status', ['registered', 'lost'])
                    ->orderBy('follow_up_date', 'asc')
                    ->limit(15)
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->weight('bold')
                    ->color('primary')
                    ->url(fn (Lead $record): string => route('filament.admin.resources.leads.edit', $record)),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->copyable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => Lead::statuses()[$state]['label'] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => Lead::statuses()[$state]['color'] ?? 'gray'),

                Tables\Columns\TextColumn::make('follow_up_date')
                    ->label('Follow-up Date')
                    ->date('M j, Y')
                    ->color(function (Lead $record): string {
                        if ($record->is_overdue) return 'danger';
                        if ($record->follow_up_date?->isToday()) return 'warning';
                        return 'success';
                    })
                    ->weight(fn (Lead $record): ?string => $record->is_overdue ? 'bold' : null),

                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->formatStateUsing(fn (string $state): string => Lead::sources()[$state] ?? $state)
                    ->badge()
                    ->color('info')
                    ->toggleable(),
            ])
            ->paginated(false)
            ->emptyStateHeading('No upcoming follow-ups')
            ->emptyStateDescription('You have no leads with follow-ups due in the next 7 days.');
    }
}
