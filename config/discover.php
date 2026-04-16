<?php

/**
 * Discover Profiles — Category browsing configuration.
 *
 * Each category defines:
 *   label         — display name
 *   show_search   — show client-side search box on subcategory page
 *   subcategories — callable or array returning [{label, slug, filter}]
 *   direct_filter — if set, category has no subcategories (shows results directly)
 *
 * Filter keys map to DB columns/relations:
 *   religion, denomination, caste, diocese, muslim_sect, muslim_community, jain_sect
 *   marital_status, mother_tongue, occupation
 *   native_state, native_district, residing_country
 */

return [

    'nri-matrimony' => [
        'label' => 'NRI Matrimony',
        'show_search' => true,
        'subcategories' => function () {
            $countries = [];
            foreach (config('reference_data.country_list', []) as $group => $list) {
                foreach ($list as $c) {
                    if ($c !== 'India') {
                        $countries[] = [
                            'label' => "$c Profiles",
                            'slug' => \Str::slug($c),
                            'filter' => ['residing_country' => $c],
                        ];
                    }
                }
            }
            return $countries;
        },
    ],

    'catholic-matrimony' => [
        'label' => 'Catholic Matrimony',
        'show_search' => false,
        'subcategories' => function () {
            return collect(config('reference_data.denomination_list.Catholic', []))
                ->map(fn($d) => [
                    'label' => "$d Profiles",
                    'slug' => \Str::slug($d),
                    'filter' => ['denomination' => $d],
                ])->values()->all();
        },
    ],

    'karnataka-matrimony' => [
        'label' => 'Karnataka Matrimony',
        'show_search' => true,
        'subcategories' => function () {
            return collect(config('locations.state_district_map.Karnataka', []))
                ->map(fn($d) => [
                    'label' => "$d Profiles",
                    'slug' => \Str::slug($d),
                    'filter' => ['native_state' => 'Karnataka', 'native_district' => $d],
                ])->values()->all();
        },
    ],

    'christian-matrimony' => [
        'label' => 'Christian Matrimony',
        'show_search' => false,
        'subcategories' => function () {
            return collect(config('reference_data.denomination_list.Non-Catholic', []))
                ->filter(fn($d) => $d !== 'Other')
                ->map(fn($d) => [
                    'label' => "$d Profiles",
                    'slug' => \Str::slug($d),
                    'filter' => ['denomination' => $d],
                ])->values()->all();
        },
    ],

    'occupation-matrimony' => [
        'label' => 'Occupation Matrimony',
        'show_search' => true,
        'subcategories' => function () {
            $items = [];
            foreach (config('reference_data.occupation_category_list', []) as $category => $occupations) {
                // Use the category label (strip number prefix)
                $label = preg_replace('/^\d+\.\s*/', '', $category);
                foreach ($occupations as $occ) {
                    $items[] = [
                        'label' => "$occ Profiles",
                        'slug' => \Str::slug($occ),
                        'filter' => ['occupation' => $occ],
                    ];
                }
            }
            return $items;
        },
    ],

    'diocese-matrimony' => [
        'label' => 'Diocese Matrimony',
        'show_search' => true,
        'subcategories' => function () {
            return collect(config('reference_data.diocese_list', []))
                ->map(fn($d) => [
                    'label' => "$d Profiles",
                    'slug' => \Str::slug($d),
                    'filter' => ['diocese' => $d],
                ])->values()->all();
        },
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
        'subcategories' => function () {
            return collect(config('reference_data.language_list', []))
                ->map(fn($l) => [
                    'label' => "$l Profiles",
                    'slug' => \Str::slug($l),
                    'filter' => ['mother_tongue' => $l],
                ])->values()->all();
        },
    ],

    'community-matrimony' => [
        'label' => 'Community Matrimony',
        'show_search' => true,
        'subcategories' => function () {
            return collect(\App\Models\Community::getCasteList())
                ->filter(fn($c) => $c !== 'Other')
                ->map(fn($c) => [
                    'label' => "$c Profiles",
                    'slug' => \Str::slug($c),
                    'filter' => ['caste' => $c],
                ])->values()->all();
        },
    ],

    'hindu-matrimony' => [
        'label' => 'Hindu Matrimony',
        'show_search' => true,
        'subcategories' => function () {
            return collect(\App\Models\Community::getCasteList('Hindu'))
                ->filter(fn($c) => $c !== 'Other')
                ->map(fn($c) => [
                    'label' => "$c Profiles",
                    'slug' => \Str::slug($c),
                    'filter' => ['religion' => 'Hindu', 'caste' => $c],
                ])->values()->all();
        },
    ],

    'muslim-matrimony' => [
        'label' => 'Muslim Matrimony',
        'show_search' => false,
        'subcategories' => function () {
            return collect(config('reference_data.muslim_sect_list', []))
                ->filter(fn($s) => $s !== 'Other')
                ->map(fn($s) => [
                    'label' => "$s Profiles",
                    'slug' => \Str::slug($s),
                    'filter' => ['religion' => 'Muslim', 'muslim_sect' => $s],
                ])->values()->all();
        },
    ],

    'jain-matrimony' => [
        'label' => 'Jain Matrimony',
        'show_search' => false,
        'subcategories' => function () {
            return collect(config('reference_data.jain_sect_list', []))
                ->filter(fn($s) => $s !== 'Other')
                ->map(fn($s) => [
                    'label' => "$s Profiles",
                    'slug' => \Str::slug($s),
                    'filter' => ['religion' => 'Jain', 'jain_sect' => $s],
                ])->values()->all();
        },
    ],

];
