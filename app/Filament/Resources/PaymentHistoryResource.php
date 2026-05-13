<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentHistoryResource\Pages;
use App\Models\Subscription;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentHistoryResource extends Resource
{
    protected static ?string $model = Subscription::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Payment History';
    protected static ?string $modelLabel = 'Payment';
    protected static ?string $pluralModelLabel = 'Payment History';
    protected static \UnitEnum|string|null $navigationGroup = 'Membership & Payments';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('view_payment_history');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('view_payment_history');
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
                Tables\Columns\TextColumn::make('razorpay_payment_id')
                    ->label('Transaction ID')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—')
                    ->limit(20)
                    ->tooltip(fn (Subscription $record) => $record->razorpay_payment_id),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Subscription $record) => $record->user?->profile?->matri_id)
                    ->limit(25),

                Tables\Columns\TextColumn::make('plan_name')
                    ->label('Plan')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'diamond plus' => 'success',
                        'diamond' => 'info',
                        'gold' => 'warning',
                        'silver' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state) => '₹' . number_format($state / 100, 2))
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Start')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn ($state) => $state && $state < now() ? 'danger' : null)
                    ->placeholder('—'),

                \App\Filament\Tables\BranchTableComponents::column(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Payment Date')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                \App\Filament\Tables\BranchTableComponents::filter(),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Status')
                    ->options([
                        'paid' => 'Paid',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                    ]),

                Tables\Filters\SelectFilter::make('plan_name')
                    ->label('Plan')
                    ->options(fn () => Subscription::whereNotNull('plan_name')
                        ->distinct()
                        ->pluck('plan_name', 'plan_name')
                        ->toArray()
                    ),
            ])
            ->actions([
                \Filament\Actions\Action::make('viewDetails')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading('Payment Details')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (Subscription $record) => view('filament.pages.payment-details', ['payment' => $record])),
            ])
            ->searchPlaceholder('Search by transaction ID or user name...');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentHistory::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Branch scoping: Branch Manager / Branch Staff see only payments in their branch.
        return parent::getEloquentQuery()
            ->with(['user.profile'])
            ->forUserBranch();
    }
}
