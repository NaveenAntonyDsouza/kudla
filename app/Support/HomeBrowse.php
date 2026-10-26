<?php

namespace App\Support;

use App\Http\Controllers\DiscoverController;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

/**
 * The homepage "Browse profiles by" tabs (Oct 2026, Garland / Harmony designs;
 * Admin → Homepage Content → "Browse profiles by"). Built from the Discover
 * pages, so every link lands on a page that has members. Community comes
 * first (owner, 26 Oct 2026): one list, biggest first, that also holds the
 * groups of other religions (e.g. Roman Catholic, Sunni), so no member is left
 * without a link. Places, languages and professions are biggest first too.
 * Cached for two hours and rebuilt hourly in the background
 * (routes/console.php), so visitors never wait for the member counts behind it.
 */
class HomeBrowse
{
    public const SETTING = 'show_browse_section';

    private const CACHE_KEY = 'home.browse.tabs';

    /** Religion pages whose groups (denominations, sects) join the Community list. */
    private const OTHER_RELIGIONS = ['catholic-matrimony', 'christian-matrimony', 'muslim-matrimony', 'jain-matrimony'];

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
            'community' => ['label' => 'Community', 'links' => self::communityLinks(), 'all' => 'community-matrimony'],
            'location' => ['label' => 'City & District', 'links' => DiscoverController::homeLinks('karnataka-matrimony', 18), 'all' => 'karnataka-matrimony'],
            'nri' => ['label' => 'NRI', 'links' => DiscoverController::homeLinks('nri-matrimony', 18), 'all' => 'nri-matrimony'],
            'language' => ['label' => 'Mother Tongue', 'links' => DiscoverController::homeLinks('mother-tongue-matrimony', 18), 'all' => 'mother-tongue-matrimony'],
            'profession' => ['label' => 'Profession', 'links' => DiscoverController::homeLinks('occupation-matrimony', 18), 'all' => 'occupation-matrimony'],
            'second' => ['label' => 'Second Marriage', 'links' => DiscoverController::homeLinks('second-marriage'), 'all' => 'second-marriage'],
        ];

        return collect($tabs)
            ->filter(fn ($tab) => ! empty($tab['links']))
            ->map(fn ($tab) => $tab + ['all_url' => route('discover.category', $tab['all'])])
            ->all();
    }

    /**
     * Communities and the groups of other religions in one list, biggest first
     * (Billava, Kulal, Bunts, … Roman Catholic, …). A group listed twice keeps
     * its bigger page.
     */
    private static function communityLinks(): array
    {
        return collect(DiscoverController::homeLinkCounts('community-matrimony'))
            ->merge(collect(self::OTHER_RELIGIONS)->flatMap(fn ($category) => DiscoverController::homeLinkCounts($category)))
            ->sortByDesc('count')
            ->unique(fn ($link) => mb_strtolower($link['label']))
            ->sortBy([['count', 'desc'], ['label', 'asc']])
            ->map(fn ($link) => ['label' => $link['label'], 'url' => $link['url']])
            ->values()
            ->all();
    }
}
