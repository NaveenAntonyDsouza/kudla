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
 *   seo_title, browse_by  — Google title and "Browse brides and grooms by …" wording
 *                           for the category page (App\Support\DiscoverSeo)
 *   hub_label, hub_group  — card title and section on the Discover hub (Oct 2026):
 *                           community | language | place | work | life
 *   hub => false          — a category that repeats another: its pages stay, but no
 *                           button on the hub, the dashboard or "other directories"
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
        'hub_label' => 'Living abroad (NRI)',
        'hub_group' => 'place',
        'seo_title' => 'NRI Matrimony – Brides & Grooms Abroad',
        'browse_by' => 'the country they live in',
        'show_search' => true,
        'subcategories_source' => 'nriCountries',
    ],

    'catholic-matrimony' => [
        'label' => 'Catholic Matrimony',
        'hub_label' => 'Catholic',
        'hub_group' => 'community',
        'seo_title' => 'Catholic Matrimony – Catholic Brides & Grooms',
        'browse_by' => 'Catholic rite',
        'show_search' => false,
        'subcategories_source' => 'catholicDenominations',
    ],

    'karnataka-matrimony' => [
        'label' => 'Karnataka Matrimony',
        'hub_label' => 'Karnataka',
        'hub_group' => 'place',
        'seo_title' => 'Karnataka Matrimony – Brides & Grooms by District',
        'browse_by' => 'native district',
        'show_search' => true,
        'subcategories_source' => 'karnatakaDistricts',
    ],

    'christian-matrimony' => [
        'label' => 'Christian Matrimony',
        'hub_label' => 'Christian',
        'hub_group' => 'community',
        'seo_title' => 'Christian Matrimony – Christian Brides & Grooms',
        'browse_by' => 'church',
        'show_search' => false,
        'subcategories_source' => 'nonCatholicDenominations',
    ],

    // job titles grouped the way families search (config/profession_groups.php, Oct 2026)
    'profession-matrimony' => [
        'label' => 'Profession Matrimony',
        'hub_label' => 'Profession',
        'hub_group' => 'work',
        'seo_title' => 'Matrimony by Profession – Doctors, Engineers',
        'browse_by' => 'profession',
        'show_search' => true,
        'subcategories_source' => 'professionGroups',
    ],

    'occupation-matrimony' => [
        'label' => 'Occupation Matrimony',
        'hub_label' => 'Job title',
        'hub_group' => 'work',
        'seo_title' => 'Matrimony by Job Title – Brides & Grooms',
        'browse_by' => 'profession',
        'show_search' => true,
        'subcategories_source' => 'occupations',
    ],

    'diocese-matrimony' => [
        'label' => 'Diocese Matrimony',
        'hub_label' => 'Diocese',
        'hub_group' => 'community',
        'seo_title' => 'Diocese Matrimony – Catholic Brides & Grooms',
        'browse_by' => 'diocese',
        'show_search' => true,
        'subcategories_source' => 'dioceses',
    ],

    'kannadiga-matrimony' => [
        'label' => 'Kannadiga Matrimony',
        'hub_label' => 'Kannadiga',
        'hub_group' => 'language',
        'hub' => false, // repeats Mother tongue → Kannada: its pages stay, no hub button
        'show_search' => false,
        'direct_filter' => ['mother_tongue' => 'Kannada'],
    ],

    'second-marriage' => [
        'label' => 'Second Marriage Matrimony',
        'hub_label' => 'Second marriage',
        'hub_group' => 'life',
        'seo_title' => 'Second Marriage Matrimony – Divorced & Widowed',
        'browse_by' => 'marital status',
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
        'hub_label' => 'Mother tongue',
        'hub_group' => 'language',
        'seo_title' => 'Mother Tongue Matrimony – Brides & Grooms',
        'browse_by' => 'mother tongue',
        'show_search' => false,
        'subcategories_source' => 'languages',
    ],

    'community-matrimony' => [
        'label' => 'Community Matrimony',
        'hub_label' => 'Community',
        'hub_group' => 'community',
        'seo_title' => 'Community Matrimony – Brides & Grooms by Caste',
        'browse_by' => 'community',
        'show_search' => true,
        // castes plus Christian denominations and Muslim / Jain sects, less any religion
        // hidden in Homepage Content → Browse profiles by (Oct 2026)
        'subcategories_source' => 'communities',
    ],

    'hindu-matrimony' => [
        'label' => 'Hindu Matrimony',
        'hub_label' => 'Hindu',
        'hub_group' => 'community',
        'hub' => false, // repeats Community's caste pages: its pages stay, no hub button
        'seo_title' => 'Hindu Matrimony – Hindu Brides & Grooms',
        'browse_by' => 'community',
        'show_search' => true,
        'subcategories_source' => 'hinduCastes',
    ],

    'muslim-matrimony' => [
        'label' => 'Muslim Matrimony',
        'hub_label' => 'Muslim',
        'hub_group' => 'community',
        'seo_title' => 'Muslim Matrimony – Muslim Brides & Grooms',
        'browse_by' => 'sect',
        'show_search' => false,
        'subcategories_source' => 'muslimSects',
    ],

    'jain-matrimony' => [
        'label' => 'Jain Matrimony',
        'hub_label' => 'Jain',
        'hub_group' => 'community',
        'seo_title' => 'Jain Matrimony – Jain Brides & Grooms',
        'browse_by' => 'sect',
        'show_search' => false,
        'subcategories_source' => 'jainSects',
    ],

];
