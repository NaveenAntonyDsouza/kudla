<?php

// Viewing another member's profile showed "Print Profile" twice (once for
// everyone, again under the interest/report actions). One link is enough.
it('shows the Print Profile link once on a profile page', function () {
    $view = file_get_contents(resource_path('views/profile/preview.blade.php'));

    expect(substr_count($view, "route('profile.print', \$profile)"))->toBe(1);
});
