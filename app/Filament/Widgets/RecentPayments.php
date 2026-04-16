<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentPayments extends TableWidget
{
    protected static ?string $heading = 'Recent Payments';
    protected static ?int $sort = 9;
    protected static bool $isLazy = true;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Subscription::query()
                    ->where('payment_status', 'paid')
                    ->with(['user.profile'])
                    ->orderByDesc('created_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.profile.matri_id')
                    ->label('Matri ID')
                    ->color('primary')
                    ->weight('bold')
                    ->default('-'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->limit(20),

                Tables\Columns\TextColumn::make('plan_name')
                    ->label('Plan')
                    ->badge()
                    ->color(fn(string $state): string => match(strtolower($state)) {
                        'diamond plus', 'diamond+' => 'warning',
                        'diamond' => 'info',
                        'gold' => 'success',
                        'silver' => 'gray',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->prefix('₹')
                    ->formatStateUsing(fn($state) => number_format($state / 100)),

                Tables\Columns\TextColumn::make('razorpay_payment_id')
                    ->label('Payment ID')
                    ->limit(15)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->since()
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
