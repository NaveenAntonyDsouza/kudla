<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

class MembershipPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'plan_name' => 'Free',
                'slug' => 'free',
                'duration_months' => 0,
                'price_inr' => 0,
                'strike_price_inr' => null,
                'daily_interest_limit' => 5,
                'can_view_contact' => false,
                'is_highlighted' => false,
                'sort_order' => 1,
                'features' => json_encode(['Create profile', 'Search profiles', '5 interests per day', 'Basic match recommendations']),
            ],
            [
                'plan_name' => 'Gold',
                'slug' => 'gold',
                'duration_months' => 3,
                'price_inr' => 2999,
                'strike_price_inr' => 3999,
                'daily_interest_limit' => 20,
                'can_view_contact' => true,
                'is_highlighted' => false,
                'sort_order' => 2,
                'features' => json_encode(['Everything in Free', 'View contact details', '20 interests per day', 'Priority customer support', 'See who viewed your profile']),
            ],
            [
                'plan_name' => 'Diamond',
                'slug' => 'diamond',
                'duration_months' => 6,
                'price_inr' => 4999,
                'strike_price_inr' => 6999,
                'daily_interest_limit' => 50,
                'can_view_contact' => true,
                'is_highlighted' => true,
                'sort_order' => 3,
                'features' => json_encode(['Everything in Gold', '50 interests per day', 'Highlighted profile in search', 'Custom interest messages', 'Advanced match recommendations']),
            ],
            [
                'plan_name' => 'Diamond Plus',
                'slug' => 'diamond-plus',
                'duration_months' => 12,
                'price_inr' => 7999,
                'strike_price_inr' => 11999,
                'daily_interest_limit' => 50,
                'can_view_contact' => true,
                'is_highlighted' => true,
                'sort_order' => 4,
                'features' => json_encode(['Everything in Diamond', '12 months validity', 'Top placement in search', 'Dedicated relationship manager', 'Profile boost every week']),
            ],
        ];

        foreach ($plans as $plan) {
            MembershipPlan::create($plan);
        }
    }
}
