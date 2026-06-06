<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Community extends Model
{
    protected $fillable = [
        'religion',
        'community_name',
        'sub_communities',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sub_communities' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByReligion(Builder $query, string $religion): Builder
    {
        return $query->where('religion', $religion);
    }

    /**
     * Get all active community names as a flat array.
     * Used by partner preferences, search filters, etc.
     * Returns: ['Brahmin', 'Nair', 'Bunt', ...]
     */
    public static function getCasteList(?string $religion = null): array
    {
        $cacheKey = 'community_caste_list' . ($religion ? "_{$religion}" : '_all');

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($religion) {
            $query = static::active()->orderBy('sort_order');

            if ($religion) {
                $query->byReligion($religion);
            }

            return $query->pluck('community_name')->toArray();
        });
    }

    /**
     * Get all active sub-communities as a flat array (merged from all communities).
     * Returns: ['Shetty', 'Hegde', 'Rai', ...]
     */
    public static function getSubCasteList(?string $religion = null): array
    {
        $cacheKey = 'community_sub_caste_list' . ($religion ? "_{$religion}" : '_all');

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($religion) {
            $query = static::active()->orderBy('sort_order');

            if ($religion) {
                $query->byReligion($religion);
            }

            return $query->get()
                ->flatMap(fn ($c) => $c->sub_communities ?? [])
                ->unique()
                ->sort()
                ->values()
                ->toArray();
        });
    }

    /**
     * Sub-communities (sub-castes) for ONE community by name, optionally
     * religion-scoped. Drives the cascading Sub-Caste dropdown so it only shows
     * the selected caste's sub-castes (the flat getSubCasteList() is for
     * filters/search where the caste isn't known).
     * Returns e.g. ['Naik', 'Nayak'] for community_name 'Naik / Nayak'.
     */
    public static function getSubCommunitiesFor(?string $communityName, ?string $religion = null): array
    {
        if (! $communityName) {
            return [];
        }
        $query = static::active()->where('community_name', $communityName);
        if ($religion) {
            $query->byReligion($religion);
        }

        return $query->first()?->sub_communities ?? [];
    }

    /**
     * Clear community list caches.
     */
    protected static function booted(): void
    {
        $clearCache = function () {
            \Illuminate\Support\Facades\Cache::forget('community_caste_list_all');
            \Illuminate\Support\Facades\Cache::forget('community_sub_caste_list_all');
            foreach (['Hindu', 'Christian', 'Muslim', 'Jain', 'Other'] as $r) {
                \Illuminate\Support\Facades\Cache::forget("community_caste_list_{$r}");
                \Illuminate\Support\Facades\Cache::forget("community_sub_caste_list_{$r}");
            }
        };

        static::saved(fn () => $clearCache());
        static::deleted(fn () => $clearCache());
    }
}
