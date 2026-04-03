<?php

return [
    'basic' => [
        'id' => 'basic',
        'name' => 'Basic',
        'price' => 999,
        'original_price' => 1499,
        'duration_months' => 3,
        'color' => '#8B5CF6',
        'features' => [
            'View 50 Contacts' => true,
            '5 Interests/Day' => true,
            'Personalized Messages' => false,
            'Featured Profile' => false,
            'Priority Support' => false,
        ],
    ],
    'standard' => [
        'id' => 'standard',
        'name' => 'Standard',
        'price' => 1999,
        'original_price' => 2999,
        'duration_months' => 6,
        'color' => '#8B1D91',
        'popular' => true,
        'features' => [
            'View 200 Contacts' => true,
            '15 Interests/Day' => true,
            'Personalized Messages' => true,
            'Featured Profile' => false,
            'Priority Support' => true,
        ],
    ],
    'premium' => [
        'id' => 'premium',
        'name' => 'Premium',
        'price' => 3999,
        'original_price' => 5999,
        'duration_months' => 12,
        'color' => '#D97706',
        'features' => [
            'View 500 Contacts' => true,
            '50 Interests/Day' => true,
            'Personalized Messages' => true,
            'Featured Profile' => true,
            'Priority Support' => true,
        ],
    ],
];
