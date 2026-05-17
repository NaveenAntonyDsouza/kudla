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
                // Transaction ID is gateway-specific. Razorpay stores it
                // in razorpay_payment_id; PhonePe stores it in
                // gateway_metadata->phonepe_transaction_id; future gateways
                // can extend the match below. `searchable(query: ...)` keeps
                // the search box working across both columns.
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->getStateUsing(fn (Subscription $record): ?string => match ($record->gateway) {
                        'razorpay' => $record->razorpay_payment_id,
                        'phonepe' => $record->gateway_metadata['phonepe_transaction_id'] ?? null,
                        default => $record->razorpay_payment_id, // legacy rows pre-multi-gateway
                    })
                    ->searchable(query: fn ($query, string $search) => $query
                        ->where('razorpay_payment_id', 'like', "%{$search}%")
                        ->orWhere('gateway_metadata->phonepe_transaction_id', 'like', "%{$search}%")
                    )
                    ->copyable()
                    ->placeholder('—')
                    ->limit(20)
                    ->tooltip(fn (Subscription $record): ?string => match ($record->gateway) {
                        'razorpay' => $record->razorpay_payment_id,
                        'phonepe' => $record->gateway_metadata['phonepe_transaction_id'] ?? null,
                        default => $record->razorpay_payment_id,
                    }),

                // Which gateway processed this payment. Badge colors mirror
                // the gateway picker cards on /membership-plans so admins
                // build the same mental association across surfaces.
                Tables\Columns\TextColumn::make('gateway')
                    ->label('Gateway')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'razorpay' => 'info',
                        'phonepe' => 'success',
                        'stripe' => 'primary',
                        'paypal' => 'warning',
                        'paytm' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'razorpay' => 'Razorpay',
                        'phonepe' => 'PhonePe',
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'paytm' => 'Paytm',
                        null, '' => '—',
                        default => ucfirst((string) $state),
                    })
                    ->sortable(),

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

                Tables\Filters\SelectFilter::make('gateway')
                    ->label('Gateway')
                    ->options([
                        'razorpay' => 'Razorpay',
                        'phonepe' => 'PhonePe',
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'paytm' => 'Paytm',
                    ]),
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

                // "Refresh from PhonePe" — re-queries PhonePe's order-status
                // API for a still-pending PhonePe row and reconciles our
                // side. Use when the webhook never arrived AND the buyer
                // didn't come back through the synchronous return URL
                // (e.g. closed the browser between paying and redirect).
                //
                // What it does:
                //   • Hits PhonePe /checkout/v2/order/{id}/status
                //   • If state=COMPLETED → activate (idempotent — same
                //     SubscriptionActivator path the webhook uses)
                //   • If state=FAILED → markFailed
                //   • Otherwise (PENDING, declined, etc.) → just refresh
                //     gateway_metadata.phonepe_state so admins see the
                //     latest fact and can decide what to do
                //
                // Only razorpay+phonepe gateways have real implementations
                // today, so action is gated on gateway === 'phonepe' AND
                // payment_status === 'pending'. Razorpay equivalent can
                // follow the same shape when that becomes a real support
                // pattern.
                \Filament\Actions\Action::make('refreshFromPhonePe')
                    ->label('Refresh from PhonePe')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (Subscription $record) =>
                        $record->gateway === 'phonepe'
                        && $record->payment_status === 'pending'
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Re-check this order with PhonePe?')
                    ->modalDescription('Hits PhonePe\'s order-status API. If they confirm COMPLETED the subscription activates immediately. If they confirm FAILED the row moves to the Failed tab. Otherwise we just update the recorded State so you can see what PhonePe is reporting.')
                    ->modalSubmitActionLabel('Check now')
                    ->action(function (Subscription $record) {
                        $merchantOrderId = $record->gateway_metadata['phonepe_merchant_order_id'] ?? null;
                        if (! $merchantOrderId) {
                            \Filament\Notifications\Notification::make()
                                ->title('Cannot refresh')
                                ->body('No PhonePe merchant order id recorded for this row.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $phonepe = app(\App\Services\Payment\PaymentGatewayManager::class)->forSlug('phonepe');
                        if (! $phonepe instanceof \App\Services\Payment\PhonePeService) {
                            \Filament\Notifications\Notification::make()
                                ->title('Cannot refresh')
                                ->body('PhonePe service is not currently configured.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $status = $phonepe->fetchOrderStatus($merchantOrderId);
                        if (! $status) {
                            \Filament\Notifications\Notification::make()
                                ->title('PhonePe API call failed')
                                ->body('Could not reach PhonePe or the order id is unknown to them. Check the row in PhonePe\'s dashboard manually.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $state = (string) ($status['state'] ?? '');
                        $first = $status['paymentDetails'][0] ?? [];

                        // Always refresh the stored metadata so the Details
                        // modal reflects PhonePe's latest truth.
                        $existing = $record->gateway_metadata ?? [];
                        $record->update([
                            'gateway_metadata' => array_merge((array) $existing, [
                                'phonepe_state' => $state ?: 'UNKNOWN',
                                'phonepe_transaction_id' => (string) ($first['transactionId'] ?? ($existing['phonepe_transaction_id'] ?? '')),
                                'phonepe_payment_mode' => (string) ($first['paymentMode'] ?? ($existing['phonepe_payment_mode'] ?? '')),
                            ]),
                        ]);

                        $activator = app(\App\Services\Payment\SubscriptionActivator::class);

                        if ($state === 'COMPLETED') {
                            // Idempotent — same path the webhook + sync
                            // return URL take. Activates membership if
                            // not already activated.
                            $activated = $activator->activate($record->fresh());
                            \Filament\Notifications\Notification::make()
                                ->title($activated ? 'Activated' : 'Already paid')
                                ->body($activated
                                    ? "Subscription #{$record->id} is now active. UserMembership created."
                                    : "Subscription #{$record->id} was already in a paid state — no change.")
                                ->success()
                                ->send();
                            return;
                        }

                        if ($state === 'FAILED') {
                            $activator->markFailed($record->fresh());
                            \Filament\Notifications\Notification::make()
                                ->title('Marked failed')
                                ->body("PhonePe reports this order as FAILED. Subscription #{$record->id} moved to the Failed tab.")
                                ->warning()
                                ->send();
                            return;
                        }

                        // PENDING or any other intermediate state — just
                        // report what PhonePe said. Admin decides next step.
                        \Filament\Notifications\Notification::make()
                            ->title('Still '.($state ?: 'unknown'))
                            ->body("PhonePe hasn't settled this order yet (state: {$state}). The Details modal now reflects their latest reported state.")
                            ->info()
                            ->send();
                    }),

                // "Mark as Failed" — only visible on pending rows. Support
                // workflow: user calls saying "I tried to pay but it didn't
                // go through", admin checks the row, confirms with the user
                // it's a true abandon (not still in-flight), clicks Mark
                // Failed. SubscriptionActivator::markFailed is idempotent
                // and refuses to flip paid/refunded rows, so even a
                // misfire here can't corrupt an active membership.
                \Filament\Actions\Action::make('markFailed')
                    ->label('Mark as Failed')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Subscription $record) => $record->payment_status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Mark this payment as failed?')
                    ->modalDescription('The buyer\'s subscription stays inactive and the row moves to the "Failed" tab. Use this only when the buyer has confirmed they never completed the payment.')
                    ->modalSubmitActionLabel('Yes, mark failed')
                    ->action(function (Subscription $record) {
                        app(\App\Services\Payment\SubscriptionActivator::class)->markFailed($record);

                        \Filament\Notifications\Notification::make()
                            ->title('Payment marked as failed')
                            ->body("Subscription #{$record->id} is now in the Failed tab.")
                            ->success()
                            ->send();
                    }),
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
