<?php

use App\Http\Controllers\SearchController;
use App\Support\Analytics;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/*
|--------------------------------------------------------------------------
| A search that finds nothing shows the closest profiles
|--------------------------------------------------------------------------
| Weekly review, 4 Nov 2026: 6 of 21 member searches that week found
| nothing, and an empty page is the worst answer on a site where a narrow
| search often fails. Lesser choices are left out one group at a time,
| then the age range widens a little; who a member wants to marry
| (religion, denomination, caste, mother tongue, marital status, physical
| status, with photo) is never left out.
*/

$asked = fn (array $more = []) => array_merge([
    'search' => '1', 'religion' => ['Christian'], 'caste' => ['Konkani'], 'mother_tongue' => 'Konkani',
    'marital_status' => 'Never Married', 'age_from' => '24', 'age_to' => '28',
    'occupation' => ['Nurse'], 'education' => ['B.Sc'], 'native_district' => 'Udupi', 'diet' => 'Any',
], $more);

it('leaves out the lesser choices one group at a time until someone matches', function () use ($asked) {
    $tried = [];
    $closest = SearchController::closestSearchParams($asked(), function (array $p) use (&$tried) {
        $tried[] = array_keys($p);

        return ! isset($p['occupation']) && ! isset($p['education']);   // someone matches without those two
    });

    expect($closest['left_out'])->toBe(['occupation', 'education'])   // diet was "Any": not a choice, not tried
        ->and($closest['wider_age'])->toBeFalse()
        ->and($closest['params'])->toHaveKeys(['religion', 'caste', 'mother_tongue', 'marital_status', 'native_district', 'age_from', 'age_to'])
        ->and($tried)->toHaveCount(2);
});

it('never leaves out religion, caste, mother tongue or marital status', function () use ($asked) {
    // only a different caste would match: nothing comes close, so the empty page stays
    $closest = SearchController::closestSearchParams($asked(), fn (array $p) => ! isset($p['caste']));

    expect($closest)->toBeNull();
});

it('widens the age range a little as the last step', function () use ($asked) {
    $closest = SearchController::closestSearchParams($asked(), fn (array $p) => (int) $p['age_from'] <= 21);

    expect($closest['wider_age'])->toBeTrue()
        ->and($closest['params']['age_from'])->toBe('21')
        ->and($closest['params']['age_to'])->toBe('31')
        ->and($closest['left_out'])->toBe(['occupation', 'education', 'home place']);
});

it('says plainly what the closest profiles leave out', function () {
    expect(SearchController::closestNote(['occupation'], false))
        ->toBe('No profile matched all your choices, so these are the closest: they leave out your occupation choice.')
        ->and(SearchController::closestNote(['occupation', 'education', 'home place'], true))
        ->toBe('No profile matched all your choices, so these are the closest: they leave out your occupation, education and home place choices and allow 3 years either side of your age range.')
        ->and(SearchController::closestNote([], true))
        ->toBe('No profile matched all your choices, so these are the closest: they allow 3 years either side of your age range.');
});

it('still counts the search as finding nothing, and records the closest apart', function () {
    $request = Request::create('/search/results', 'GET', ['search' => 1, 'occupation' => ['Nurse'], 'age_from' => '24']);
    $request->setLaravelSession(app('session.store'));
    app()->instance('request', $request);

    $track = (new ReflectionMethod(SearchController::class, 'trackSearch'))->getClosure(new SearchController);
    $track('filters', $request, new LengthAwarePaginator([], 12, 20, 1), ['params' => [], 'left_out' => ['occupation'], 'wider_age' => true]);

    $params = Analytics::pull()[0]['params'];
    expect($params['results'])->toBe(0)
        ->and($params['closest_results'])->toBe(12)
        ->and($params['loosened'])->toBe('occupation,age');
});
