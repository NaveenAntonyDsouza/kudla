<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Services\MatchingService;
use App\Services\ProfileCompletionService;
use App\Traits\ProfileQueryFilters;

class DashboardController extends Controller
{
    use ProfileQueryFilters;

    /**
     * Maps ProfileCompletionService section keys to the web edit URL that opens
     * that section. The profile-edit page (profile.show) accepts ?section=KEY to
     * auto-open the matching accordion; photos have their own manage page.
     */
    private const SECTION_WEB_KEY = [
        'basic_info' => 'primary',
        'religious' => 'religious',
        'education' => 'education',
        'family_details' => 'family',
        'location' => 'location',
        'contact' => 'contact',
        'lifestyle' => 'hobbies',
        'partner_preferences' => 'partner',
    ];

    public function __construct(
        private MatchingService $matchingService,
        private ProfileCompletionService $completion,
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

        // Section checklist — driven by ProfileCompletionService so the web
        // dashboard uses the same accurate field-level "done" detection as the
        // % bar and the mobile app (no more shallow row-exists checks). Each
        // section deep-links straight to its edit form, and incomplete sections
        // bubble to the top so the most valuable gap is the first thing seen.
        $sections = collect($this->completion->getSectionStatuses($profile))
            ->map(fn (array $s) => [
                'label' => $s['label'],
                'done' => $s['done'],
                'url' => $s['key'] === 'photo'
                    ? route('photos.manage')
                    : route('profile.show', ['section' => self::SECTION_WEB_KEY[$s['key']]]),
            ])
            ->sortBy('done') // incomplete (false) first, complete (true) last
            ->values()
            ->all();

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
