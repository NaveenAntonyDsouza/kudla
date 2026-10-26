<?php

use App\Http\Controllers\DiscoverController;
use Illuminate\Support\Facades\Cache;

/*
| Kudla was built from a Catholic site's template (Oct 2026 audit). What every
| member saw is now set by the site itself: religion lists follow the site's
| own order (Kudla: Hindu first), priest / nun / denomination appear only for
| Christians, a caste is never called a "Denomination", and directories are
| listed biggest first, so Catholic pages don't lead on a Tulu site.
*/

it('takes the religion lists from the site, in its own order', function () {
    foreach (['search/public', 'search/index', 'onboarding/partner-preferences'] as $view) {
        $blade = file_get_contents(resource_path("views/{$view}.blade.php"));
        expect($blade)->toContain("config('reference_data.religion_list', [])")
            ->not->toContain("'Christian', 'Hindu', 'Muslim'");
    }
    expect(file_get_contents(app_path('Filament/Pages/AdvancedUserSearch.php')))->not->toContain("['Christian' => 'Christian', 'Hindu' => 'Hindu'");
});

it('shows priest, nun and denomination only where they apply', function () {
    $family = file_get_contents(resource_path('views/profile/preview/family.blade.php'));
    $partner = file_get_contents(resource_path('views/profile/preview/partner.blade.php'));
    $print = file_get_contents(resource_path('views/profile/print.blade.php'));

    expect($family)->toContain("\$showClergy = \$profile->religiousInfo?->religion === 'Christian'")
        ->toContain('@if($showClergy)<div><p class="text-xs text-gray-500">Priest</p>')
        ->toContain('@if($showClergy)<div><p class="text-xs text-gray-500">Nun</p>')
        ->and($partner)->toContain('<p class="text-xs text-gray-500">Religion</p>')->toContain('<p class="text-xs text-gray-500">Caste</p>')
        ->toContain("@if(in_array('Christian', (array) \$pp->religions, true) || ! empty(\$pp->denomination))")
        ->and($print)->toContain("{{ \$profile->religiousInfo->display_denomination ? 'Denomination' : 'Caste' }}:");
});

it('uses the site\'s own keyword examples and no "church weddings" on the blog', function () {
    foreach (['search/public', 'search/index'] as $view) {
        expect(file_get_contents(resource_path("views/{$view}.blade.php")))
            ->toContain("SiteSetting::getValue('keyword_search_examples', '') ?: 'Doctor, Bangalore, Catholic'");
    }
    expect(file_get_contents(resource_path('views/blog/index.blade.php')))->not->toContain('church weddings');
});

it('lists the other directories biggest first', function () {
    foreach (array_keys(config('discover')) as $slug) {
        Cache::put("discover.counts.{$slug}", [], 60);
    }
    Cache::put('discover.counts.catholic-matrimony', ['roman-catholic' => 26], 60);
    Cache::put('discover.counts.christian-matrimony', ['csi-christian' => 1], 60);
    Cache::put('discover.counts.mother-tongue-matrimony', ['tulu' => 427, 'kannada' => 66], 60);
    Cache::put('discover.counts.karnataka-matrimony', ['dakshina-kannada' => 297], 60);
    Cache::put('discover.counts.nri-matrimony', ['uae' => 22], 60);
    Cache::put('site_setting.seo_region', '', 60);
    Cache::put('site_setting.site_name', 'Kudla Matrimony', 60);

    $other = app(DiscoverController::class)->category('nri-matrimony')->getData()['otherCategories'];

    expect(collect($other)->pluck('slug')->all())->toBe(['mother-tongue-matrimony', 'karnataka-matrimony', 'catholic-matrimony', 'christian-matrimony']);
});
