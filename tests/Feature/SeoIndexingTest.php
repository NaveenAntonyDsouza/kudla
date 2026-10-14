<?php

use App\Http\Controllers\DiscoverController;
use App\Models\Profile;

/*
| Search Console, Oct 2026: 737 "soft 404" Discover pages with no profiles,
| community pages missing from the sitemap, and a /public/ copy of the site.
*/

it('tells Google not to index a Discover page with no profiles', function () {
    $view = file_get_contents(resource_path('views/discover/results.blade.php'));
    $layout = file_get_contents(resource_path('views/components/layouts/app.blade.php'));

    expect($view)->toContain(":robots=\"\$results->total() === 0 ? 'noindex, follow' : null\"")
        ->and($layout)->toContain("<meta name=\"robots\" content=\"{{ \$robots ?? 'index, follow' }}\">");
});

it('lists every Discover page that has members in the sitemap, and only those', function () {
    expect(file_get_contents(app_path('Http/Controllers/SitemapController.php')))
        ->toContain("DiscoverController::populatedPageUrls()")
        ->and(file_get_contents(app_path('Http/Controllers/DiscoverController.php')))
        ->toContain('if ($count > 0) {');
});

it('applies a Discover filter to a profile query', function () {
    $sql = DiscoverController::applyFilters(Profile::query(), ['religion' => 'Hindu', 'caste' => 'Billava'])->toSql();

    expect(substr_count($sql, 'exists (select * from "religious_info"'))->toBe(2);
});

it('sends /public/... to the real address with a permanent redirect', function () {
    $ht = file_get_contents(base_path('.htaccess'));

    expect($ht)->toContain('RewriteCond %{THE_REQUEST} ^[A-Z]+\s/public/([^\s?]*) [NC]')
        ->toContain('RewriteRule ^ /%1 [R=301,L]')
        ->and(strpos($ht, '/%1 [R=301,L]'))->toBeLessThan(strpos($ht, 'RewriteRule ^(.*)$ public/$1 [L]'));
});
