<?php

namespace App\Filament\Widgets;

use App\Models\Profile;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentRegistrations extends TableWidget
{
    protected static ?string $heading = 'Recent Registrations';
    protected static ?int $sort = 8;
    protected static bool $isLazy = true;
    protected int|string|array $columnSpan = 'full';

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
                    ->with(['religiousInfo', 'user'])
                    ->orderByDesc('created_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('matri_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('gender')
                    ->badge()
                    ->color(fn(string $state): string => $state === 'male' ? 'info' : 'danger'),

                Tables\Columns\TextColumn::make('religiousInfo.religion')
                    ->label('Religion')
                    ->default('-'),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->limit(25)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('profile_completion_pct')
                    ->label('Complete')
                    ->suffix('%')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->since()
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
