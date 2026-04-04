<?php

namespace App\Http\Controllers;

use App\Models\IgnoredProfile;
use App\Models\Profile;

class IgnoredProfileController extends Controller
{
    public function index()
    {
        $profile = auth()->user()->profile;

        $ignored = IgnoredProfile::where('profile_id', $profile->id)
            ->with(['ignoredProfile' => fn($q) => $q->with(['primaryPhoto', 'religiousInfo', 'educationDetail', 'locationInfo'])])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('ignored.index', compact('ignored'));
    }

    public function toggle(Profile $profile)
    {
        $myProfile = auth()->user()->profile;

        $existing = IgnoredProfile::where('profile_id', $myProfile->id)
            ->where('ignored_profile_id', $profile->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return back()->with('success', 'Profile removed from ignored list.');
        }

        IgnoredProfile::create([
            'profile_id' => $myProfile->id,
            'ignored_profile_id' => $profile->id,
        ]);

        return back()->with('success', 'Profile ignored. It will not appear in your search results.');
    }
}
