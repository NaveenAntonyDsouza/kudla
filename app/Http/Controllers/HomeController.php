<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\Faq;
use App\Models\Profile;
use App\Models\SiteSetting;

class HomeController extends Controller
{
    public function index()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        $communities = Community::active()
            ->orderBy('religion')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('religion');

        $stats = [
            'members' => SiteSetting::getValue('total_members', '0'),
            'marriages' => SiteSetting::getValue('successful_marriages', '0'),
            'years' => SiteSetting::getValue('years_of_service', '1'),
        ];

        // Get recent active profiles for the featured section
        $featuredProfiles = Profile::where('is_active', true)
            ->approved()
            ->where(fn($q) => $q->where('is_hidden', false)->orWhereNull('is_hidden'))
            ->whereNotNull('full_name')
            ->with(['primaryPhoto', 'religiousInfo', 'educationDetail', 'locationInfo'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $totalProfiles = Profile::where('is_active', true)->count();

        $faqs = Faq::visible()->orderBy('display_order')->limit(4)->get();

        // SEO: Homepage-specific title and description
        $siteName = SiteSetting::getValue('site_name', 'Matrimony');
        $siteTagline = SiteSetting::getValue('tagline', 'Find Your Perfect Match');
        $siteTitle = "{$siteName} - {$siteTagline} | Free Registration";
        $siteMetaDesc = "{$siteName} - Trusted matrimony service. Register free, browse verified profiles, and find your perfect life partner. {$stats['members']}+ members, {$stats['marriages']}+ successful marriages.";

        return view('pages.home', compact('communities', 'stats', 'featuredProfiles', 'totalProfiles', 'faqs', 'siteTitle', 'siteMetaDesc'));
    }
}
