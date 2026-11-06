<?php

use Symfony\Component\Finder\Finder;

/*
| Filament 5, 6 Nov 2026: Filament 3's Filament\Forms\Get and Filament\Forms\Set no longer
| exist. Filament hands form closures Filament\Schemas\Components\Utilities\Get / Set by
| parameter name, so a closure still type-hinted with the old class threw a TypeError as
| soon as it ran (typing a plan name or a page title, opening the advertisement or a call
| log form, picking a quick reply). The forms themselves are tested on the bench, in
| tests/PrivacyBench/AdminFormGetSetTest.php.
*/

it('has no Filament 3 Forms\Get / Forms\Set left in the app (use Schemas\Components\Utilities)', function () {
    // class names in the code only (Forms\Get, Filament\Forms\Set, \Filament\Forms\Get), not comments
    $hits = collect(Finder::create()->in(app_path())->name('*.php')->files())
        ->filter(fn ($f) => collect(PhpToken::tokenize($f->getContents()))->contains(fn (PhpToken $t) => $t->is([T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])
            && preg_match('/(^|\\\\)Forms\\\\(Get|Set)$/', $t->text)))
        ->map(fn ($f) => $f->getRelativePathname())->values()->all();

    expect($hits)->toBe([]);
});
