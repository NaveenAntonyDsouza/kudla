<?php

/**
 * Discover Profiles — Category browsing configuration.
 *
 * Each category defines:
 *   label                 — display name
 *   show_search           — show client-side search box on subcategory page
 *   subcategories         — literal array of [{label, slug, filter}], OR
 *   subcategories_source  — string method name on App\Services\DiscoverConfigService
 *                           that returns the same shape (used for dynamic lists)
 *   direct_filter         — if set, category has no subcategories (shows results directly)
 *
 * Filter keys map to DB columns/relations:
 *   religion, denomination, caste, diocese, muslim_sect, muslim_community, jain_sect
 *   marital_status, mother_tongue, occupation
 *   native_state, native_district, residing_country
 *
 * NOTE: This file is config-cached-friendly — no closures (those moved to
 * App\Services\DiscoverConfigService so php artisan config:cache works).
 */

return [

    'nri-matrimony' => [
        'label' => 'NRI Matrimony',
        'show_search' => true,
        'subcategories_source' => 'nriCountries',
    ],

    'catholic-matrimony' => [
        'label' => 'Catholic Matrimony',
        'show_search' => false,
        'subcategories_source' => 'catholicDenominations',
    ],

    'karnataka-matrimony' => [
        'label' => 'Karnataka Matrimony',
        'show_search' => true,
        'subcategories_source' => 'karnatakaDistricts',
    ],

    'christian-matrimony' => [
        'label' => 'Christian Matrimony',
        'show_search' => false,
        'subcategories_source' => 'nonCatholicDenominations',
    ],

    'occupation-matrimony' => [
        'label' => 'Occupation Matrimony',
        'show_search' => true,
        'subcategories_source' => 'occupations',
    ],

    'diocese-matrimony' => [
        'label' => 'Diocese Matrimony',
        'show_search' => true,
        'subcategories_source' => 'dioceses',
    ],

    'kannadiga-matrimony' => [
        'label' => 'Kannadiga Matrimony',
        'show_search' => false,
        'direct_filter' => ['mother_tongue' => 'Kannada'],
    ],

    'second-marriage' => [
        'label' => 'Second Marriage Matrimony',
        'show_search' => false,
        'subcategories' => [
            ['label' => 'Annulled Profiles', 'slug' => 'annulled', 'filter' => ['marital_status' => 'Annulled']],
            ['label' => 'Awaiting Divorce Profiles', 'slug' => 'awaiting-divorce', 'filter' => ['marital_status' => 'Awaiting Divorce']],
            ['label' => 'Divorced Profiles', 'slug' => 'divorced', 'filter' => ['marital_status' => 'Divorced']],
            ['label' => 'Widow / Widower Profiles', 'slug' => 'widow', 'filter' => ['marital_status' => 'Widow/Widower']],
        ],
    ],

    'mother-tongue-matrimony' => [
        'label' => 'Mother Tongue Matrimony',
        'show_search' => false,
        'subcategories_source' => 'languages',
    ],

    'community-matrimony' => [
        'label' => 'Community Matrimony',
        'show_search' => true,
        'subcategories_source' => 'allCastes',
    ],

    'hindu-matrimony' => [
        'label' => 'Hindu Matrimony',
        'show_search' => true,
        'subcategories_source' => 'hinduCastes',
    ],

    'muslim-matrimony' => [
        'label' => 'Muslim Matrimony',
        'show_search' => false,
        'subcategories_source' => 'muslimSects',
    ],

    'jain-matrimony' => [
        'label' => 'Jain Matrimony',
        'show_search' => false,
        'subcategories_source' => 'jainSects',
    ],

];
