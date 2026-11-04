<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\Shortlist;
use Illuminate\Http\Request;

class ShortlistController extends Controller
{
    /**
     * Toggle shortlist for a profile.
     */
    public function toggle(Profile $profile)
    {
        $myProfile = auth()->user()->profile;

        if (! $myProfile || $myProfile->id === $profile->id) {
            return back()->withErrors(['shortlist' => 'Cannot shortlist yourself.']);
        }

        if ($myProfile->gender === $profile->gender) {
            return back()->withErrors(['shortlist' => 'Cannot shortlist same gender.']);
        }

        $existing = Shortlist::where('profile_id', $myProfile->id)
            ->where('shortlisted_profile_id', $profile->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return back()->with('success', 'Removed from shortlist.');
        }

        // only a profile the member may see (privacy check, 4 Nov 2026); removing always works
        if (! app(\App\Services\ProfileAccessService::class)->canAccess($myProfile, $profile)) {
            return back()->withErrors(['shortlist' => 'This profile is not available.']);
        }

        Shortlist::create([
            'profile_id' => $myProfile->id,
            'shortlisted_profile_id' => $profile->id,
        ]);

        return back()->with('success', 'Added to shortlist!');
    }

    /**
     * View shortlisted profiles.
     */
    public function index()
    {
        $profile = auth()->user()->profile;
        $shortlisted = Shortlist::where('profile_id', $profile->id)
            // only members still visible to this member (4 Nov 2026)
            ->whereHas('shortlistedProfile', fn ($q) => \App\Support\ProfileVisibility::forMember($q, $profile))
            ->with(['shortlistedProfile.primaryPhoto', 'shortlistedProfile.religiousInfo', 'shortlistedProfile.educationDetail', 'shortlistedProfile.locationInfo'])
            ->latest()
            ->paginate(20);

        return view('shortlist.index', compact('shortlisted'));
    }
}
