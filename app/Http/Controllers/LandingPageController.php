<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\StaticPage;

/**
 * Region / tradition pages that families search for, e.g.
 * /mangalorean-catholic-matrimony (Oct 2026, catholicmatrimony.in SEO).
 * Written and edited in admin → Static Pages: any active page whose slug ends
 * in "-matrimony" is served at the site root, listed in the sitemap and
 * linked from the footer. Sites without such pages are unaffected.
 */
class LandingPageController extends Controller
{
    public function show(string $slug)
    {
        $page = StaticPage::getBySlug($slug);
        if (! $page || ! StaticPage::isLanding($slug)) {
            abort(404);
        }

        $others = collect(StaticPage::getLandingPages())->reject(fn ($p) => $p['slug'] === $slug)->values();
        $articles = BlogPost::hasPublishedPosts()
            ? BlogPost::published()->orderByDesc('published_at')->limit(3)->get()
            : collect();

        return view('pages.landing', compact('page', 'others', 'articles'));
    }
}
