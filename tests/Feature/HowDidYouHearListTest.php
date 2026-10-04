<?php

use App\Filament\Pages\ReferenceDataEditor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| "How did you hear about us?" options
|--------------------------------------------------------------------------
| Registration renders the list as <optgroup>s, so it must stay grouped —
| including after an admin saves it in the Reference Data editor.
*/

it('is a short grouped list that keeps the spellings earlier members chose', function () {
    $list = config('reference_data.how_did_you_hear_list');
    $all = collect($list)->flatten();

    expect($list)->each->toBeArray()
        ->and($all->count())->toBeLessThanOrEqual(25)
        ->and($all->duplicates())->toBeEmpty()
        ->and($all->all())->toContain('Google Search', 'Instagram', 'Facebook', 'YouTube', 'Friends', 'Family', 'Others')
        ->and($all->all())->not->toContain('Asianet', 'Deepika', 'Radio Mango', 'Yandex');
});

it('moves every answer from the old list onto an option of the new one', function () {
    $migration = require database_path('migrations/2026_10_04_000005_map_how_did_you_hear_answers_to_new_list.php');
    $newOptions = collect(config('reference_data.how_did_you_hear_list'))->flatten()->all();
    $oldOptions = ['Auto', 'Bill Board', 'Boat', 'Bus', 'Metro', 'Carnival', 'Consumer Fair', 'Conventions',
        'College Magazines', 'Community Magazines', 'Other Magazines', 'Parish Bulletins', 'Deepika', 'Deshabhimani',
        'Janapraksham', 'Keralasabha', 'Hoarding', 'Notice/Pamphlet', 'Others', 'Tele-In', 'Telemarketing',
        'Aakashavani', 'Club FM', 'Local Radios', 'Radio Mango', 'Radio Mirchi', 'Red FM', 'Church', 'Family',
        'Family Marriage', 'Friends', 'Bing', 'DuckDuckGo', 'Google Search', 'Yahoo', 'Yandex', 'Facebook',
        'Instagram', 'LinkedIn', 'Pinterest', 'Snapchat', 'YouTube', '24news', 'Asianet', 'Asianet Movies',
        'Asianet News', 'Flowers'];

    Schema::create('profiles', function (Blueprint $t) {
        $t->id();
        $t->string('how_did_you_hear_about_us')->nullable();
    });
    DB::table('profiles')->insert(array_map(fn ($v) => ['how_did_you_hear_about_us' => $v], [...$oldOptions, null]));

    $migration->up();

    $after = DB::table('profiles')->orderBy('id')->pluck('how_did_you_hear_about_us')->all();
    expect(array_filter($after))->each->toBeIn($newOptions)
        ->and(array_keys($migration::MAP))->each->toBeIn($newOptions)
        ->and(end($after))->toBeNull()
        ->and($after[0])->toBe('Auto / Bus Advertisement')
        ->and($after[array_search('Google Search', $oldOptions)])->toBe('Google Search');

    Schema::dropIfExists('profiles');
});

it('round-trips through the Reference Data editor without losing its groups', function () {
    $editor = new ReferenceDataEditor;
    $grouped = ReferenceDataEditor::categories()['how_did_you_hear_list']['grouped'];
    $toText = (new ReflectionMethod($editor, 'dataToText'))->getClosure($editor);
    $toData = (new ReflectionMethod($editor, 'textToData'))->getClosure($editor);

    $list = config('reference_data.how_did_you_hear_list');

    expect($grouped)->toBeTrue()
        ->and($toData($toText($list, $grouped), $grouped))->toBe($list);
});
