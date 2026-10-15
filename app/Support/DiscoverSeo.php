<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * Titles, headings and descriptions for the Discover pages (Oct 2026 SEO).
 *
 * Before this, 250+ browse pages shared one generic description and titles
 * like "Billava Profiles", which is not what families type into Google
 * ("Billava matrimony", "Billava brides Mangalore"). The region wording comes
 * from the `seo_region` site setting, e.g. "Mangaluru, Udupi and Tulu Nadu"
 * on Kudla; a site without it gets the same text minus the place.
 */
class DiscoverSeo
{
    /** Google shows about 60 characters; the site name suffix adds ~18. */
    private const TITLE_BUDGET = 48;

    private const DESCRIPTION_BUDGET = 158;

    public static function region(): string
    {
        return trim((string) SiteSetting::getValue('seo_region', ''));
    }

    /** "Billava Profiles" -> "Billava", "Corporate communication Profiles" -> "Corporate Communication". */
    public static function name(string $label): string
    {
        return ucwords(trim(preg_replace('/\s+Profiles$/i', '', html_entity_decode($label, ENT_QUOTES))));
    }

    /**
     * A results page (one community, place, job...).
     *
     * @return array{title: string, heading: string, intro: string, description: string}
     */
    public static function forResults(string $category, string $label): array
    {
        $n = self::name($label);
        $region = self::region();
        $from = $region !== '' ? " from {$region}" : '';

        [$titles, $heading, $intro] = match ($category) {
            'community-matrimony' => [
                ["{$n} Matrimony – {$n} Brides & Grooms", "{$n} Matrimony – Brides & Grooms"],
                "{$n} Matrimony",
                "Find {$n} brides and grooms{$from}.",
            ],
            'hindu-matrimony' => [
                ["{$n} Matrimony – Hindu Brides & Grooms", "{$n} Hindu Matrimony"],
                "{$n} Matrimony",
                "Find {$n} Hindu brides and grooms{$from}.",
            ],
            'mother-tongue-matrimony' => [
                ["{$n}-Speaking Brides & Grooms", "{$n}-Speaking Matrimony"],
                "{$n}-Speaking Brides & Grooms",
                "Find {$n}-speaking brides and grooms{$from}.",
            ],
            'karnataka-matrimony' => [
                ["{$n} Matrimony – Brides & Grooms from {$n}", "{$n} Matrimony – Brides & Grooms"],
                "{$n} Matrimony",
                "Find brides and grooms from {$n} district in Karnataka.",
            ],
            'nri-matrimony' => [
                ["NRI Brides & Grooms in {$n}", "NRI Matrimony – {$n}"],
                "NRI Matrimony – {$n}",
                "Find NRI brides and grooms{$from} living in {$n}.",
            ],
            'catholic-matrimony', 'christian-matrimony' => [
                [self::with($n, 'Christian', $category === 'christian-matrimony') . ' Matrimony – Brides & Grooms', self::with($n, 'Christian', $category === 'christian-matrimony') . ' Matrimony'],
                self::with($n, 'Christian', $category === 'christian-matrimony') . ' Matrimony',
                'Find ' . self::with($n, 'Christian', $category === 'christian-matrimony') . " brides and grooms{$from}.",
            ],
            'diocese-matrimony' => [
                [self::with($n, 'Diocese') . ' Catholic Matrimony', self::with($n, 'Diocese') . ' Matrimony'],
                self::with($n, 'Diocese') . ' Catholic Matrimony',
                'Find Catholic brides and grooms from the ' . self::with($n, 'Diocese') . '.',
            ],
            'occupation-matrimony' => [
                ["{$n} Matrimony – {$n} Brides & Grooms", "{$n} Matrimony – Brides & Grooms", "{$n} Matrimony"],
                "{$n} Matrimony",
                "Find {$n} brides and grooms{$from}.",
            ],
            'second-marriage' => [
                ["{$n} Brides & Grooms – Second Marriage", "{$n} – Second Marriage Matrimony"],
                "Second Marriage – {$n}",
                'Find ' . mb_strtolower($n) . " brides and grooms{$from} looking to marry again.",
            ],
            'muslim-matrimony' => [
                [self::with($n, 'Muslim') . ' Matrimony – Brides & Grooms', self::with($n, 'Muslim') . ' Matrimony'],
                self::with($n, 'Muslim') . ' Matrimony',
                'Find ' . self::with($n, 'Muslim') . " brides and grooms{$from}.",
            ],
            'jain-matrimony' => [
                [self::with($n, 'Jain') . ' Matrimony – Brides & Grooms', self::with($n, 'Jain') . ' Matrimony'],
                self::with($n, 'Jain') . ' Matrimony',
                'Find ' . self::with($n, 'Jain') . " brides and grooms{$from}.",
            ],
            'kannadiga-matrimony' => [
                ['Kannadiga Matrimony – Kannada Brides & Grooms', 'Kannadiga Matrimony'],
                'Kannadiga Matrimony',
                "Find Kannada-speaking brides and grooms{$from}.",
            ],
            default => [
                ["{$n} Matrimony – Brides & Grooms", "{$n} Matrimony"],
                "{$n} Matrimony",
                "Find {$n} brides and grooms{$from}.",
            ],
        };

        return [
            'title' => self::fit([...$titles, $heading]),
            'heading' => $heading,
            'intro' => $intro,
            'description' => self::describe($intro, 'View profiles with photos, shortlist and send interest.'),
        ];
    }

    /**
     * A category page that lists its communities / places / jobs.
     *
     * @return array{title: string, heading: string, intro: string, description: string}
     */
    public static function forCategory(string $category, array $config): array
    {
        $label = $config['label'] ?? self::name($category);
        $by = $config['browse_by'] ?? null;
        $region = self::region();
        $from = $region !== '' ? " from {$region}" : '';

        $intro = $by
            ? "Browse brides and grooms{$from} by {$by}."
            : "Browse brides and grooms{$from}.";

        return [
            'title' => self::fit(array_filter([$config['seo_title'] ?? null, "{$label} – Brides & Grooms", $label])),
            'heading' => $label,
            'intro' => $intro,
            'description' => self::describe($intro, 'Pick one to see matching profiles with photos.'),
        ];
    }

    /** The Discover hub. */
    public static function forHub(): array
    {
        $region = self::region();
        $from = $region !== '' ? " from {$region}" : '';
        $intro = "Browse brides and grooms{$from} by community, religion, place, profession and more.";

        return [
            'title' => 'Browse Brides & Grooms by Community & Place',
            'heading' => 'Discover Profiles',
            'intro' => $intro,
            'description' => self::describe($intro, 'Free to register.'),
        ];
    }

    /** "Udupi" + Diocese -> "Udupi Diocese"; "CSI Christian" stays as it is. */
    private static function with(string $name, string $word, bool $add = true): string
    {
        if (! $add || stripos($name, $word) !== false) {
            return $name;
        }

        return "{$name} {$word}";
    }

    /** The first title that fits Google's width; failing that, the shortest one. */
    private static function fit(array $titles): string
    {
        foreach ($titles as $title) {
            if (mb_strlen($title) <= self::TITLE_BUDGET) {
                return $title;
            }
        }

        return collect($titles)->sortBy(fn ($t) => mb_strlen($t))->first();
    }

    /** Intro + site name + the call to action, dropping the extras that don't fit. */
    private static function describe(string $intro, string $more): string
    {
        $site = SiteSetting::getValue('site_name', 'Matrimony');
        $parts = [rtrim($intro, '.') . " on {$site}.", $more, 'Register free.'];

        $text = array_shift($parts);
        foreach ($parts as $part) {
            if (mb_strlen("{$text} {$part}") <= self::DESCRIPTION_BUDGET) {
                $text .= " {$part}";
            }
        }

        return $text;
    }
}
