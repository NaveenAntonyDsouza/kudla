<?php

/*
| 4 Nov 2026 (privacy check): on Kudla the root .htaccess served ANY existing
| file in the project folder, so the stale .git folder (source + history) and
| docs/ were downloadable, and scanners were fetching .git that day. Now only
| public/ serves files; dot folders are closed except .well-known (SSL checks).
| This file is used where the host's web root is the project folder (Bluehost:
| Kudla, Crasta); Hostinger's web root holds public files only.
*/

it('serves files only from public/, keeps dot folders closed and source folders denied', function () {
    $rules = file_get_contents(base_path('.htaccess'));

    expect($rules)
        ->toContain('RewriteRule (^|/)\.(?!well-known(/|$)) - [F,L]')
        ->toContain("RewriteCond %{REQUEST_URI} ^/\\.well-known/")
        ->toContain('RewriteRule ^(app|bootstrap|config|database|resources|routes|tests|node_modules)(/|$) - [F,L]')
        ->toContain('RewriteRule ^storage/(logs|framework|app)(/|$) - [F,L]')
        ->toContain('RewriteRule ^(.*)$ public/$1 [L]')
        ->not->toContain('Allow direct file access for existing files');

    // the only "serve the file as it is" rule is the .well-known one
    expect(substr_count($rules, 'RewriteCond %{REQUEST_FILENAME} -f'))->toBe(1)
        ->and(strpos($rules, "RewriteCond %{REQUEST_URI} ^/\\.well-known/"))->toBeLessThan(strpos($rules, 'RewriteCond %{REQUEST_FILENAME} -f'));
});
