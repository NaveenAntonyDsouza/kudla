<?php

// Boots the Laravel app so config() resolves (Unit tests don't by default).
uses(Tests\TestCase::class);

/*
| The denomination_rite map drives the Denomination → Diocese cascade: a
| Catholic denomination resolves to a rite, and the cascade shows the dioceses
| tagged with that rite. Non-Catholic denominations are intentionally absent
| (no rite → free-text diocese). This locks the mapping so it can't silently
| drift. Pure config read — no DB.
*/

it('maps each Catholic denomination to the correct rite', function () {
    $map = config('reference_data.denomination_rite');

    expect($map)->toBeArray();
    expect($map['Roman Catholic'])->toBe('Latin');
    expect($map['Anglo Indian'])->toBe('Latin');
    expect($map['Nadar Christian'])->toBe('Latin');
    expect($map['Cheramar Christian'])->toBe('Latin');
    expect($map['Syrian Catholic'])->toBe('Syro-Malabar');
    expect($map['Knanaya Catholic'])->toBe('Syro-Malabar');
    expect($map['Malankara Catholic'])->toBe('Syro-Malankara');
});

it('omits Non-Catholic denominations so they fall back to free-text diocese', function () {
    $map = config('reference_data.denomination_rite');

    foreach (['Orthodox', 'Jacobite', 'CSI Christian', 'Pentecostal', 'Brethren', 'Other'] as $nonCatholic) {
        expect($map)->not->toHaveKey($nonCatholic);
    }
});
