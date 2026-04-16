<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IdProofResource\Pages;
use App\Models\IdProof;
use App\Models\Notification;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IdProofResource extends Resource
{
    protected static ?string $model = IdProof::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'ID Verification';
    protected static ?string $modelLabel = 'ID Proof';
    protected static ?string $pluralModelLabel = 'ID Proofs';
    protected static \UnitEnum|string|null $navigationGroup = 'Verification';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = IdProof::where('verification_status', 'pending')->count();
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
                Tables\Columns\ImageColumn::make('document_url')
                    ->label('Front')
                    ->disk('public')
                    ->size(80)
                    ->square(),

                Tables\Columns\ImageColumn::make('document_back_url')
                    ->label('Back')
                    ->disk('public')
                    ->size(80)
                    ->square(),

                Tables\Columns\TextColumn::make('profile.matri_id')
                    ->label('Matri ID')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->url(fn (IdProof $record) => UserResource::getUrl('view', ['record' => $record->profile_id])),

                Tables\Columns\TextColumn::make('profile.full_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('document_type')
                    ->label('Document')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('document_number')
                    ->label('Number')
                    ->formatStateUsing(fn (?string $state) => $state ? '****' . substr($state, -4) : '-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('verification_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('rejection_reason')
                    ->label('Reason')
                    ->limit(30)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('verifier.name')
                    ->label('Verified By')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('verification_status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                Tables\Filters\SelectFilter::make('document_type')
                    ->options([
                        'aadhaar' => 'Aadhaar',
                        'passport' => 'Passport',
                        'voter_id' => 'Voter ID',
                        'driving_license' => 'Driving License',
                        'pan_card' => 'PAN Card',
                    ]),
            ])
            ->actions([
                // View full document
                \Filament\Actions\Action::make('viewFront')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (IdProof $record) => $record->document_url ? asset('storage/' . $record->document_url) : null, shouldOpenInNewTab: true),

                // Approve
                \Filament\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (IdProof $record): void {
                        $record->update([
                            'verification_status' => 'approved',
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                            'rejection_reason' => null,
                        ]);
                        $record->profile->update(['id_proof_verified' => true]);

                        Notification::create([
                            'profile_id' => $record->profile_id,
                            'type' => 'id_proof_approved',
                            'title' => 'ID Proof Verified',
                            'message' => 'Your ' . $record->document_type . ' has been verified successfully. Your profile now shows the verified badge.',
                            'is_read' => false,
                        ]);
                    })
                    ->visible(fn (IdProof $record) => $record->verification_status !== 'approved')
                    ->successNotificationTitle('ID proof approved'),

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
                                'mismatch' => 'Name does not match profile',
                                'expired' => 'Document is expired',
                                'wrong_type' => 'Wrong document type',
                                'fake' => 'Document appears to be fake / edited',
                                'other' => 'Other',
                            ]),
                        Forms\Components\Textarea::make('custom_note')
                            ->label('Additional Note (optional)')
                            ->rows(2),
                    ])
                    ->action(function (IdProof $record, array $data): void {
                        $reasons = [
                            'blurry' => 'Document is blurry / unreadable',
                            'incomplete' => 'Document is incomplete / cropped',
                            'mismatch' => 'Name does not match profile',
                            'expired' => 'Document is expired',
                            'wrong_type' => 'Wrong document type',
                            'fake' => 'Document appears to be fake / edited',
                            'other' => 'Other',
                        ];
                        $reason = $reasons[$data['rejection_reason']] ?? $data['rejection_reason'];
                        if (!empty($data['custom_note'])) {
                            $reason .= ' — ' . $data['custom_note'];
                        }

                        $record->update([
                            'verification_status' => 'rejected',
                            'rejection_reason' => $reason,
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);
                        $record->profile->update(['id_proof_verified' => false]);

                        Notification::create([
                            'profile_id' => $record->profile_id,
                            'type' => 'id_proof_rejected',
                            'title' => 'ID Proof Rejected',
                            'message' => 'Your ' . $record->document_type . ' was rejected. Reason: ' . $reason . '. Please upload a valid document.',
                            'is_read' => false,
                        ]);
                    })
                    ->visible(fn (IdProof $record) => $record->verification_status !== 'rejected')
                    ->successNotificationTitle('ID proof rejected'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkAction::make('bulkApprove')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records): void {
                        $records->each(function (IdProof $record) {
                            $record->update([
                                'verification_status' => 'approved',
                                'verified_by' => auth()->id(),
                                'verified_at' => now(),
                            ]);
                            $record->profile->update(['id_proof_verified' => true]);
                        });
                    })
                    ->deselectRecordsAfterCompletion()
                    ->successNotificationTitle('Selected ID proofs approved'),
            ])
            ->searchPlaceholder('Search by matri ID or name...')
            ->poll('30s');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIdProofs::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['profile', 'verifier']);
    }
}
