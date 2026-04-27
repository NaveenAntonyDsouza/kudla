<?php

namespace App\Models;

use App\Models\Concerns\BranchScopable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use BranchScopable;

    protected $fillable = [
        'user_id', 'branch_id', 'plan_id', 'plan_name',
        'coupon_id', 'coupon_code', 'discount_amount', 'original_amount',
        'amount',
        'gateway', 'gateway_metadata',
        'razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature',
        'payment_status', 'starts_at', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'expires_at' => 'date',
            'is_active' => 'boolean',
            'amount' => 'integer',
            'gateway_metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * When a subscription becomes 'paid', mark the affiliate click that
     * brought this user as converted (if there is one).
     * Fires on both create (new paid sub) and update (status changed to paid).
     */
    protected static function booted(): void
    {
        static::saved(function (Subscription $subscription) {
            if (!$subscription->wasRecentlyCreated && !$subscription->wasChanged('payment_status')) {
                return; // no relevant change
            }
            if ($subscription->payment_status !== 'paid') {
                return;
            }
            try {
                app(\App\Services\AffiliateTracker::class)->markConversion($subscription);
            } catch (\Throwable $e) {
                // Conversion tracking is best-effort — don't break payment flow
                report($e);
            }
        });
    }
}
