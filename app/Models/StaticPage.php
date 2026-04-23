<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class StaticPage extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'content',
        'meta_title',
        'meta_description',
        'is_active',
        'is_system',
        'sort_order',
        'show_in_footer',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'show_in_footer' => 'boolean',
    ];

    /**
     * Get a page by slug (cached for 1 hour).
     */
    public static function getBySlug(string $slug): ?self
    {
        return Cache::remember("static_page.{$slug}", 3600, function () use ($slug) {
            return static::where('slug', $slug)->where('is_active', true)->first();
        });
    }

    /**
     * Get all active footer pages (cached).
     */
    public static function getFooterPages(): array
    {
        return Cache::remember('static_pages.footer', 3600, function () {
            return static::where('is_active', true)
                ->where('show_in_footer', true)
                ->orderBy('sort_order')
                ->get(['slug', 'title'])
                ->toArray();
        });
    }

    /**
     * Clear cache when page is saved.
     */
    public static function clearCache(?string $slug = null): void
    {
        if ($slug) {
            Cache::forget("static_page.{$slug}");
        }
        Cache::forget('static_pages.footer');
    }

    /**
     * Render content with variable substitutions.
     * Replaces {{ app_name }}, {{ email }}, {{ phone }}, {{ current_year }}, etc.
     */
    public function getRenderedContentAttribute(): string
    {
        $content = $this->content;

        $variables = [
            '{{ app_name }}' => config('app.name'),
            '{{ email }}' => SiteSetting::getValue('email', ''),
            '{{ phone }}' => SiteSetting::getValue('phone', ''),
            '{{ whatsapp }}' => SiteSetting::getValue('whatsapp', ''),
            '{{ address }}' => SiteSetting::getValue('address', ''),
            '{{ current_year }}' => date('Y'),
            '{{ current_month_year }}' => date('F Y'),
            '{{app_name}}' => config('app.name'),
            '{{email}}' => SiteSetting::getValue('email', ''),
            '{{phone}}' => SiteSetting::getValue('phone', ''),
        ];

        return str_replace(array_keys($variables), array_values($variables), $content);
    }
}
