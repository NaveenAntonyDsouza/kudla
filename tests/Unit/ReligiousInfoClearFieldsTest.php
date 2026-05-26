<?php

use App\Models\ReligiousInfo;

/*
| ReligiousInfo::clearFieldsForReligion() nulls religion-specific fields that
| don't belong to the chosen religion, so changing a profile's religion never
| leaves stale data behind (e.g. an old caste after Hindu -> Christian).
| Religion-agnostic fields (religion, time/place of birth) are always kept.
| Pure array logic — no DB needed.
*/

function fullReligiousData(): array
{
    return [
        'religion' => 'PLACEHOLDER',
        'caste' => 'Bunts',
        'sub_caste' => 'Shetty',
        'gotra' => 'Kashyapa',
        'nakshatra' => 'Bharani',
        'rashi' => 'Aries (Medam)',
        'dosh' => 'No',
        'jain_sect' => 'Digambar',
        'denomination' => 'Roman Catholic',
        'diocese' => 'Mangalore',
        'diocese_name' => 'Some Diocese',
        'parish_name_place' => 'Some Parish',
        'muslim_sect' => 'Sunni',
        'muslim_community' => 'Sheikh',
        'religious_observance' => 'Practicing',
        'other_religion_name' => 'Zoroastrian',
        'time_of_birth' => '23:50',
        'place_of_birth' => 'Mangalore',
    ];
}

it('keeps Hindu fields and clears the rest for Hindu', function () {
    $d = ReligiousInfo::clearFieldsForReligion(fullReligiousData(), 'Hindu');

    // kept (Hindu/Jain shared)
    expect($d['caste'])->toBe('Bunts');
    expect($d['sub_caste'])->toBe('Shetty');
    expect($d['gotra'])->toBe('Kashyapa');
    expect($d['nakshatra'])->toBe('Bharani');
    expect($d['rashi'])->toBe('Aries (Medam)');
    expect($d['dosh'])->toBe('No');

    // cleared (jain_sect is Jain-only; rest belong to other religions)
    expect($d['jain_sect'])->toBeNull();
    expect($d['denomination'])->toBeNull();
    expect($d['diocese'])->toBeNull();
    expect($d['muslim_sect'])->toBeNull();
    expect($d['religious_observance'])->toBeNull();
    expect($d['other_religion_name'])->toBeNull();
});

it('keeps Hindu/Jain fields plus jain_sect for Jain', function () {
    $d = ReligiousInfo::clearFieldsForReligion(fullReligiousData(), 'Jain');

    expect($d['caste'])->toBe('Bunts');
    expect($d['jain_sect'])->toBe('Digambar');
    expect($d['denomination'])->toBeNull();
    expect($d['muslim_sect'])->toBeNull();
});

it('keeps only Christian fields for Christian', function () {
    $d = ReligiousInfo::clearFieldsForReligion(fullReligiousData(), 'Christian');

    expect($d['denomination'])->toBe('Roman Catholic');
    expect($d['diocese'])->toBe('Mangalore');
    expect($d['diocese_name'])->toBe('Some Diocese');
    expect($d['parish_name_place'])->toBe('Some Parish');

    expect($d['caste'])->toBeNull();
    expect($d['gotra'])->toBeNull();
    expect($d['muslim_sect'])->toBeNull();
    expect($d['jain_sect'])->toBeNull();
});

it('keeps only Muslim fields for Muslim', function () {
    $d = ReligiousInfo::clearFieldsForReligion(fullReligiousData(), 'Muslim');

    expect($d['muslim_sect'])->toBe('Sunni');
    expect($d['muslim_community'])->toBe('Sheikh');
    expect($d['religious_observance'])->toBe('Practicing');

    expect($d['caste'])->toBeNull();
    expect($d['denomination'])->toBeNull();
});

it('keeps only other_religion_name for Other', function () {
    $d = ReligiousInfo::clearFieldsForReligion(fullReligiousData(), 'Other');

    expect($d['other_religion_name'])->toBe('Zoroastrian');
    expect($d['caste'])->toBeNull();
    expect($d['denomination'])->toBeNull();
    expect($d['muslim_sect'])->toBeNull();
});

it('clears every religion-specific field for No Religion but keeps agnostic ones', function () {
    $d = ReligiousInfo::clearFieldsForReligion(fullReligiousData(), 'No Religion');

    foreach (array_keys(ReligiousInfo::RELIGION_FIELDS) as $field) {
        expect($d[$field])->toBeNull();
    }

    // religion-agnostic fields untouched (the helper never rewrites the
    // 'religion' key itself — the caller sets that — so it stays as passed in)
    expect($d['religion'])->toBe('PLACEHOLDER');
    expect($d['time_of_birth'])->toBe('23:50');
    expect($d['place_of_birth'])->toBe('Mangalore');
});
