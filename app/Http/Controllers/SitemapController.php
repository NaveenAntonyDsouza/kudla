<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Models\StaticPage;
use App\Models\Testimonial;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        if (SiteSetting::getValue('sitemap_enabled', '1') !== '1') {
            abort(404);
        }

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
            ['url' => url('/demograph'), 'priority' => '0.5', 'changefreq' => 'monthly'],
        ];
        $urls = $urls->merge($staticPages);

        // Legal pages actually published on THIS site — a site that never
        // installed one (or switched it off) must not advertise it to Google.
        $legal = StaticPage::query()
            ->where('is_active', true)
            ->whereIn('slug', ['privacy-policy', 'terms-condition', 'refund-policy', 'child-safety', 'report-misuse'])
            ->orderBy('sort_order')
            ->pluck('slug')
            ->map(fn ($slug) => ['url' => url("/{$slug}"), 'priority' => '0.3', 'changefreq' => 'yearly']);
        $urls = $urls->merge($legal);

        // Search pages — route() so a renamed route can't leave a dead link
        // here (/search/advanced-search and /search/search-by-id were 404s).
        $searchPages = [
            ['url' => route('search.quick'), 'priority' => '0.8', 'changefreq' => 'daily'],
            ['url' => route('search.advance'), 'priority' => '0.7', 'changefreq' => 'daily'],
            ['url' => route('search.keyword'), 'priority' => '0.6', 'changefreq' => 'daily'],
            ['url' => route('search.byid'), 'priority' => '0.5', 'changefreq' => 'daily'],
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

    /**
     * robots.txt — served per site so the `Sitemap:` line is an ABSOLUTE URL
     * (crawlers ignore a relative one, and the old static public/robots.txt
     * said just "/sitemap.xml" on all six sites). Admins can edit the body in
     * SEO Settings; the Sitemap line is always appended from this host.
     */
    public function robots(): Response
    {
        $body = trim((string) SiteSetting::getValue('robots_txt', ''));
        if ($body === '') {
            $body = implode("\n", [
                'User-agent: *',
                'Disallow: /admin',
                'Disallow: /dashboard',
                'Disallow: /settings',
                'Disallow: /interests',
                'Disallow: /shortlist',
                'Disallow: /views',
                'Disallow: /photo-requests',
                'Disallow: /saved-searches',
                'Disallow: /submit-id-proof',
                'Disallow: /onboarding',
                'Disallow: /register/step*',
                'Disallow: /membership-plans/checkout',
                'Allow: /',
            ]);
        }

        // Drop any Sitemap: lines the admin typed, then add the correct one.
        $body = trim(preg_replace('/^\s*Sitemap:.*$/mi', '', $body));
        if (SiteSetting::getValue('sitemap_enabled', '1') === '1') {
            $body .= "\n\nSitemap: " . route('sitemap');
        }

        return response($body . "\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
