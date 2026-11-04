<?php

/*
| Nov 2026, after comparing Chavara Matrimony's cards: a card shows three lines
| on a phone, and community, education, job and place were cut off behind skin
| tone and religion. They now come first; skin tone stays on the full profile.
*/

it('leads the card line with community, education, job and place, without skin tone', function () {
    $card = file_get_contents(resource_path('views/components/profile-card.blade.php'));
    $line = substr($card, strpos($card, '$desc = collect(['), 700);

    expect($line)->not->toContain('complexion')
        ->and(strpos($line, '$community ?: $p->religiousInfo?->religion'))->toBeLessThan(strpos($line, 'highest_education'))
        ->and(strpos($line, 'highest_education'))->toBeLessThan(strpos($line, '->occupation'))
        ->and(strpos($line, '->occupation'))->toBeLessThan(strpos($line, 'native_district'))
        ->and(strpos($line, 'native_district'))->toBeLessThan(strpos($line, "marital_status !== 'Unmarried'"));
});
