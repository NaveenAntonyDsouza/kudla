<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\ProfileReport;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Show the report form for a profile.
     */
    public function create(Profile $profile)
    {
        $myProfile = auth()->user()->profile;

        if ($profile->id === $myProfile->id) {
            return back()->with('error', 'You cannot report your own profile.');
        }

        // Check if already reported and pending
        $existing = ProfileReport::where('reporter_profile_id', $myProfile->id)
            ->where('reported_profile_id', $profile->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return back()->with('info', 'You have already reported this profile. Our team is reviewing it.');
        }

        $reasons = ProfileReport::reasons();

        return view('report.create', compact('profile', 'reasons'));
    }

    /**
     * Store the report.
     */
    public function store(Request $request, Profile $profile)
    {
        $myProfile = auth()->user()->profile;

        if ($profile->id === $myProfile->id) {
            return back()->with('error', 'You cannot report your own profile.');
        }

        $request->validate([
            'reason' => 'required|string|in:' . implode(',', array_keys(ProfileReport::reasons())),
            'description' => 'nullable|string|max:1000',
        ]);

        // Prevent duplicate pending reports
        $existing = ProfileReport::where('reporter_profile_id', $myProfile->id)
            ->where('reported_profile_id', $profile->id)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return redirect()->route('profile.view', $profile)
                ->with('info', 'You have already reported this profile.');
        }

        ProfileReport::create([
            'reporter_profile_id' => $myProfile->id,
            'reported_profile_id' => $profile->id,
            'reason' => $request->reason,
            'description' => $request->description,
        ]);

        return redirect()->route('profile.view', $profile)
            ->with('success', 'Report submitted. Our team will review it shortly.');
    }
}
