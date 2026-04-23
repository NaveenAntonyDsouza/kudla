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

        // Stats: Total Members respects the admin's auto-compute toggle.
        // When ON, show live DB count of active + approved members. When OFF, show manual value.
        $autoComputeMembers = SiteSetting::getValue('stats_auto_compute', '0') === '1';
        $members = $autoComputeMembers
            ? Profile::where('is_active', true)
                ->approved()
                ->whereHas('user', fn($q) => $q->whereNull('staff_role_id'))
                ->count()
            : (int) SiteSetting::getValue('total_members', '0');

        $stats = [
            'members' => $members,
            'marriages' => SiteSetting::getValue('successful_marriages', '0'),
            'years' => SiteSetting::getValue('years_of_service', '1'),
        ];

        // Get VIP/Featured profiles first, then recent active profiles to fill up to 8
        $featuredProfiles = Profile::where('is_active', true)
            ->approved()
            ->where(fn($q) => $q->where('is_hidden', false)->orWhereNull('is_hidden'))
            ->whereNotNull('full_name')
            ->with(['primaryPhoto', 'religiousInfo', 'educationDetail', 'locationInfo'])
            ->orderBy('is_vip', 'desc')
            ->orderBy('is_featured', 'desc')
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

        // Pick the homepage template: classic (default) | modern | premium.
        // Admin sets this from Settings -> Homepage Content -> Homepage Design.
        $template = SiteSetting::getValue('homepage_template', 'classic');
        $template = in_array($template, ['classic', 'modern', 'premium'], true) ? $template : 'classic';

        return view("pages.home.{$template}", compact('communities', 'stats', 'featuredProfiles', 'totalProfiles', 'faqs', 'siteTitle', 'siteMetaDesc'));
    }
}
