<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = collect();

        // Static pages
        $staticPages = [
            ['url' => url('/'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['url' => url('/register'), 'priority' => '0.9', 'changefreq' => 'monthly'],
            ['url' => url('/login'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => url('/membership-plans'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['url' => url('/about-us'), 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['url' => url('/faq'), 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['url' => url('/contact-us'), 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['url' => url('/success-stories'), 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['url' => url('/privacy-policy'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['url' => url('/terms-condition'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['url' => url('/refund-policy'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['url' => url('/child-safety'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['url' => url('/demograph'), 'priority' => '0.5', 'changefreq' => 'monthly'],
        ];
        $urls = $urls->merge($staticPages);

        // Search pages
        $searchPages = [
            ['url' => url('/search/quick-search'), 'priority' => '0.8', 'changefreq' => 'daily'],
            ['url' => url('/search/advanced-search'), 'priority' => '0.7', 'changefreq' => 'daily'],
            ['url' => url('/search/keyword-search'), 'priority' => '0.6', 'changefreq' => 'daily'],
            ['url' => url('/search/search-by-id'), 'priority' => '0.5', 'changefreq' => 'daily'],
        ];
        $urls = $urls->merge($searchPages);

        // Discover pages
        $discoverCategories = config('discover', []);
        foreach ($discoverCategories as $slug => $category) {
            $urls->push(['url' => url("/discover/{$slug}"), 'priority' => '0.7', 'changefreq' => 'weekly']);
        }

        // Build XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $entry) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$entry['url']}</loc>\n";
            $xml .= "    <changefreq>{$entry['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$entry['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
