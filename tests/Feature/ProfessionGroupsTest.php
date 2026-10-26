<?php

use App\Http\Controllers\DiscoverController;
use App\Models\Profile;
use App\Services\DiscoverConfigService;
use App\Support\DiscoverSeo;
use Illuminate\Support\Facades\Cache;

/*
| Profession groups (Oct 2026): the homepage "Profession" tab and Discover →
| Profession Matrimony group the job titles the way families search ("Doctors
| & Nurses", "Engineers & IT", "Government & Defence"), instead of 108 titles
| led by "Manager" and "Supervisors".
*/

it('puts every job title on the site in exactly one group, spelled as on the list', function () {
    $titles = collect(config('reference_data.occupation_category_list'))->flatten();
    $grouped = collect(config('profession_groups.groups'))->flatMap(fn ($g) => $g['occupations']);
    $unassigned = collect(config('profession_groups.unassigned'));
    $everywhere = $grouped->merge($unassigned);

    expect($titles->diff($everywhere)->values()->all())->toBe([])                       // no title left out
        ->and($everywhere->diff($titles)->values()->all())->toBe([])                     // no typo / unknown title
        ->and($everywhere->duplicates()->values()->all())->toBe([])                      // no title in two places
        ->and(collect(config('profession_groups.groups'))->pluck('slug')->duplicates()->all())->toBe([]);
});

it('builds one Discover page per group, filtering on its job titles', function () {
    $subs = collect(app(DiscoverConfigService::class)->professionGroups());

    expect($subs->firstWhere('slug', 'doctors-nurses'))
        ->label->toBe('Doctors & Nurses Profiles')
        ->filter->toHaveKey('occupation')
        ->and($subs->firstWhere('slug', 'doctors-nurses')['filter']['occupation'])->toContain('General Nurse')->toContain('Allopathic Doctor')
        ->and(config('discover.profession-matrimony.subcategories_source'))->toBe('professionGroups');

    $sql = DiscoverController::applyFilters(Profile::query(), ['occupation' => ['Driver', 'Mason']])->toSql();
    expect($sql)->toContain('"occupation" in (?, ?)');
    expect(DiscoverController::applyFilters(Profile::query(), ['occupation' => 'Driver'])->toSql())->toContain('"occupation" = ?');
});

it('describes a group page in plain words', function () {
    Cache::put('site_setting.seo_region', 'Mangaluru, Udupi and Tulu Nadu', 60);
    Cache::put('site_setting.site_name', 'Kudla Matrimony', 60);
    $seo = DiscoverSeo::forResults('profession-matrimony', 'Doctors & Nurses Profiles');

    expect($seo['heading'])->toBe('Doctors & Nurses Matrimony')
        ->and($seo['intro'])->toBe('Find brides and grooms from Mangaluru, Udupi and Tulu Nadu working in medicine, nursing and healthcare.');
});

it('shows the groups in the homepage Profession tab, biggest first', function () {
    Cache::put('discover.counts.profession-matrimony', ['corporate-office' => 220, 'engineers-it' => 47, 'lawyers-legal' => 2], 60);
    Cache::put('discover.counts.community-matrimony', [], 60);
    Cache::forget('home.browse.tabs');

    $tabs = \App\Support\HomeBrowse::tabs();
    expect(array_column($tabs['profession']['links'], 'label'))->toBe(['Corporate & Office', 'Engineers & IT'])   // Lawyers: 2 members, "View all" only
        ->and($tabs['profession']['all_url'])->toBe(route('discover.category', 'profession-matrimony'));
});
