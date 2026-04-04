<?php

namespace App\Http\Controllers;

use App\Services\MatchingService;

class MatchController extends Controller
{
    public function __construct(
        private MatchingService $matchingService,
    ) {}

    public function index()
    {
        $profile = auth()->user()->profile;
        $hasPreferences = $profile->partnerPreference()->exists();
        $matches = $hasPreferences
            ? $this->matchingService->getMatches($profile)
            : null;

        return view('matches.index', compact('profile', 'matches', 'hasPreferences'));
    }

    public function mutual()
    {
        $profile = auth()->user()->profile;
        $hasPreferences = $profile->partnerPreference()->exists();
        $matches = $hasPreferences
            ? $this->matchingService->getMutualMatches($profile)
            : null;

        return view('matches.mutual', compact('profile', 'matches', 'hasPreferences'));
    }
}
