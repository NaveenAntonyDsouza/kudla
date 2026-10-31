<?php

namespace App\Support;

use App\Models\Profile;
use Illuminate\Support\Facades\Cache;

/**
 * "NRI" means an Indian living abroad (Oct 2026). Global Catholic Match's
 * members live in the USA, Canada, the UK and elsewhere, so on a site where
 * most live profiles are outside India the NRI pages go by country instead:
 * "Country", "Brides & Grooms in USA", with India listed like any other
 * country. Kudla and the India sites keep "NRI".
 */
class NriWording
{
    public const CATEGORY = 'nri-matrimony';

    /** Most of the site's live profiles live outside India. */
    public static function byCountry(): bool
    {
        try {
            // counted here, not from the category's country list: on a "Country" site that list includes India
            ['live' => $live, 'abroad' => $abroad] = Cache::remember('discover.counts.live-abroad', 3600, function () {
                // the same profiles the Discover counts are taken from
                $live = fn () => Profile::where('is_active', true)->approved()
                    ->where(fn ($q) => $q->where('is_hidden', false)->orWhereNull('is_hidden'));

                return [
                    'live' => $live()->count(),
                    'abroad' => $live()->whereHas('locationInfo', fn ($q) => $q->whereNotNull('residing_country')
                        ->where('residing_country', '!=', '')->where('residing_country', '!=', 'India'))->count(),
                ];
            });
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        return $abroad > 0 && $abroad * 2 > $live;
    }

    /** A Discover category's config, in this site's words. */
    public static function config(string $category): ?array
    {
        $config = config("discover.{$category}");
        if (! $config || $category !== self::CATEGORY || ! self::byCountry()) {
            return $config;
        }

        return array_merge($config, [
            'label' => 'Matrimony by Country',
            'hub_label' => 'Country',
            'seo_title' => 'Brides & Grooms by Country',
        ]);
    }

    /** Every Discover category's config, in this site's words. */
    public static function all(): array
    {
        $all = config('discover');
        if (! is_array($all)) {
            return [];
        }
        if (isset($all[self::CATEGORY])) {
            $all[self::CATEGORY] = self::config(self::CATEGORY);
        }

        return $all;
    }
}
