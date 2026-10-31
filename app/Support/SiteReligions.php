<?php

namespace App\Support;

use App\Models\Community;
use App\Models\ReligiousInfo;
use Illuminate\Support\Facades\Cache;

/**
 * Which religions a site's members follow (Oct 2026): the live profiles', or,
 * on a site with none yet, its community list's. Names the document approvals
 * menu (horoscopes or baptism certificates) and gives the Catholic sites their
 * Dioceses row on the Discover hub.
 */
class SiteReligions
{
    /** @return array{christian: bool, other: bool} */
    public static function mix(): array
    {
        return Cache::remember('site-religions', 3600, function () {
            try {
                $religions = ReligiousInfo::query()
                    ->whereHas('profile') // live profiles only (not deleted)
                    ->whereNotNull('religion')->where('religion', '!=', '')
                    ->distinct()->pluck('religion');
                if ($religions->isEmpty()) {
                    $religions = Community::active()->whereNotNull('religion')->distinct()->pluck('religion');
                }

                return [
                    'christian' => $religions->contains('Christian'),
                    'other' => $religions->contains(fn ($r) => $r !== 'Christian'),
                ];
            } catch (\Throwable) {
                return ['christian' => true, 'other' => true];
            }
        });
    }

    /** Every member is Christian: Crasta, Global Catholic Match, Amchi, catholicmatrimony.in. */
    public static function christianOnly(): bool
    {
        $mix = self::mix();

        return $mix['christian'] && ! $mix['other'];
    }
}
