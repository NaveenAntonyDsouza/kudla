<?php

use App\Http\Controllers\SearchController;
use App\Models\PartnerPreference;
use Illuminate\Support\Facades\Blade;

/*
|--------------------------------------------------------------------------
| "Any" is the default, and it means no limit
|--------------------------------------------------------------------------
| Owner, 4 Nov 2026: every Partner Preference and search field starts at
| "Any" instead of a grey "Select" that looked like an unfinished required
| field. "Any" used to tick every option and send the whole list, so on
| income, occupation or education it quietly left out members who never
| filled that field in. Now "Any" is no limit: nothing chosen, nothing (or a
| single "Any") sent, nothing stored, no filter in the search.
*/

it('shows Any for an untouched preference or search field, and sends no list for it', function () {
    $html = Blade::render('<x-multi-select name="occupation" label="Occupation" :options="$o" />', ['o' => ['Nurse', 'Engineer']]);

    expect($html)->toContain('anyAllowed: true')
        ->toContain("get isAny() {\n        return this.anyAllowed && (this.selected.length === 0 || this.selected.includes('Any'));")
        ->toContain("if (this.isAny) return 'Any';")                      // the button reads "Any", not "Select"
        ->toContain('x-for="val in sent"')                               // hidden inputs: what "sent" says
        ->toContain("return this.isAny ? (this.hadChoice ? ['Any'] : []) : this.selected;")
        ->toContain("toggle(val) {\n        if (val === 'Any') {\n            this.selected = [];")
        ->not->toContain("['Any', ...this.allOptions]");                 // never the whole list again
});

it('keeps Select, never Any, on a member\'s own details', function () {
    $html = Blade::render('<x-multi-select name="hobbies" label="Hobbies" :options="$o" :showAny="false" />', ['o' => ['Music', 'Reading']]);
    expect($html)->toContain('anyAllowed: false');

    // the onboarding Lifestyle page now hides "Any" like the profile edit page always did
    $lifestyle = file_get_contents(resource_path('views/onboarding/lifestyle.blade.php'));
    expect(substr_count($lifestyle, '<x-multi-select'))->toBe(6)
        ->and(substr_count($lifestyle, ':showAny="false"'))->toBe(6);
});

it('stores no limit for Any, for an empty list, and for an old list that carries Any', function () {
    expect(PartnerPreference::listOrNull(['Any']))->toBeNull()
        ->and(PartnerPreference::listOrNull(['Any', 'Hindu', 'Christian', 'Muslim']))->toBeNull()
        ->and(PartnerPreference::listOrNull([]))->toBeNull()
        ->and(PartnerPreference::listOrNull(['Hindu', 'Jain']))->toBe(['Hindu', 'Jain']);
});

it('drops Any from a search, old links included, and keeps real choices', function () {
    expect(SearchController::withoutAny([
        'search' => '1', 'religion' => ['Any', 'Hindu', 'Christian'], 'occupation' => ['Any'],
        'diet' => 'Any', 'caste' => ['Billava'], 'age_from' => '24',
    ]))->toBe(['search' => '1', 'caste' => ['Billava'], 'age_from' => '24']);
});
