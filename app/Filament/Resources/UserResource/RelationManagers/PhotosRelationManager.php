<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\ProfilePhoto;
use App\Services\WatermarkService;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'profilePhotos';
    protected static ?string $title = 'Photos';
    protected static BackedEnum|string|null $icon = 'heroicon-o-photo';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_url')
                    ->label('Photo')
                    ->disk('public')
                    ->size(80)
                    ->square(),

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

                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean(),

                Tables\Columns\TextColumn::make('display_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('display_order')
            ->filters([
                Tables\Filters\SelectFilter::make('photo_type')
                    ->label('Type')
                    ->options([
                        'profile' => 'Profile',
                        'album' => 'Album',
                        'family' => 'Family',
                    ]),
                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('Visibility')
                    ->trueLabel('Visible')
                    ->falseLabel('Archived'),
            ])
            ->headerActions([
                // Upload photo on behalf of user
                \Filament\Actions\Action::make('uploadPhoto')
                    ->label('Upload Photo')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->form([
                        Forms\Components\FileUpload::make('photo')
                            ->label('Photo')
                            ->required()
                            ->image()
                            ->disk('public')
                            ->directory(fn () => 'photos/' . $this->getOwnerRecord()->id)
                            ->maxSize(5120)
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio(null),
                        Forms\Components\Select::make('photo_type')
                            ->label('Photo Type')
                            ->required()
                            ->options([
                                'profile' => 'Profile Photo',
                                'album' => 'Album Photo',
                                'family' => 'Family Photo',
                            ]),
                    ])
                    ->action(function (array $data): void {
                        $profile = $this->getOwnerRecord();
                        $type = $data['photo_type'];
                        $path = $data['photo'];

                        // Apply watermark
                        try {
                            app(WatermarkService::class)->apply($path);
                        } catch (\Throwable $e) {
                            // Watermark failure shouldn't block upload
                        }

                        // For profile type, archive existing
                        if ($type === 'profile') {
                            $profile->profilePhotos()->visible()->ofType('profile')
                                ->update(['is_visible' => false, 'is_primary' => false]);
                        }

                        // Check count limits
                        if ($type !== 'profile') {
                            $currentCount = $profile->profilePhotos()->visible()->ofType($type)->count();
                            $max = ProfilePhoto::maxForType($type);
                            if ($currentCount >= $max) {
                                \Filament\Notifications\Notification::make()
                                    ->title("Maximum {$max} {$type} photo(s) allowed")
                                    ->danger()
                                    ->send();
                                return;
                            }
                        }

                        $isPrimary = $type === 'profile';
                        if ($isPrimary) {
                            $profile->profilePhotos()->update(['is_primary' => false]);
                        }

                        $nextOrder = $profile->profilePhotos()->ofType($type)->max('display_order') + 1;

                        ProfilePhoto::create([
                            'profile_id' => $profile->id,
                            'photo_type' => $type,
                            'photo_url' => $path,
                            'thumbnail_url' => $path,
                            'is_primary' => $isPrimary,
                            'is_visible' => true,
                            'display_order' => $nextOrder,
                        ]);
                    })
                    ->successNotificationTitle('Photo uploaded'),
            ])
            ->actions([
                // Set as primary
                \Filament\Actions\Action::make('setPrimary')
                    ->label('Set Primary')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (ProfilePhoto $record): void {
                        $record->profile->profilePhotos()->update(['is_primary' => false]);
                        $record->update(['is_primary' => true, 'is_visible' => true]);
                    })
                    ->visible(fn (ProfilePhoto $record): bool => !$record->is_primary && $record->is_visible)
                    ->successNotificationTitle('Set as primary photo'),

                // Toggle visibility
                \Filament\Actions\Action::make('toggleVisibility')
                    ->label(fn (ProfilePhoto $record): string => $record->is_visible ? 'Archive' : 'Restore')
                    ->icon(fn (ProfilePhoto $record): string => $record->is_visible ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (ProfilePhoto $record): string => $record->is_visible ? 'gray' : 'success')
                    ->requiresConfirmation()
                    ->action(function (ProfilePhoto $record): void {
                        if ($record->is_visible) {
                            $record->update(['is_visible' => false, 'is_primary' => false]);
                        } else {
                            $currentCount = $record->profile->profilePhotos()->visible()->ofType($record->photo_type)->count();
                            $max = ProfilePhoto::maxForType($record->photo_type);
                            if ($currentCount >= $max) {
                                \Filament\Notifications\Notification::make()
                                    ->title("Cannot restore: maximum {$max} {$record->photo_type} photo(s) reached")
                                    ->danger()
                                    ->send();
                                return;
                            }
                            $record->update(['is_visible' => true]);
                        }
                    }),

                // Permanent delete
                \Filament\Actions\Action::make('deletePermanently')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Photo Permanently')
                    ->modalDescription('This will permanently delete the photo file from the server. This cannot be undone.')
                    ->action(function (ProfilePhoto $record): void {
                        if ($record->photo_url && Storage::disk('public')->exists($record->photo_url)) {
                            Storage::disk('public')->delete($record->photo_url);
                        }
                        if ($record->thumbnail_url && $record->thumbnail_url !== $record->photo_url && Storage::disk('public')->exists($record->thumbnail_url)) {
                            Storage::disk('public')->delete($record->thumbnail_url);
                        }
                        $record->delete();
                    })
                    ->successNotificationTitle('Photo deleted permanently'),
            ])
            ->emptyStateHeading('No Photos')
            ->emptyStateDescription('This user has not uploaded any photos yet. Use "Upload Photo" to add one.')
            ->emptyStateIcon('heroicon-o-photo');
    }
}
