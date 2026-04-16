<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhotoApprovalResource\Pages;
use App\Models\ProfilePhoto;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PhotoApprovalResource extends Resource
{
    protected static ?string $model = ProfilePhoto::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Photo Approvals';
    protected static ?string $modelLabel = 'Photo';
    protected static ?string $pluralModelLabel = 'Photo Approvals';
    protected static \UnitEnum|string|null $navigationGroup = 'Verification';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = ProfilePhoto::where('approval_status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_url')
                    ->label('Photo')
                    ->disk('public')
                    ->size(100)
                    ->square(),

                Tables\Columns\TextColumn::make('profile.matri_id')
                    ->label('Matri ID')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->url(fn (ProfilePhoto $record): string => UserResource::getUrl('view', ['record' => $record->profile_id])),

                Tables\Columns\TextColumn::make('profile.full_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('photo_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'profile' => 'success',
                        'album' => 'info',
                        'family' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('approval_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('rejection_reason')
                    ->label('Reason')
                    ->placeholder('-')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('approval_status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending'),

                Tables\Filters\SelectFilter::make('photo_type')
                    ->label('Type')
                    ->options([
                        'profile' => 'Profile',
                        'album' => 'Album',
                        'family' => 'Family',
                    ]),
            ])
            ->actions([
                // Approve
                \Filament\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (ProfilePhoto $record): void {
                        $record->update([
                            'approval_status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'rejection_reason' => null,
                        ]);

                        // If this is a profile photo and no primary exists, set as primary
                        if ($record->photo_type === 'profile') {
                            $hasPrimary = $record->profile->profilePhotos()
                                ->where('is_primary', true)->where('is_visible', true)->approved()->exists();
                            if (!$hasPrimary) {
                                $record->profile->profilePhotos()->update(['is_primary' => false]);
                                $record->update(['is_primary' => true]);
                            }
                        }

                        // Send notification to user
                        \App\Models\Notification::create([
                            'profile_id' => $record->profile_id,
                            'type' => 'photo_approved',
                            'title' => ucfirst($record->photo_type) . ' photo approved',
                            'message' => 'Your ' . $record->photo_type . ' photo has been approved and is now visible to other members.',
                            'is_read' => false,
                        ]);
                    })
                    ->visible(fn (ProfilePhoto $record): bool => $record->approval_status !== 'approved')
                    ->successNotificationTitle('Photo approved'),

                // Reject
                \Filament\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Select::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->options(ProfilePhoto::REJECTION_REASONS),
                        Forms\Components\Textarea::make('custom_note')
                            ->label('Additional Note (optional)')
                            ->rows(2)
                            ->placeholder('Optional note for the user...'),
                    ])
                    ->action(function (ProfilePhoto $record, array $data): void {
                        $reason = ProfilePhoto::REJECTION_REASONS[$data['rejection_reason']] ?? $data['rejection_reason'];
                        if (!empty($data['custom_note'])) {
                            $reason .= ' — ' . $data['custom_note'];
                        }

                        $record->update([
                            'approval_status' => 'rejected',
                            'rejection_reason' => $reason,
                            'is_primary' => false,
                        ]);

                        // Send notification to user
                        \App\Models\Notification::create([
                            'profile_id' => $record->profile_id,
                            'type' => 'photo_rejected',
                            'title' => ucfirst($record->photo_type) . ' photo rejected',
                            'message' => 'Your ' . $record->photo_type . ' photo was rejected. Reason: ' . $reason . '. Please upload a new photo.',
                            'is_read' => false,
                        ]);
                    })
                    ->visible(fn (ProfilePhoto $record): bool => $record->approval_status !== 'rejected')
                    ->successNotificationTitle('Photo rejected'),

                // Delete permanently
                \Filament\Actions\Action::make('deletePermanently')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('This will permanently delete the photo file from the server.')
                    ->action(function (ProfilePhoto $record): void {
                        if ($record->photo_url && \Illuminate\Support\Facades\Storage::disk('public')->exists($record->photo_url)) {
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($record->photo_url);
                        }
                        $record->delete();
                    })
                    ->successNotificationTitle('Photo deleted'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkAction::make('bulkApprove')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records): void {
                        $records->each(function (ProfilePhoto $record) {
                            $record->update([
                                'approval_status' => 'approved',
                                'approved_by' => auth()->id(),
                                'approved_at' => now(),
                                'rejection_reason' => null,
                            ]);
                        });
                    })
                    ->deselectRecordsAfterCompletion()
                    ->successNotificationTitle('Selected photos approved'),

                \Filament\Actions\BulkAction::make('bulkReject')
                    ->label('Reject Selected')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->options(ProfilePhoto::REJECTION_REASONS),
                    ])
                    ->action(function ($records, array $data): void {
                        $reason = ProfilePhoto::REJECTION_REASONS[$data['rejection_reason']] ?? $data['rejection_reason'];
                        $records->each(function (ProfilePhoto $record) use ($reason) {
                            $record->update([
                                'approval_status' => 'rejected',
                                'rejection_reason' => $reason,
                                'is_primary' => false,
                            ]);
                        });
                    })
                    ->deselectRecordsAfterCompletion()
                    ->successNotificationTitle('Selected photos rejected'),
            ])
            ->searchPlaceholder('Search by matri ID or name...')
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPhotoApprovals::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['profile']);
    }
}
