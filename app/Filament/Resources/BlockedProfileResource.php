<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlockedProfileResource\Pages;
use App\Models\BlockedProfile;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlockedProfileResource extends Resource
{
    protected static ?string $model = BlockedProfile::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Blocked Users';
    protected static ?string $modelLabel = 'Block';
    protected static ?string $pluralModelLabel = 'Blocked Users';
    protected static \UnitEnum|string|null $navigationGroup = 'Interests & Reports';
    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('profile.matri_id')
                    ->label('Blocker')
                    ->searchable()
                    ->description(fn (BlockedProfile $record) => $record->profile?->full_name)
                    ->url(fn (BlockedProfile $record) => $record->profile_id
                        ? UserResource::getUrl('view', ['record' => $record->profile_id])
                        : null),

                Tables\Columns\TextColumn::make('blockedProfile.matri_id')
                    ->label('Blocked User')
                    ->searchable()
                    ->description(fn (BlockedProfile $record) => $record->blockedProfile?->full_name)
                    ->url(fn (BlockedProfile $record) => $record->blocked_profile_id
                        ? UserResource::getUrl('view', ['record' => $record->blocked_profile_id])
                        : null),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Blocked On')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                \Filament\Actions\Action::make('unblock')
                    ->label('Unblock')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Admin Unblock')
                    ->modalDescription(fn (BlockedProfile $record) =>
                        'Remove block between ' . ($record->profile?->matri_id ?? '?') . ' and ' . ($record->blockedProfile?->matri_id ?? '?') . '?')
                    ->action(function (BlockedProfile $record) {
                        $record->delete();
                        Notification::make()
                            ->title('Block removed successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['profile', 'blockedProfile']));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlockedProfiles::route('/'),
        ];
    }
}
