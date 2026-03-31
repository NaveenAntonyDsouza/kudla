<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\SiteSetting;

class HomeController extends Controller
{
    public function index()
    {
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

        return view('pages.home', compact('communities', 'stats'));
    }
}
