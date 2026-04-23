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
     * NRI Matrimony — all countries except India.
     */
    public function nriCountries(): array
    {
        $countries = [];
        foreach (config('reference_data.country_list', []) as $group => $list) {
            foreach ($list as $c) {
                if ($c !== 'India') {
                    $countries[] = [
                        'label' => "$c Profiles",
                        'slug' => Str::slug($c),
                        'filter' => ['residing_country' => $c],
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
            ])->values()->all();
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
     * Diocese Matrimony.
     */
    public function dioceses(): array
    {
        return collect(config('reference_data.diocese_list', []))
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
     * Community Matrimony — all castes from DB.
     */
    public function allCastes(): array
    {
        return collect(Community::getCasteList())
            ->filter(fn ($c) => $c !== 'Other')
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
            ->filter(fn ($c) => $c !== 'Other')
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
