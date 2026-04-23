<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateClick extends Model
{
    protected $fillable = [
        'branch_id',
        'ip_hash',
        'user_agent_hash',
        'referrer_url',
        'landing_page',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'visited_at',
        'registered_user_id',
        'registered_at',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'registered_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function registeredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_user_id');
    }

    /* ------------------------------------------------------------------
     |  Scopes (for dashboard / reports)
     | ------------------------------------------------------------------ */

    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeRegistered(Builder $query): Builder
    {
        return $query->whereNotNull('registered_user_id');
    }

    public function scopeConverted(Builder $query): Builder
    {
        return $query->whereNotNull('converted_at');
    }

    public function scopeBetween(Builder $query, $start, $end): Builder
    {
        return $query->whereBetween('visited_at', [$start, $end]);
    }
}
