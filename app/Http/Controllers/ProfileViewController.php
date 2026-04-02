<?php

namespace App\Http\Controllers;

use App\Models\ProfileView;

class ProfileViewController extends Controller
{
    public function index()
    {
        $profile = auth()->user()->profile;
        $tab = request('tab', 'viewed_by');

        if ($tab === 'viewed_by') {
            $views = ProfileView::where('viewed_profile_id', $profile->id)
                ->with(['viewerProfile.primaryPhoto', 'viewerProfile.religiousInfo', 'viewerProfile.educationDetail', 'viewerProfile.locationInfo'])
                ->orderBy('viewed_at', 'desc')
                ->paginate(20);
        } else {
            $views = ProfileView::where('viewer_profile_id', $profile->id)
                ->with(['viewedProfile.primaryPhoto', 'viewedProfile.religiousInfo', 'viewedProfile.educationDetail', 'viewedProfile.locationInfo'])
                ->orderBy('viewed_at', 'desc')
                ->paginate(20);
        }

        return view('views.index', compact('views', 'tab'));
    }

    /**
     * Track a profile view (called from ProfileController::viewProfile).
     */
    public static function track(int $viewerProfileId, int $viewedProfileId): void
    {
        if ($viewerProfileId === $viewedProfileId) return;

        // Only record one view per viewer per day
        $exists = ProfileView::where('viewer_profile_id', $viewerProfileId)
            ->where('viewed_profile_id', $viewedProfileId)
            ->where('viewed_at', '>=', now()->startOfDay())
            ->exists();

        if (! $exists) {
            ProfileView::create([
                'viewer_profile_id' => $viewerProfileId,
                'viewed_profile_id' => $viewedProfileId,
                'viewed_at' => now(),
            ]);
        }
    }
}
