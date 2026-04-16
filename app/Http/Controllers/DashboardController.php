<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Services\MatchingService;
use App\Traits\ProfileQueryFilters;

class DashboardController extends Controller
{
    use ProfileQueryFilters;

    public function __construct(
        private MatchingService $matchingService,
    ) {}

    public function index()
    {
        $user = auth()->user();
        $profile = $user->profile;

        // Admin users without profiles → redirect to admin panel
        if (!$profile) {
            return redirect('/admin');
        }

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

        // Recommended matches (top 6 by score)
        $recommendedMatches = collect();
        if ($profile->partnerPreference) {
            $recommendedMatches = $this->matchingService->getRecommendations($profile, 6);
        }

        // Mutual matches (top 4)
        $mutualMatches = collect();
        if ($profile->partnerPreference) {
            $mutualPaginator = $this->matchingService->getMutualMatches($profile, 4);
            $mutualMatches = collect($mutualPaginator->items());
        }

        // Recent profile views (who viewed me — last 6)
        $isPremium = $user->isPremium();
        $viewCount = $profile->viewedByOthers()->count();

        if ($isPremium) {
            $recentViews = $profile->viewedByOthers()
                ->with(['viewerProfile' => fn($q) => $q->with(['primaryPhoto', 'religiousInfo', 'educationDetail', 'locationInfo'])])
                ->orderByDesc('viewed_at')
                ->limit(6)
                ->get()
                ->pluck('viewerProfile')
                ->filter();
        } else {
            $recentViews = collect(); // Free users: show count only
        }

        // Newly joined profiles (always show — latest 6 opposite gender)
        $newlyJoined = $this->baseQuery($profile)
            ->whereNotNull('full_name')
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        // Discover categories (first 6 for dashboard widget)
        $discoverCategories = collect(config('discover'))
            ->map(fn($cat, $slug) => ['label' => $cat['label'], 'slug' => $slug])
            ->values()
            ->take(6);

        // Interest counts for stats
        $interestStats = [
            'sent' => $profile->sentInterests()->count(),
            'accepted' => $profile->sentInterests()->where('status', 'accepted')->count(),
            'received' => $profile->receivedInterests()->where('status', 'pending')->count(),
            'views' => $profile->viewedByOthers()->count(),
            'shortlisted' => $profile->shortlists()->count(),
        ];

        return view('dashboard.index', compact(
            'profile', 'user', 'completionPct', 'sections',
            'recommendedMatches', 'mutualMatches', 'recentViews',
            'newlyJoined', 'discoverCategories', 'interestStats',
            'isPremium', 'viewCount'
        ));
    }
}
