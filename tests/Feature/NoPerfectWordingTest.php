<?php

use Illuminate\Support\Facades\File;

/*
| Owner, 24 Oct 2026: "nothing is perfect" — no "perfect match / perfect
| partner" promises anywhere on the six sites. Replaced with "life partner"
| and "the right match". Site-owned database text (settings, emails, sample
| stories) was changed the same day; this keeps the code from bringing it back.
*/

it('never promises a "perfect" match in pages, emails, admin hints or seed content', function () {
    $paths = [resource_path('views'), app_path(), database_path('seeders'), config_path('fonts.php')];

    $hits = collect($paths)
        ->flatMap(fn ($path) => is_dir($path) ? File::allFiles($path) : [new SplFileInfo($path)])
        ->filter(fn ($file) => preg_match('/perfect/i', file_get_contents($file->getPathname())))
        ->map(fn ($file) => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname()))
        ->values()
        ->all();

    expect($hits)->toBe([]);
});
