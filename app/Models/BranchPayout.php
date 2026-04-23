<?php

namespace App\Models;

use App\Models\Concerns\BranchScopable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BranchPayout extends Model
{
    use BranchScopable, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'branch_id',
        'period_start',
        'period_end',
        'gross_revenue_paise',
        'commission_pct',
        'payout_amount_paise',
        'status',
        'paid_on',
        'transaction_reference',
        'notes',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'gross_revenue_paise' => 'integer',
            'commission_pct' => 'decimal:2',
            'payout_amount_paise' => 'integer',
            'paid_on' => 'date',
        ];
    }

    /* ------------------------------------------------------------------
     |  Relationships
     | ------------------------------------------------------------------ */

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /* ------------------------------------------------------------------
     |  Scopes
     | ------------------------------------------------------------------ */

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeForMonth(Builder $query, Carbon $monthStart): Builder
    {
        return $query->whereDate('period_start', $monthStart->copy()->startOfMonth()->toDateString());
    }

    /* ------------------------------------------------------------------
     |  Accessors
     | ------------------------------------------------------------------ */

    public function getGrossRevenueRupeesAttribute(): float
    {
        return round($this->gross_revenue_paise / 100, 2);
    }

    public function getPayoutAmountRupeesAttribute(): float
    {
        return round($this->payout_amount_paise / 100, 2);
    }

    public function getPeriodLabelAttribute(): string
    {
        return $this->period_start ? $this->period_start->format('F Y') : '—';
    }

    /* ------------------------------------------------------------------
     |  Static helpers
     | ------------------------------------------------------------------ */

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PAID => 'Paid',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function statusColors(): array
    {
        return [
            self::STATUS_PENDING => 'warning',
            self::STATUS_PAID => 'success',
            self::STATUS_CANCELLED => 'gray',
        ];
    }
}
