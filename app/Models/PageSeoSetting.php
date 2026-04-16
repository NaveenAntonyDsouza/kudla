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
        return Cache::remember("page_seo.{$pageSlug}", 3600, function () use ($pageSlug) {
            return static::where('page_slug', $pageSlug)->first();
        });
    }

    /**
     * Clear cache for a specific page or all pages.
     */
    public static function clearCache(?string $pageSlug = null): void
    {
        if ($pageSlug) {
            Cache::forget("page_seo.{$pageSlug}");
        } else {
            $slugs = static::pluck('page_slug');
            foreach ($slugs as $slug) {
                Cache::forget("page_seo.{$slug}");
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
        ];
    }
}
