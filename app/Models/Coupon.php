<?php

namespace App\Models;

use App\Models\Concerns\BranchScopable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use BranchScopable;

    protected $fillable = [
        'branch_id',
        'code',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_cap',
        'min_purchase_amount',
        'applicable_plan_ids',
        'usage_limit_total',
        'usage_limit_per_user',
        'times_used',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'integer',
            'max_discount_cap' => 'integer',
            'min_purchase_amount' => 'integer',
            'applicable_plan_ids' => 'array',
            'usage_limit_total' => 'integer',
            'usage_limit_per_user' => 'integer',
            'times_used' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Validate coupon for a specific plan and user.
     * Returns ['valid' => true, 'discount' => amount_in_paise] or ['valid' => false, 'message' => '...']
     */
    public function validateFor(int $planId, int $priceInPaise, int $userId): array
    {
        // Check active
        if (!$this->is_active) {
            return ['valid' => false, 'message' => 'This coupon is no longer active.'];
        }

        // Check date range
        if ($this->valid_from && $this->valid_from->isFuture()) {
            return ['valid' => false, 'message' => 'This coupon is not yet valid.'];
        }
        if ($this->valid_until && $this->valid_until->endOfDay()->isPast()) {
            return ['valid' => false, 'message' => 'This coupon has expired.'];
        }

        // Check total usage limit
        if ($this->usage_limit_total && $this->times_used >= $this->usage_limit_total) {
            return ['valid' => false, 'message' => 'This coupon has reached its usage limit.'];
        }

        // Check per-user limit
        $userUsageCount = $this->usages()->where('user_id', $userId)->count();
        if ($userUsageCount >= $this->usage_limit_per_user) {
            return ['valid' => false, 'message' => 'You have already used this coupon.'];
        }

        // Check applicable plans
        if (!empty($this->applicable_plan_ids) && !in_array($planId, $this->applicable_plan_ids)) {
            return ['valid' => false, 'message' => 'This coupon is not valid for the selected plan.'];
        }

        // Check minimum purchase
        if ($this->min_purchase_amount && $priceInPaise < $this->min_purchase_amount) {
            $minRupees = number_format($this->min_purchase_amount / 100);
            return ['valid' => false, 'message' => "Minimum purchase of ₹{$minRupees} required for this coupon."];
        }

        // Calculate discount
        $discount = $this->calculateDiscount($priceInPaise);

        return ['valid' => true, 'discount' => $discount];
    }

    /**
     * Calculate discount amount in paise.
     */
    public function calculateDiscount(int $priceInPaise): int
    {
        if ($this->discount_type === 'percentage') {
            $discount = (int) round($priceInPaise * $this->discount_value / 100);
            // Apply cap if set
            if ($this->max_discount_cap && $discount > $this->max_discount_cap) {
                $discount = $this->max_discount_cap;
            }
        } else {
            // Fixed amount (stored in paise)
            $discount = $this->discount_value;
        }

        // Never discount more than the price
        return min($discount, $priceInPaise);
    }

    /**
     * Record a usage of this coupon.
     */
    public function recordUsage(int $userId, int $subscriptionId, int $discountAmount): void
    {
        $this->usages()->create([
            'user_id' => $userId,
            'subscription_id' => $subscriptionId,
            'discount_amount' => $discountAmount,
        ]);

        $this->increment('times_used');
    }

    /**
     * Display formatted discount value.
     */
    public function getFormattedDiscountAttribute(): string
    {
        if ($this->discount_type === 'percentage') {
            return $this->discount_value . '%';
        }

        return '₹' . number_format($this->discount_value / 100);
    }
}
