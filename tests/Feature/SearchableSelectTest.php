<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

/*
|--------------------------------------------------------------------------
| <x-searchable-select> — long single-choice lists with a search box
|--------------------------------------------------------------------------
| Education (192 options) and occupation (203) in registration step 3 and
| the profile editor. The native <select> must still carry every option,
| the saved choice and `required`, because it is what the form submits.
*/

beforeEach(fn () => View::share('errors', new ViewErrorBag));

function renderSearchableSelect(array $props): string
{
    return Blade::render('<x-searchable-select :name="$name" :label="$label" :options="$options" :selected="$selected" :required="$required" />',
        array_merge(['label' => 'Education', 'selected' => null, 'required' => false], $props));
}

it('keeps every option, the saved choice and required on the native select', function () {
    $list = config('reference_data.educational_qualifications_list');
    $html = renderSearchableSelect(['name' => 'highest_education', 'options' => $list, 'selected' => 'MBA/PGDM', 'required' => true]);

    expect(substr_count($html, '<option value="') - 1)->toBe(collect($list)->flatten()->count())
        ->and($html)->toContain('<option value="MBA/PGDM" selected')
        ->and($html)->toMatch('/<select name="highest_education" id="highest_education"[^>]*required/')
        ->and($html)->toContain('Type to search')
        // Group headings lose the "12. " numbering
        ->and($html)->toContain('<optgroup label="Masters - Management">');
});

it('keeps a saved answer that is no longer in the list instead of blanking it', function () {
    $html = renderSearchableSelect(['name' => 'occupation', 'options' => ['IT' => ['Developer']], 'selected' => 'Lighthouse Keeper']);

    expect($html)->toContain('<option value="Lighthouse Keeper" selected')
        ->and($html)->toContain('<option value="Developer"');
});

it('handles a flat list as one unlabelled group', function () {
    $html = renderSearchableSelect(['name' => 'x', 'options' => ['A', 'B & C'], 'selected' => 'B & C']);

    expect($html)->not->toContain('<optgroup')
        ->and($html)->toContain('<option value="B &amp; C" selected');
});
