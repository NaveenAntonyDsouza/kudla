<?php

namespace App\Support;

use App\Http\Controllers\DiscoverController;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

/**
 * The homepage "Browse profiles by" tabs (Oct 2026, Garland / Harmony designs;
 * Admin → Homepage Content → "Browse profiles by"). Built from the Discover
 * pages, so every link lands on a page that has members. Religions and
 * communities are listed A–Z and side by side, so no group comes first; places,
 * languages and professions are biggest first. Cached for two hours and
 * rebuilt hourly in the background (routes/console.php), so visitors never
 * wait for the member counts behind it.
 */
class HomeBrowse
{
    public const SETTING = 'show_browse_section';

    private const CACHE_KEY = 'home.browse.tabs';

    /** Religion pages, by the name shown on the chip. */
    private const RELIGIONS = [
        'Catholic' => 'catholic-matrimony',
        'Christian' => 'christian-matrimony',
        'Hindu' => 'hindu-matrimony',
        'Jain' => 'jain-matrimony',
        'Muslim' => 'muslim-matrimony',
    ];

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
        $religions = collect(self::RELIGIONS)
            ->filter(fn ($category) => config("discover.{$category}") && DiscoverController::profileCounts($category) !== [])
            ->map(fn ($category, $name) => ['label' => $name, 'url' => route('discover.category', $category)])
            ->values()
            ->all();

        $tabs = [
            'location' => ['label' => 'City & District', 'links' => DiscoverController::homeLinks('karnataka-matrimony', 18), 'all' => 'karnataka-matrimony'],
            'nri' => ['label' => 'NRI', 'links' => DiscoverController::homeLinks('nri-matrimony', 18), 'all' => 'nri-matrimony'],
            'religion' => ['label' => 'Religion & Community', 'religions' => $religions, 'links' => DiscoverController::homeLinks('community-matrimony', 0, true), 'all' => 'community-matrimony'],
            'language' => ['label' => 'Mother Tongue', 'links' => DiscoverController::homeLinks('mother-tongue-matrimony', 18), 'all' => 'mother-tongue-matrimony'],
            'profession' => ['label' => 'Profession', 'links' => DiscoverController::homeLinks('occupation-matrimony', 18), 'all' => 'occupation-matrimony'],
            'second' => ['label' => 'Second Marriage', 'links' => DiscoverController::homeLinks('second-marriage'), 'all' => 'second-marriage'],
        ];

        return collect($tabs)
            ->filter(fn ($tab) => ! empty($tab['links']) || ! empty($tab['religions']))
            ->map(fn ($tab) => $tab + ['religions' => [], 'all_url' => route('discover.category', $tab['all'])])
            ->all();
    }
}
