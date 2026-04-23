<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentApprovalResource\Pages;
use App\Models\Notification;
use App\Models\ReligiousInfo;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentApprovalResource extends Resource
{
    protected static ?string $model = ReligiousInfo::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Horoscope / Baptism';
    protected static ?string $modelLabel = 'Document';
    protected static ?string $pluralModelLabel = 'Documents';
    protected static \UnitEnum|string|null $navigationGroup = 'Verification';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('horoscope_approval');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('horoscope_approval');
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

    public static function getNavigationBadge(): ?string
    {
        $count = ReligiousInfo::whereNotNull('jathakam_upload_url')
            ->where('jathakam_approval_status', 'pending')
            ->count();
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
                Tables\Columns\ImageColumn::make('jathakam_upload_url')
                    ->label('Document')
                    ->disk('public')
                    ->size(80)
                    ->square(),

                Tables\Columns\TextColumn::make('profile.matri_id')
                    ->label('Matri ID')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->url(fn (ReligiousInfo $record) => UserResource::getUrl('view', ['record' => $record->profile_id])),

                Tables\Columns\TextColumn::make('profile.full_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('religion')
                    ->label('Religion')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('document_label')
                    ->label('Document Type')
                    ->getStateUsing(fn (ReligiousInfo $record): string => match ($record->religion) {
                        'Christian' => 'Baptism Certificate',
                        'Hindu', 'Jain' => 'Horoscope',
                        default => 'Document',
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('jathakam_approval_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('jathakam_rejection_reason')
                    ->label('Reason')
                    ->placeholder('-')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Uploaded')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('jathakam_approval_status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                Tables\Filters\SelectFilter::make('religion')
                    ->options(fn () => ReligiousInfo::whereNotNull('jathakam_upload_url')
                        ->whereNotNull('religion')
                        ->distinct()
                        ->pluck('religion', 'religion')
                        ->toArray()
                    ),
            ])
            ->actions([
                // View full document
                \Filament\Actions\Action::make('viewDocument')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (ReligiousInfo $record) => $record->jathakam_upload_url ? asset('storage/' . $record->jathakam_upload_url) : null, shouldOpenInNewTab: true),

                // Approve
                \Filament\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (ReligiousInfo $record): void {
                        $record->update([
                            'jathakam_approval_status' => 'approved',
                            'jathakam_approved_by' => auth()->id(),
                            'jathakam_approved_at' => now(),
                            'jathakam_rejection_reason' => null,
                        ]);

                        $docType = $record->religion === 'Christian' ? 'Baptism Certificate' : 'Horoscope';
                        Notification::create([
                            'profile_id' => $record->profile_id,
                            'type' => 'document_approved',
                            'title' => $docType . ' Approved',
                            'message' => 'Your ' . $docType . ' has been verified and approved.',
                            'is_read' => false,
                        ]);
                    })
                    ->visible(fn (ReligiousInfo $record) => $record->jathakam_approval_status !== 'approved')
                    ->successNotificationTitle('Document approved'),

                // Reject
                \Filament\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Select::make('rejection_reason')
                            ->label('Reason')
                            ->required()
                            ->options([
                                'blurry' => 'Document is blurry / unreadable',
                                'incomplete' => 'Document is incomplete / cropped',
                                'wrong_document' => 'Wrong document type uploaded',
                                'expired' => 'Document is expired',
                                'other' => 'Other',
                            ]),
                        Forms\Components\Textarea::make('custom_note')
                            ->label('Additional Note (optional)')
                            ->rows(2),
                    ])
                    ->action(function (ReligiousInfo $record, array $data): void {
                        $reasons = [
                            'blurry' => 'Document is blurry / unreadable',
                            'incomplete' => 'Document is incomplete / cropped',
                            'wrong_document' => 'Wrong document type uploaded',
                            'expired' => 'Document is expired',
                            'other' => 'Other',
                        ];
                        $reason = $reasons[$data['rejection_reason']] ?? $data['rejection_reason'];
                        if (!empty($data['custom_note'])) {
                            $reason .= ' — ' . $data['custom_note'];
                        }

                        $record->update([
                            'jathakam_approval_status' => 'rejected',
                            'jathakam_rejection_reason' => $reason,
                            'jathakam_approved_by' => auth()->id(),
                            'jathakam_approved_at' => now(),
                        ]);

                        $docType = $record->religion === 'Christian' ? 'Baptism Certificate' : 'Horoscope';
                        Notification::create([
                            'profile_id' => $record->profile_id,
                            'type' => 'document_rejected',
                            'title' => $docType . ' Rejected',
                            'message' => 'Your ' . $docType . ' was rejected. Reason: ' . $reason . '. Please upload a valid document.',
                            'is_read' => false,
                        ]);
                    })
                    ->visible(fn (ReligiousInfo $record) => $record->jathakam_approval_status !== 'rejected')
                    ->successNotificationTitle('Document rejected'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkAction::make('bulkApprove')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records): void {
                        $records->each(function (ReligiousInfo $record) {
                            $record->update([
                                'jathakam_approval_status' => 'approved',
                                'jathakam_approved_by' => auth()->id(),
                                'jathakam_approved_at' => now(),
                            ]);
                        });
                    })
                    ->deselectRecordsAfterCompletion()
                    ->successNotificationTitle('Selected documents approved'),
            ])
            ->searchPlaceholder('Search by matri ID or name...');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentApprovals::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('jathakam_upload_url')
            ->with(['profile']);
    }
}
