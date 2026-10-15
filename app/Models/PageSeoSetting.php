<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PageSeoSetting extends Model
{
    protected $fillable = [
        'page_slug',
        'page_label',
        'meta_title',
        'meta_description',
        'og_image_url',
        'canonical_url',
    ];

    /**
     * Get SEO settings for a specific page.
     * Returns null if no custom SEO is set for this page.
     */
    public static function getForPage(string $pageSlug): ?self
    {
        // Cache plain attributes, not the model: the cache refuses to unserialize
        // objects (config cache.serializable_classes = false), which turned a
        // cached model into __PHP_Incomplete_Class and took every page down (14 Oct 2026).
        $attributes = Cache::remember("page_seo_v2.{$pageSlug}", 3600, function () use ($pageSlug) {
            return static::where('page_slug', $pageSlug)->first()?->getAttributes() ?? [];
        });

        return is_array($attributes) && $attributes !== [] ? (new static)->newFromBuilder($attributes) : null;
    }

    /**
     * Clear cache for a specific page or all pages.
     */
    public static function clearCache(?string $pageSlug = null): void
    {
        if ($pageSlug) {
            Cache::forget("page_seo_v2.{$pageSlug}");
        } else {
            // Pages just reset to blank are no longer in the table, so clear
            // every known page as well as the saved ones.
            $slugs = static::pluck('page_slug')->merge(array_keys(static::defaultPages()))->unique();
            foreach ($slugs as $slug) {
                Cache::forget("page_seo_v2.{$slug}");
            }
        }
    }

    /**
     * Default pages available for SEO customization.
     */
    public static function defaultPages(): array
    {
        return [
            'home' => 'Home Page',
            'search' => 'Search / Results',
            'login' => 'Login',
            'register' => 'Register',
            'happy-stories' => 'Happy Stories',
            'privacy-policy' => 'Privacy Policy',
            'terms' => 'Terms & Conditions',
            'about' => 'About Us',
            'contact' => 'Contact Us',
            'membership-plans' => 'Membership Plans',
            'faq' => 'Help & FAQ',
            'demograph' => 'Member Demographics',
        ];
    }
}
