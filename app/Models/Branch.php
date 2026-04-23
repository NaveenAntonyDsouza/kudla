<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'location',
        'state',
        'address',
        'phone',
        'email',
        'manager_user_id',
        'is_active',
        'is_head_office',
        'commission_pct',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_head_office' => 'boolean',
            'commission_pct' => 'decimal:2',
        ];
    }

    /**
     * Use the branch code in URLs (for affiliate links in Phase 1.4.4).
     * e.g. /register?ref=MNG instead of /register?ref=42
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * Branch Manager — head of this branch.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    /**
     * All staff users assigned to this branch.
     */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class, 'branch_id')
            ->whereNotNull('staff_role_id');
    }

    /**
     * All members (non-staff users) registered through this branch.
     */
    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'branch_id')
            ->whereNull('staff_role_id');
    }

    /**
     * Profiles registered through this branch.
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class, 'branch_id');
    }

    /**
     * Leads belonging to this branch.
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'branch_id');
    }

    /**
     * Subscriptions attributed to this branch.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'branch_id');
    }

    /**
     * Coupons restricted to this branch (NULL coupons are global).
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class, 'branch_id');
    }

    /**
     * Staff targets specific to this branch.
     */
    public function staffTargets(): HasMany
    {
        return $this->hasMany(StaffTarget::class, 'branch_id');
    }

    /**
     * Commission payouts for this branch.
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(BranchPayout::class, 'branch_id');
    }

    /* ------------------------------------------------------------------
     |  Scopes
     | ------------------------------------------------------------------ */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeHeadOffice(Builder $query): Builder
    {
        return $query->where('is_head_office', true);
    }

    /* ------------------------------------------------------------------
     |  Helpers
     | ------------------------------------------------------------------ */

    /**
     * Get the head office branch (created by seeder; only one should exist).
     */
    public static function getHeadOffice(): ?self
    {
        return static::query()->where('is_head_office', true)->first();
    }

    /**
     * Display label combining name and code, e.g. "Mangalore Branch (MNG)"
     */
    public function getDisplayLabelAttribute(): string
    {
        return "{$this->name} ({$this->code})";
    }
}
