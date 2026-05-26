<?php

use App\Models\ReferenceDataOption;

/*
| ReferenceDataOption::assembleList() turns ordered, active DB rows into the
| shape config() expects: a FLAT list when no row carries a group_label, or a
| GROUPED map when they do (e.g. Denomination -> Catholic / Non-Catholic).
| This is what GatewayConfigProvider uses at boot. Pure array logic — no DB.
*/

it('returns a flat list when no row has a group_label', function () {
    $rows = [
        ['value' => 'Sunni', 'group_label' => null],
        ['value' => 'Shia', 'group_label' => null],
        ['value' => 'Other', 'group_label' => null],
    ];

    expect(ReferenceDataOption::assembleList($rows))->toBe(['Sunni', 'Shia', 'Other']);
});

it('returns a grouped map when rows carry group_labels', function () {
    $rows = [
        ['value' => 'Roman Catholic', 'group_label' => 'Catholic'],
        ['value' => 'Syrian Catholic', 'group_label' => 'Catholic'],
        ['value' => 'Orthodox', 'group_label' => 'Non-Catholic'],
        ['value' => 'Protestant', 'group_label' => 'Non-Catholic'],
    ];

    expect(ReferenceDataOption::assembleList($rows))->toBe([
        'Catholic' => ['Roman Catholic', 'Syrian Catholic'],
        'Non-Catholic' => ['Orthodox', 'Protestant'],
    ]);
});

it('preserves group order by first appearance', function () {
    $rows = [
        ['value' => 'B1', 'group_label' => 'Beta'],
        ['value' => 'A1', 'group_label' => 'Alpha'],
        ['value' => 'B2', 'group_label' => 'Beta'],
    ];

    expect(array_keys(ReferenceDataOption::assembleList($rows)))->toBe(['Beta', 'Alpha']);
});

it('buckets ungrouped rows under Other when the category is grouped', function () {
    $rows = [
        ['value' => 'X', 'group_label' => 'G1'],
        ['value' => 'Y', 'group_label' => null],
    ];

    expect(ReferenceDataOption::assembleList($rows))->toBe([
        'G1' => ['X'],
        'Other' => ['Y'],
    ]);
});

it('returns an empty array for no rows', function () {
    expect(ReferenceDataOption::assembleList([]))->toBe([]);
});
