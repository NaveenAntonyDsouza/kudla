<?php

namespace App\Http\Controllers;

use App\Models\ProfileView;

class ProfileViewController extends Controller
{
    public function index()
    {
        $profile = auth()->user()->profile;
        $tab = request('tab', 'viewed_by');
        $isPremium = auth()->user()->isPremium();

        if ($tab === 'viewed_by') {
            // Count is always available (even for free users)
            $viewedByCount = ProfileView::where('viewed_profile_id', $profile->id)->count();

            if ($isPremium) {
                // Premium: show full profile list
                $views = ProfileView::where('viewed_profile_id', $profile->id)
                    ->with(['viewerProfile.primaryPhoto', 'viewerProfile.religiousInfo', 'viewerProfile.educationDetail', 'viewerProfile.locationInfo'])
                    ->orderBy('viewed_at', 'desc')
                    ->paginate(20);
            } else {
                // Free: empty paginator — view will show count + upgrade CTA
                $views = ProfileView::where('viewed_profile_id', $profile->id)
                    ->whereRaw('1 = 0') // empty result
                    ->paginate(20);
            }
        } else {
            $viewedByCount = 0;
            $views = ProfileView::where('viewer_profile_id', $profile->id)
                ->with(['viewedProfile.primaryPhoto', 'viewedProfile.religiousInfo', 'viewedProfile.educationDetail', 'viewedProfile.locationInfo'])
                ->orderBy('viewed_at', 'desc')
                ->paginate(20);
        }

        return view('views.index', compact('views', 'tab', 'isPremium', 'viewedByCount'));
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
