<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipPlan extends Model
{
    protected $fillable = [
        'plan_name',
        'slug',
        'duration_months',
        'price_inr',
        'strike_price_inr',
        'features',
        'daily_interest_limit',
        'can_view_contact',
        'is_highlighted',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'duration_months' => 'integer',
            'price_inr' => 'integer',
            'strike_price_inr' => 'integer',
            'features' => 'array',
            'daily_interest_limit' => 'integer',
            'can_view_contact' => 'boolean',
            'is_highlighted' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function userMemberships(): HasMany
    {
        return $this->hasMany(UserMembership::class, 'plan_id');
    }
}
