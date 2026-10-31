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
 *   hub_label             — its short name on the member dashboard, and its hub row's title
 *   hub_row               — its row of chips on the Discover hub, in this order (Oct 2026);
 *                           none: no row (the religions: the communities row holds them,
 *                           as on the homepage). The dioceses row is for Catholic sites only.
 *   hub_title, hub_all    — the row's heading when not hub_label, and its "All …" link
 *   hub => false          — a category that repeats another: its pages stay, but no
 *                           button on the hub, the dashboard or "other directories"
 *
 * Filter keys map to DB columns/relations:
 *   religion, denomination, caste, diocese, muslim_sect, muslim_community, jain_sect
 *   marital_status, mother_tongue, occupation, working_state, working_district
 *   native_state, native_district, residing_country, abroad_country (lives or works there)
 *
 * NOTE: This file is config-cached-friendly — no closures (those moved to
 * App\Services\DiscoverConfigService so php artisan config:cache works).
 */

return [

    // members who live or work outside India (Oct 2026: on Kudla 59 live abroad, 89 work abroad)
    'nri-matrimony' => [
        'label' => 'NRI Matrimony',
        'hub_label' => 'NRI',
        'hub_row' => 5,
        'hub_all' => 'All countries',
        'seo_title' => 'NRI Matrimony – Brides & Grooms Abroad',
        'browse_by' => 'the country they live or work in',
        'show_search' => true,
        'subcategories_source' => 'nriCountries',
    ],

    'catholic-matrimony' => [
        'label' => 'Catholic Matrimony',
        'hub_label' => 'Catholic',
        'seo_title' => 'Catholic Matrimony – Catholic Brides & Grooms',
        'browse_by' => 'Catholic rite',
        'show_search' => false,
        'subcategories_source' => 'catholicDenominations',
    ],

    // where members are from: Karnataka's districts and Kasaragod (Tulu Nadu, in Kerala)
    'karnataka-matrimony' => [
        'label' => 'Matrimony by Home District',
        'hub_label' => 'Home district',
        'hub_row' => 3,
        'hub_all' => 'All districts',
        'seo_title' => 'Brides & Grooms by Home District',
        'browse_by' => 'home district',
        'show_search' => true,
        'subcategories_source' => 'karnatakaDistricts',
    ],

    // where members work in India (abroad: NRI)
    'working-in' => [
        'label' => 'Matrimony by Work Place',
        'hub_label' => 'Working in',
        'hub_row' => 4,
        'hub_all' => 'All work places',
        'seo_title' => 'Brides & Grooms by Where They Work',
        'browse_by' => 'where they work',
        'show_search' => true,
        'subcategories_source' => 'workPlaces',
    ],

    'christian-matrimony' => [
        'label' => 'Christian Matrimony',
        'hub_label' => 'Christian',
        'seo_title' => 'Christian Matrimony – Christian Brides & Grooms',
        'browse_by' => 'church',
        'show_search' => false,
        'subcategories_source' => 'nonCatholicDenominations',
    ],

    // job titles grouped the way families search (config/profession_groups.php, Oct 2026)
    'profession-matrimony' => [
        'label' => 'Profession Matrimony',
        'hub_label' => 'Profession',
        'hub_row' => 7, // the easier way in: 15 groups before 109 job titles
        'hub_all' => 'All professions',
        'seo_title' => 'Matrimony by Profession – Doctors, Engineers',
        'browse_by' => 'profession',
        'show_search' => true,
        'subcategories_source' => 'professionGroups',
    ],

    'occupation-matrimony' => [
        'label' => 'Occupation Matrimony',
        'hub_label' => 'Job title',
        'hub_row' => 8,
        'hub_all' => 'All job titles',
        'seo_title' => 'Matrimony by Job Title – Brides & Grooms',
        'browse_by' => 'profession',
        'show_search' => true,
        'subcategories_source' => 'occupations',
    ],

    'diocese-matrimony' => [
        'label' => 'Diocese Matrimony',
        'hub_label' => 'Diocese',
        'hub_row' => 2,
        'hub_title' => 'Dioceses',
        'hub_all' => 'All dioceses',
        'seo_title' => 'Diocese Matrimony – Catholic Brides & Grooms',
        'browse_by' => 'diocese',
        'show_search' => true,
        'subcategories_source' => 'dioceses',
    ],

    'kannadiga-matrimony' => [
        'label' => 'Kannadiga Matrimony',
        'hub_label' => 'Kannadiga',
        'hub' => false, // repeats Mother tongue → Kannada: its pages stay, no hub button
        'show_search' => false,
        'direct_filter' => ['mother_tongue' => 'Kannada'],
    ],

    'second-marriage' => [
        'label' => 'Second Marriage Matrimony',
        'hub_label' => 'Second marriage',
        'hub_row' => 9,
        'hub_all' => 'See all',
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
        'hub_row' => 6,
        'hub_all' => 'All languages',
        'seo_title' => 'Mother Tongue Matrimony – Brides & Grooms',
        'browse_by' => 'mother tongue',
        'show_search' => false,
        'subcategories_source' => 'languages',
    ],

    'community-matrimony' => [
        'label' => 'Community Matrimony',
        'hub_label' => 'Community',
        'hub_row' => 1,
        'hub_title' => 'Communities',
        'hub_all' => 'All communities',
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
        'hub' => false, // repeats Community's caste pages: its pages stay, no hub button
        'seo_title' => 'Hindu Matrimony – Hindu Brides & Grooms',
        'browse_by' => 'community',
        'show_search' => true,
        'subcategories_source' => 'hinduCastes',
    ],

    'muslim-matrimony' => [
        'label' => 'Muslim Matrimony',
        'hub_label' => 'Muslim',
        'seo_title' => 'Muslim Matrimony – Muslim Brides & Grooms',
        'browse_by' => 'sect',
        'show_search' => false,
        'subcategories_source' => 'muslimSects',
    ],

    'jain-matrimony' => [
        'label' => 'Jain Matrimony',
        'hub_label' => 'Jain',
        'seo_title' => 'Jain Matrimony – Jain Brides & Grooms',
        'browse_by' => 'sect',
        'show_search' => false,
        'subcategories_source' => 'jainSects',
    ],

];
