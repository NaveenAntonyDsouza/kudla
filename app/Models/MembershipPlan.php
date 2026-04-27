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
        'view_contacts_limit',
        'daily_contact_views',
        'personalized_messages',
        'allows_free_member_chat',
        'exposes_contact_to_free',
        'featured_profile',
        'priority_support',
        'is_highlighted',
        'is_popular',
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
            'view_contacts_limit' => 'integer',
            'daily_contact_views' => 'integer',
            'personalized_messages' => 'boolean',
            'allows_free_member_chat' => 'boolean',
            'exposes_contact_to_free' => 'boolean',
            'featured_profile' => 'boolean',
            'priority_support' => 'boolean',
            'is_highlighted' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function userMemberships(): HasMany
    {
        return $this->hasMany(UserMembership::class, 'plan_id');
    }
}
