<?php

/*
|--------------------------------------------------------------------------
| No member text pasted into JavaScript with '{{ }}'
|--------------------------------------------------------------------------
| `x-data="{ name: '{{ $value }}' }"` looks safe but isn't: Blade turns ' into
| &#039;, and the browser turns it back into ' inside the attribute — which
| ends the JS string. A member named D'Souza could not finish registration
| step 4 on kudla (the whole form's script crashed, so State never appeared),
| and URL parameters pasted this way let a crafted link run code.
| Use @js($value) instead. This scans the views for the risky sources.
*/

it('never pastes member- or URL-supplied values into JS strings with {{ }}', function () {
    $risky = '/\'\{\{\s*(old\(|request\(|session\(|auth\(\)|\$activeTab|\$currentReligion|'
        . '\$r\?->|\$l\?->|\$locationInfo|\$educationDetail|\$religiousInfo|\$profile\b|\$user\b)/';

    $offenders = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));
    foreach ($files as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }
        $path = str_replace('\\', '/', $file->getPathname());
        if (preg_match('#/views/(filament|scribe|emails|vendor)/#', $path)) {
            continue;
        }
        foreach (file($path) as $i => $line) {
            if (preg_match($risky, $line)) {
                $offenders[] = substr($path, strpos($path, '/views/') + 7) . ':' . ($i + 1);
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('@js keeps an apostrophe inside the JavaScript string', function () {
    $html = Illuminate\Support\Facades\Blade::render('<div x-data="{ name: @js($name) }"></div>', ['name' => "Naveen D'Souza"]);

    expect($html)->toContain("name: 'Naveen D\\u0027Souza'")
        ->and($html)->not->toContain('&#039;');
});
