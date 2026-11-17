<?php

namespace App\Http\Controllers;

use App\Services\MatchingService;
use App\Support\SuggestedPreferences;

class MatchController extends Controller
{
    public function __construct(
        private MatchingService $matchingService,
    ) {}

    public function index()
    {
        $profile = auth()->user()->profile;
        if (!$profile) {
            return redirect()->route('register.step1');
        }
        // saved preferences, or (switch on) ones suggested from the member's own profile
        $prefs = SuggestedPreferences::for($profile);
        $hasPreferences = $prefs !== null;
        $suggested = SuggestedPreferences::isSuggested($prefs);
        $matches = $hasPreferences
            ? $this->matchingService->getMatches($profile)
            : null;

        return view('matches.index', compact('profile', 'matches', 'hasPreferences', 'suggested'));
    }

    public function mutual()
    {
        $profile = auth()->user()->profile;
        if (!$profile) {
            return redirect()->route('register.step1');
        }
        $prefs = SuggestedPreferences::for($profile);
        $hasPreferences = $prefs !== null;
        $suggested = SuggestedPreferences::isSuggested($prefs);
        $matches = $hasPreferences
            ? $this->matchingService->getMutualMatches($profile)
            : null;

        return view('matches.mutual', compact('profile', 'matches', 'hasPreferences', 'suggested'));
    }
}
