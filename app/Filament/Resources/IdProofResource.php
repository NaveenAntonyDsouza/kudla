<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IdProofResource\Pages;
use App\Models\IdProof;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IdProofResource extends Resource
{
    protected static ?string $model = IdProof::class;
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationLabel = 'ID Verification';
    protected static ?string $modelLabel = 'ID Proof';
    protected static ?string $pluralModelLabel = 'ID Proofs';
    protected static ?int $navigationSort = 2;
    protected static \Illuminate\Contracts\Support\Htmlable|string|null $navigationBadgeTooltip = 'Pending verifications';

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
                Tables\Columns\TextColumn::make('profile.matri_id')
                    ->label('Matri ID')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->url(fn(IdProof $record) => UserResource::getUrl('view', ['record' => $record->profile_id])),

                Tables\Columns\TextColumn::make('profile.full_name')
                    ->label('Name')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('document_type')
                    ->label('Document')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('document_number')
                    ->label('Number')
                    ->formatStateUsing(fn(?string $state) => $state ? '****' . substr($state, -4) : '-')
                    ->toggleable(),

                Tables\Columns\ImageColumn::make('document_url')
                    ->label('Front')
                    ->disk('public')
                    ->height(50)
                    ->toggleable(),

                Tables\Columns\ImageColumn::make('document_back_url')
                    ->label('Back')
                    ->disk('public')
                    ->height(50)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('verification_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('rejection_reason')
                    ->label('Reason')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('verified_at')
                    ->label('Verified At')
                    ->dateTime('d M Y H:i')
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
                    ])
                    ->default('pending'),

                Tables\Filters\SelectFilter::make('document_type')
                    ->options([
                        'aadhaar' => 'Aadhaar',
                        'passport' => 'Passport',
                        'voter_id' => 'Voter ID',
                        'driving_license' => 'Driving License',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(IdProof $record) => $record->verification_status === 'pending')
                    ->action(function (IdProof $record) {
                        $record->update([
                            'verification_status' => 'approved',
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                            'rejection_reason' => null,
                        ]);
                        $record->profile->update(['id_proof_verified' => true]);
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(IdProof $record) => $record->verification_status === 'pending')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('rejection_reason')
                            ->label('Reason for Rejection')
                            ->required()
                            ->maxLength(500)
                            ->rows(3),
                    ])
                    ->action(function (IdProof $record, array $data) {
                        $record->update([
                            'verification_status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);
                        $record->profile->update(['id_proof_verified' => false]);
                    }),

                Tables\Actions\Action::make('viewDocument')
                    ->label('View Full')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn(IdProof $record) => $record->document_url ? asset('storage/' . $record->document_url) : null, shouldOpenInNewTab: true),
            ]);
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIdProofs::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['profile']);
    }
}
