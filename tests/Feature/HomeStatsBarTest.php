<?php

/*
| The "0+ Members / 0+ Successful Marriages" strip: the admin switch
| (Homepage Settings → "Show the stats strip", show_stats_bar) now hides it
| on the Classic design too (owner, 11 Oct 2026: off on all sites but Kudla
| and GCM).
*/

it('hides the Classic stats strip when the admin switches it off', function () {
    $classic = file_get_contents(resource_path('views/pages/home/classic.blade.php'));
    $at = strpos($classic, "SiteSetting::getValue('show_stats_bar', '1') === '1')");

    expect($at)->not->toBeFalse()
        ->and(strpos($classic, '<p class="mt-1 text-sm text-gray-600">Years of Service</p>'))->toBeGreaterThan($at)
        ->and(file_get_contents(resource_path('views/pages/home/modern.blade.php')))->toContain("getValue('show_stats_bar', '1')");
});
