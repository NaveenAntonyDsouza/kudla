<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    protected $fillable = [
        'title',
        'ad_space',
        'type',
        'image_path',
        'click_url',
        'html_code',
        'advertiser_name',
        'start_date',
        'end_date',
        'is_active',
        'priority',
        'impressions',
        'clicks',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'priority' => 'integer',
            'impressions' => 'integer',
            'clicks' => 'integer',
        ];
    }

    /**
     * Ad space options for forms.
     */
    public static function adSpaces(): array
    {
        return [
            'homepage_banner' => 'Homepage Banner (below hero)',
            'sidebar' => 'Sidebar (right column)',
            'search_results' => 'Search Results (between listings)',
            'footer_banner' => 'Footer Banner (above footer)',
            'mobile_banner' => 'Mobile Banner (between content)',
        ];
    }

    /**
     * Scope: only active ads within their date range.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()));
    }

    /**
     * Get the best ad for a given slot.
     */
    public static function getForSlot(string $slot): ?self
    {
        return static::active()
            ->where('ad_space', $slot)
            ->orderByDesc('priority')
            ->first();
    }

    /**
     * Record an ad impression.
     */
    public function recordImpression(): void
    {
        $this->increment('impressions');
    }

    /**
     * Record an ad click.
     */
    public function recordClick(): void
    {
        $this->increment('clicks');
    }

    /**
     * Get click-through rate.
     */
    public function getCtrAttribute(): string
    {
        if ($this->impressions <= 0) {
            return '0%';
        }

        return round(($this->clicks / $this->impressions) * 100, 2) . '%';
    }
}
