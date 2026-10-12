<?php

use App\Http\Controllers\SearchController;
use App\Models\Profile;

/*
| Keyword search (Oct 2026): 46% of typed searches found nothing. A Matri ID
| typed any way finds that profile; several words each have to match some
| field, in any order; if nothing matches every word, any word will do.
*/

it('finds a Matri ID however it is typed', function (string $typed) {
    $q = SearchController::applyKeyword(Profile::query(), $typed);

    expect($q->toSql())->toContain('"matri_id" like ?')->not->toContain('full_name')
        ->and($q->getBindings())->toBe(['%KM100123%']);
})->with(['KM100123', 'km100123', 'km 100123', 'KM-100123', ' km.100123 ']);

it('splits words, drops filler words, and needs every word to match somewhere', function () {
    expect(SearchController::keywordWords('Billava engineer from Mangalore'))->toBe(['billava', 'engineer', 'mangalore']);

    $q = SearchController::applyKeyword(Profile::query(), 'Billava Mangalore');
    $sql = $q->toSql();

    expect(substr_count($sql, '"full_name" like ?'))->toBe(2)
        ->and($sql)->toContain('"caste" like ?')->toContain('"sub_caste" like ?')->toContain('"native_place" like ?')->toContain('"occupation" like ?')
        ->and($q->getBindings())->toContain('%billava%')->toContain('%mangalore%');
});

it('can match any word instead of every word (the fallback)', function () {
    $all = SearchController::applyKeyword(Profile::query(), 'Billava Mangalore')->toSql();
    $any = SearchController::applyKeyword(Profile::query(), 'Billava Mangalore', matchAll: false)->toSql();

    expect($all)->not->toBe($any)->and($any)->toContain(') or (');
});

it('treats % and _ typed by a member as plain characters', function () {
    expect(SearchController::applyKeyword(Profile::query(), '50%')->getBindings())->toContain('%50\%%');
});
