<?php

namespace App\Support;

use App\Http\Controllers\DiscoverController;
use App\Models\Profile;
use Illuminate\Support\Facades\Cache;

/**
 * "NRI" means an Indian living abroad (Oct 2026). Global Catholic Match's
 * members live in the USA, Canada, the UK and elsewhere, so on a site where
 * most live profiles are outside India the NRI pages go by country instead:
 * "Country", "Brides & Grooms in USA". Kudla and the India sites keep "NRI".
 */
class NriWording
{
    public const CATEGORY = 'nri-matrimony';

    /** Most of the site's live profiles live outside India. */
    public static function byCountry(): bool
    {
        $abroad = array_sum(DiscoverController::profileCounts(self::CATEGORY));
        if ($abroad === 0) {
            return false;
        }

        try {
            // the same profiles the Discover counts are taken from
            $live = Cache::remember('discover.counts.live', 3600, fn () => Profile::where('is_active', true)->approved()
                ->where(fn ($q) => $q->where('is_hidden', false)->orWhereNull('is_hidden'))
                ->count());
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        return $abroad * 2 > $live;
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
