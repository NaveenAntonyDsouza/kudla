<?php

namespace App\Http\Controllers;

use App\Models\BlockedProfile;
use App\Models\Profile;

class BlockController extends Controller
{
    /**
     * Block a profile.
     */
    public function block(Profile $profile)
    {
        $myProfile = auth()->user()->profile;

        if (! $myProfile || $myProfile->id === $profile->id) {
            return back()->withErrors(['block' => 'Cannot block yourself.']);
        }

        if ($myProfile->gender === $profile->gender) {
            return back()->withErrors(['block' => 'Cannot perform this action.']);
        }

        BlockedProfile::firstOrCreate([
            'profile_id' => $myProfile->id,
            'blocked_profile_id' => $profile->id,
        ]);

        return redirect()->route('dashboard')->with('success', 'Profile blocked. You will no longer see this profile.');
    }

    /**
     * Unblock a profile.
     */
    public function unblock(Profile $profile)
    {
        $myProfile = auth()->user()->profile;

        BlockedProfile::where('profile_id', $myProfile->id)
            ->where('blocked_profile_id', $profile->id)
            ->delete();

        return back()->with('success', 'Profile unblocked.');
    }

    /**
     * View blocked profiles.
     */
    public function index()
    {
        $blocked = BlockedProfile::where('profile_id', auth()->user()->profile->id)
            ->with(['blockedProfile.primaryPhoto'])
            ->latest()
            ->paginate(20);

        return view('blocked.index', compact('blocked'));
    }
}
