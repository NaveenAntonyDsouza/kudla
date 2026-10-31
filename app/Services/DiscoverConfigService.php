<?php

namespace App\Services;

use App\Models\Community;
use Illuminate\Support\Str;

/**
 * DiscoverConfigService — provides dynamic subcategory lists for discover pages.
 *
 * Why this exists:
 * The discover config used to embed PHP closures inside config/discover.php to
 * compute subcategory lists at runtime (e.g., list of countries from reference_data).
 * PHP closures are not serializable, which broke `php artisan config:cache`.
 *
 * Solution: each closure is now a method on this service. The config file references
 * the method by name (string), and DiscoverController::resolveSubcategories() calls
 * it via app(DiscoverConfigService::class)->{$methodName}().
 *
 * Adding a new dynamic subcategory list:
 *   1. Add a public method here that returns array of [['label', 'slug', 'filter']]
 *   2. Reference it in config/discover.php as 'subcategories_source' => 'methodName'
 */
class DiscoverConfigService
{
    /**
     * NRI Matrimony — all countries except India, for members who live or work
     * there (Oct 2026: many Gulf workers give only their working country). On a
     * site that calls it "Country" (most members abroad: NriWording) India is
     * listed too.
     */
    public function nriCountries(): array
    {
        $withIndia = \App\Support\NriWording::byCountry();
        $countries = [];
        foreach (config('reference_data.country_list', []) as $group => $list) {
            foreach ($list as $c) {
                if ($c !== 'India' || $withIndia) {
                    $countries[] = [
                        'label' => "$c Profiles",
                        'slug' => Str::slug($c),
                        'filter' => ['abroad_country' => $c],
                    ];
                }
            }
        }
        return $countries;
    }

    /**
     * Catholic Matrimony — Catholic denominations.
     */
    public function catholicDenominations(): array
    {
        return collect(config('reference_data.denomination_list.Catholic', []))
            ->filter(fn ($d) => $d !== 'Other')
            ->map(fn ($d) => [
                'label' => "$d Profiles",
                'slug' => Str::slug($d),
                'filter' => ['denomination' => $d],
            ])->values()->all();
    }

    /**
     * Karnataka Matrimony — districts of Karnataka.
     */
    public function karnatakaDistricts(): array
    {
        return collect(config('locations.state_district_map.Karnataka', []))
            ->map(fn ($d) => [
                'label' => "$d Profiles",
                'slug' => Str::slug($d),
                'filter' => ['native_state' => 'Karnataka', 'native_district' => $d],
            ])
            // Tulu Nadu reaches into Kerala: 33 Kudla members are from Kasaragod (Oct 2026)
            ->push(['label' => 'Kasaragod Profiles', 'slug' => 'kasaragod', 'filter' => ['native_state' => 'Kerala', 'native_district' => 'Kasaragod']])
            ->values()->all();
    }

    /** Districts that are one place to the people working there. */
    private const WORK_PLACES_MERGED = [
        'Bangalore' => ['state' => 'Karnataka', 'districts' => ['Bangalore Urban', 'Bangalore Rural']],
        'Mumbai' => ['state' => 'Maharashtra', 'districts' => ['Mumbai City', 'Mumbai Suburban']],
    ];

    /**
     * Working in — where members work in India, by district (Oct 2026: on Kudla
     * Dakshina Kannada 208, Bangalore 89, Udupi 41). Abroad is the NRI list.
     */
    public function workPlaces(): array
    {
        $merged = collect(self::WORK_PLACES_MERGED)->flatMap(fn ($place) => $place['districts'])->all();
        $places = [];
        foreach (config('locations.state_district_map', []) as $state => $districts) {
            foreach ($districts as $d) {
                if (! in_array($d, $merged, true)) {
                    $places[] = ['label' => "$d Profiles", 'slug' => Str::slug($d), 'filter' => ['working_state' => $state, 'working_district' => $d]];
                }
            }
        }
        foreach (self::WORK_PLACES_MERGED as $name => $place) {
            $places[] = ['label' => "$name Profiles", 'slug' => Str::slug($name), 'filter' => ['working_state' => $place['state'], 'working_district' => $place['districts']]];
        }

        return $places;
    }

    /**
     * Christian Matrimony — non-Catholic Christian denominations.
     */
    public function nonCatholicDenominations(): array
    {
        return collect(config('reference_data.denomination_list.Non-Catholic', []))
            ->filter(fn ($d) => $d !== 'Other')
            ->map(fn ($d) => [
                'label' => "$d Profiles",
                'slug' => Str::slug($d),
                'filter' => ['denomination' => $d],
            ])->values()->all();
    }

    /**
     * Occupation Matrimony — flatten occupation_category_list.
     */
    public function occupations(): array
    {
        $items = [];
        foreach (config('reference_data.occupation_category_list', []) as $category => $occupations) {
            foreach ($occupations as $occ) {
                $items[] = [
                    'label' => "$occ Profiles",
                    'slug' => Str::slug($occ),
                    'filter' => ['occupation' => $occ],
                ];
            }
        }
        return $items;
    }

    /**
     * Profession Matrimony — job titles grouped the way families search
     * ("Doctors & Nurses", "Engineers & IT", config/profession_groups.php).
     * Only titles on this site's occupation list are used.
     */
    public function professionGroups(): array
    {
        $onSite = collect(config('reference_data.occupation_category_list', []))->flatten()->all();

        return collect(config('profession_groups.groups', []))
            ->map(fn ($group) => [
                'label' => "{$group['label']} Profiles",
                'slug' => $group['slug'],
                'filter' => ['occupation' => array_values(array_intersect($group['occupations'], $onSite))],
            ])
            ->filter(fn ($sub) => $sub['filter']['occupation'] !== [])
            ->values()->all();
    }

    /**
     * Diocese Matrimony.
     */
    public function dioceses(): array
    {
        return collect(config('reference_data.diocese_list', []))
            ->filter(fn ($d) => $d !== 'Other')
            ->map(fn ($d) => [
                'label' => "$d Profiles",
                'slug' => Str::slug($d),
                'filter' => ['diocese' => $d],
            ])->values()->all();
    }

    /**
     * Mother Tongue Matrimony — all configured languages.
     */
    public function languages(): array
    {
        return collect(config('reference_data.language_list', []))
            ->map(fn ($l) => [
                'label' => "$l Profiles",
                'slug' => Str::slug($l),
                'filter' => ['mother_tongue' => $l],
            ])->values()->all();
    }

    /**
     * Religions whose groups a site leaves out of its Community list (setting
     * community_hidden_religions, a JSON list; Kudla hides "Muslim", owner
     * 26 Oct 2026). Members stay searchable; only these links are left out.
     */
    public static function hiddenCommunityReligions(): array
    {
        $value = json_decode((string) \App\Models\SiteSetting::getValue('community_hidden_religions', '[]'), true);

        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }

    /**
     * Community Matrimony — every community in one list: the castes, then the
     * Christian denominations and the Muslim and Jain sects (Oct 2026), so a
     * Roman Catholic or Jain member has a community page too. A group that also
     * has its own religion page carries canonical_category, and Google is told
     * that page is the one to show.
     */
    public function communities(): array
    {
        $hidden = self::hiddenCommunityReligions();
        $groups = [
            'Christian' => ['catholic-matrimony' => $this->catholicDenominations(), 'christian-matrimony' => $this->nonCatholicDenominations()],
            'Muslim' => ['muslim-matrimony' => $this->muslimSects()],
            'Jain' => ['jain-matrimony' => $this->jainSects()],
        ];

        $list = collect();
        foreach ($groups as $religion => $sets) {
            if (in_array($religion, $hidden, true)) {
                continue;
            }
            foreach ($sets as $category => $subs) {
                $list = $list->merge(collect($subs)->map(fn ($sub) => $sub + ['canonical_category' => $category]));
            }
        }

        // A group wins over a caste of the same name: Kudla's caste list also has
        // "Roman Catholic", but Catholic members record it as their denomination.
        return $list->merge($this->allCastes())->unique('slug')->values()->all();
    }

    /**
     * Community Matrimony castes — all castes from DB, less those of a hidden religion.
     */
    public function allCastes(): array
    {
        $hidden = collect(self::hiddenCommunityReligions())->flatMap(fn ($religion) => Community::getCasteList($religion))->all();

        return collect(Community::getCasteList())
            ->reject(fn ($c) => \App\Models\ReligiousInfo::isOtherCaste($c) || in_array($c, $hidden, true))
            ->map(fn ($c) => [
                'label' => "$c Profiles",
                'slug' => Str::slug($c),
                'filter' => ['caste' => $c],
            ])->values()->all();
    }

    /**
     * Hindu Matrimony — Hindu castes from DB.
     */
    public function hinduCastes(): array
    {
        return collect(Community::getCasteList('Hindu'))
            ->reject(fn ($c) => \App\Models\ReligiousInfo::isOtherCaste($c))
            ->map(fn ($c) => [
                'label' => "$c Profiles",
                'slug' => Str::slug($c),
                'filter' => ['religion' => 'Hindu', 'caste' => $c],
            ])->values()->all();
    }

    /**
     * Muslim Matrimony — Muslim sects.
     */
    public function muslimSects(): array
    {
        return collect(config('reference_data.muslim_sect_list', []))
            ->filter(fn ($s) => $s !== 'Other')
            ->map(fn ($s) => [
                'label' => "$s Profiles",
                'slug' => Str::slug($s),
                'filter' => ['religion' => 'Muslim', 'muslim_sect' => $s],
            ])->values()->all();
    }

    /**
     * Jain Matrimony — Jain sects.
     */
    public function jainSects(): array
    {
        return collect(config('reference_data.jain_sect_list', []))
            ->filter(fn ($s) => $s !== 'Other')
            ->map(fn ($s) => [
                'label' => "$s Profiles",
                'slug' => Str::slug($s),
                'filter' => ['religion' => 'Jain', 'jain_sect' => $s],
            ])->values()->all();
    }
}
