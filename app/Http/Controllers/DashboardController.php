<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $profile = $user->profile;
        $completionPct = $profile->calculateCompletion();

        // Update stored completion %
        if ($profile->profile_completion_pct !== $completionPct) {
            $profile->update(['profile_completion_pct' => $completionPct]);
        }

        // Sections status for quick actions
        $sections = [
            ['label' => 'Primary Information', 'done' => (bool) ($profile->full_name && $profile->gender), 'route' => 'onboarding.step1'],
            ['label' => 'Religious Information', 'done' => $profile->religiousInfo()->exists(), 'route' => 'register.step2'],
            ['label' => 'Education & Profession', 'done' => $profile->educationDetail()->exists(), 'route' => 'onboarding.step1'],
            ['label' => 'Family Information', 'done' => $profile->familyDetail()->exists(), 'route' => 'onboarding.step1'],
            ['label' => 'Location & Contact', 'done' => $profile->locationInfo?->residing_country || $profile->locationInfo?->native_country, 'route' => 'onboarding.step2'],
            ['label' => 'Additional Contact', 'done' => $profile->contactInfo()->exists(), 'route' => 'onboarding.step2'],
            ['label' => 'Partner Preferences', 'done' => $profile->partnerPreference()->exists(), 'route' => 'onboarding.preferences'],
            ['label' => 'Lifestyle & Interests', 'done' => $profile->lifestyleInfo()->exists(), 'route' => 'onboarding.lifestyle'],
            ['label' => 'Photo Uploaded', 'done' => $profile->profilePhotos()->visible()->exists(), 'route' => 'photos.manage'],
        ];

        // Recently joined profiles (exclude self)
        $recentProfiles = Profile::where('id', '!=', $profile->id)
            ->whereNotNull('full_name')
            ->where('is_active', true)
            ->with(['locationInfo', 'primaryPhoto', 'educationDetail', 'religiousInfo'])
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        // Interest counts for stats
        $interestStats = [
            'sent' => $profile->sentInterests()->count(),
            'accepted' => $profile->sentInterests()->where('status', 'accepted')->count(),
            'received' => $profile->receivedInterests()->where('status', 'pending')->count(),
        ];

        return view('dashboard.index', compact('profile', 'user', 'completionPct', 'sections', 'recentProfiles', 'interestStats'));
    }

}
