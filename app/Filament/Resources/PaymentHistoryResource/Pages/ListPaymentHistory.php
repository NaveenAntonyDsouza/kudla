<?php

namespace App\Filament\Resources\PaymentHistoryResource\Pages;

use App\Filament\Resources\PaymentHistoryResource;
use App\Models\Subscription;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tabs at the top of /admin/payment-history.
 *
 * The "Abandoned Pending" tab is the one support actually needs: rows
 * where payment_status='pending' and the order is older than 30 min
 * with no completion event. These are users who hit checkout, were
 * sent to the gateway, and either closed the browser, lost network,
 * or had the payment time out at the gateway side without our webhook
 * ever firing the activator.
 *
 * 30 minutes is the cutoff because:
 *   • Razorpay orders TTL out at ~15 min on their side.
 *   • PhonePe V2 orders TTL at 20 min (we pass expireAfter=1200).
 *   • A 30-min buffer lets a slow webhook still land cleanly without
 *     the row showing up here as abandoned.
 *
 * Badge counts are unscoped intentionally — matches the LeadResource
 * pattern. Branch-bound admins will see counts across all branches in
 * the tab badges but the table itself is still scoped via
 * PaymentHistoryResource::getEloquentQuery() (forUserBranch).
 */
class ListPaymentHistory extends ListRecords
{
    protected static string $resource = PaymentHistoryResource::class;

    /** Pending rows older than this count as "abandoned". */
    private const ABANDONED_AFTER_MINUTES = 30;

    public function getTabs(): array
    {
        $abandonedSince = now()->subMinutes(self::ABANDONED_AFTER_MINUTES);

        return [
            'all' => Tab::make('All')
                ->icon('heroicon-o-list-bullet'),

            'paid' => Tab::make('Paid')
                ->icon('heroicon-o-check-badge')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('payment_status', 'paid'))
                ->badge(fn () => Subscription::where('payment_status', 'paid')->count() ?: null)
                ->badgeColor('success'),

            'pending_recent' => Tab::make('Pending (recent)')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $q) => $q
                    ->where('payment_status', 'pending')
                    ->where('created_at', '>', $abandonedSince)
                )
                ->badge(fn () => Subscription::where('payment_status', 'pending')
                    ->where('created_at', '>', now()->subMinutes(self::ABANDONED_AFTER_MINUTES))
                    ->count() ?: null
                )
                ->badgeColor('warning'),

            'abandoned' => Tab::make('Abandoned Pending')
                ->icon('heroicon-o-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $q) => $q
                    ->where('payment_status', 'pending')
                    ->where('created_at', '<=', $abandonedSince)
                )
                ->badge(fn () => Subscription::where('payment_status', 'pending')
                    ->where('created_at', '<=', now()->subMinutes(self::ABANDONED_AFTER_MINUTES))
                    ->count() ?: null
                )
                ->badgeColor('danger'),

            'failed' => Tab::make('Failed')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('payment_status', 'failed'))
                ->badge(fn () => Subscription::where('payment_status', 'failed')->count() ?: null)
                ->badgeColor('gray'),
        ];
    }
}
