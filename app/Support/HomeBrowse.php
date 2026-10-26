<?php

namespace App\Support;

use App\Http\Controllers\DiscoverController;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

/**
 * The homepage "Browse profiles by" tabs (Oct 2026, Garland / Harmony designs;
 * Admin → Homepage Content → "Browse profiles by"). Built from the Discover
 * pages, biggest first, and only groups with at least MIN_MEMBERS members, so
 * every link lands on a page worth visiting (owner, 26 Oct 2026). Community
 * comes first and holds every community in one list, the same one as its
 * "View all" page; Mother Tongue comes second. Cached for two hours and
 * rebuilt hourly in the background (routes/console.php), so visitors never
 * wait for the member counts behind it.
 */
class HomeBrowse
{
    public const SETTING = 'show_browse_section';

    /** A chip needs this many members; smaller groups stay one click away under "View all". */
    public const MIN_MEMBERS = 3;

    private const CACHE_KEY = 'home.browse.tabs';

    /**
     * Tabs that have at least one link: [key => ['label', 'links' => [[label, url]], 'all' => url]].
     */
    public static function tabs(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, 7200, fn () => self::build());
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * Rebuild the tabs ahead of visitors (hourly, routes/console.php), only on a
     * site whose homepage shows them. A cold build is ~600 count queries.
     */
    public static function refresh(): void
    {
        $template = SiteSetting::getValue('homepage_template', 'classic');
        if (! in_array($template, ['garland', 'harmony'], true) || SiteSetting::getValue(self::SETTING, '1') !== '1') {
            return;
        }
        Cache::put(self::CACHE_KEY, self::build(), 7200);
    }

    private static function build(): array
    {
        $tabs = [
            'community' => ['label' => 'Community', 'links' => self::links('community-matrimony'), 'all' => 'community-matrimony'],
            'language' => ['label' => 'Mother Tongue', 'links' => self::links('mother-tongue-matrimony', 18), 'all' => 'mother-tongue-matrimony'],
            'location' => ['label' => 'City & District', 'links' => self::links('karnataka-matrimony', 18), 'all' => 'karnataka-matrimony'],
            'nri' => ['label' => 'NRI', 'links' => self::links('nri-matrimony', 18), 'all' => 'nri-matrimony'],
            'profession' => ['label' => 'Profession', 'links' => self::links('occupation-matrimony', 18), 'all' => 'occupation-matrimony'],
            'second' => ['label' => 'Second Marriage', 'links' => self::links('second-marriage'), 'all' => 'second-marriage'],
        ];

        return collect($tabs)
            ->filter(fn ($tab) => ! empty($tab['links']))
            ->map(fn ($tab) => $tab + ['all_url' => route('discover.category', $tab['all'])])
            ->all();
    }

    /** A Discover category's links with at least MIN_MEMBERS members, biggest first. */
    private static function links(string $category, int $limit = 0): array
    {
        $links = collect(DiscoverController::homeLinkCounts($category))
            ->filter(fn ($link) => $link['count'] >= self::MIN_MEMBERS)
            ->map(fn ($link) => ['label' => $link['label'], 'url' => $link['url']])
            ->values();

        return ($limit > 0 ? $links->take($limit) : $links)->all();
    }
}
