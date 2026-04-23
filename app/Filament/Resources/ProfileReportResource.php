<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfileReportResource\Pages;
use App\Models\ProfileReport;
use BackedEnum;
use Filament\Forms;
use Filament\Infolists;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProfileReportResource extends Resource
{
    protected static ?string $model = ProfileReport::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Profile Reports';
    protected static ?string $modelLabel = 'Report';
    protected static ?string $pluralModelLabel = 'Profile Reports';
    protected static \UnitEnum|string|null $navigationGroup = 'Interests & Reports';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('view_member');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('view_member');
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return static::canAccess();
    }

    public static function canDelete($record): bool
    {
        return static::canAccess();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reporterProfile.matri_id')
                    ->label('Reported By')
                    ->searchable()
                    ->description(fn (ProfileReport $record) => $record->reporterProfile?->full_name)
                    ->url(fn (ProfileReport $record) => $record->reporter_profile_id
                        ? UserResource::getUrl('view', ['record' => $record->reporter_profile_id])
                        : null),

                Tables\Columns\TextColumn::make('reportedProfile.matri_id')
                    ->label('Reported Profile')
                    ->searchable()
                    ->description(fn (ProfileReport $record) => $record->reportedProfile?->full_name)
                    ->url(fn (ProfileReport $record) => $record->reported_profile_id
                        ? UserResource::getUrl('view', ['record' => $record->reported_profile_id])
                        : null),

                Tables\Columns\TextColumn::make('reason')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ProfileReport::reasons()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'harassment', 'fraud' => 'danger',
                        'fake_profile', 'already_married' => 'warning',
                        'inappropriate_photo', 'wrong_info' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Details')
                    ->limit(40)
                    ->placeholder('-')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'reviewed' => 'info',
                        'action_taken' => 'success',
                        'dismissed' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Reported')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'reviewed' => 'Reviewed',
                        'action_taken' => 'Action Taken',
                        'dismissed' => 'Dismissed',
                    ]),
                Tables\Filters\SelectFilter::make('reason')
                    ->options(ProfileReport::reasons()),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),

                \Filament\Actions\Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ProfileReport $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Select::make('new_status')
                            ->label('Action')
                            ->options([
                                'reviewed' => 'Mark as Reviewed (no action needed)',
                                'action_taken' => 'Action Taken (user warned/blocked)',
                                'dismissed' => 'Dismiss Report (false report)',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->rows(3)
                            ->placeholder('What action was taken?'),
                    ])
                    ->action(function (ProfileReport $record, array $data) {
                        $record->update([
                            'status' => $data['new_status'],
                            'admin_notes' => $data['admin_notes'],
                            'reviewed_at' => now(),
                        ]);
                        Notification::make()
                            ->title('Report resolved')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['reporterProfile', 'reportedProfile']));
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist->schema([
            \Filament\Schemas\Components\Section::make('Report Details')
                ->schema([
                    Infolists\Components\TextEntry::make('reporterProfile.matri_id')->label('Reported By'),
                    Infolists\Components\TextEntry::make('reporterProfile.full_name')->label('Reporter Name'),
                    Infolists\Components\TextEntry::make('reportedProfile.matri_id')->label('Reported Profile'),
                    Infolists\Components\TextEntry::make('reportedProfile.full_name')->label('Reported Name'),
                    Infolists\Components\TextEntry::make('reason')
                        ->formatStateUsing(fn (string $state) => ProfileReport::reasons()[$state] ?? $state)
                        ->badge()
                        ->color('danger'),
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'pending' => 'warning',
                            'action_taken' => 'success',
                            'dismissed' => 'gray',
                            default => 'info',
                        }),
                    Infolists\Components\TextEntry::make('description')->label('Description')->columnSpanFull()->default('-'),
                    Infolists\Components\TextEntry::make('admin_notes')->label('Admin Notes')->columnSpanFull()->default('-'),
                    Infolists\Components\TextEntry::make('created_at')->label('Reported At')->dateTime('d M Y, h:i A'),
                    Infolists\Components\TextEntry::make('reviewed_at')->label('Reviewed At')->dateTime('d M Y, h:i A')->default('Not yet reviewed'),
                ])
                ->columns(3),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProfileReports::route('/'),
            'view' => Pages\ViewProfileReport::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }
}
