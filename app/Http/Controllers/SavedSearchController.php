<?php

namespace App\Http\Controllers;

use App\Models\SavedSearch;
use Illuminate\Http\Request;

class SavedSearchController extends Controller
{
    public function index()
    {
        $profile = auth()->user()->profile;
        $savedSearches = SavedSearch::where('profile_id', $profile->id)
            ->orderByDesc('created_at')
            ->get();

        return view('saved-searches.index', compact('savedSearches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'search_name' => 'required|string|max:100',
        ]);

        $profile = auth()->user()->profile;

        // Capture all search params except internal ones
        $criteria = $request->except(['_token', 'search_name', 'search', 'tab']);

        SavedSearch::create([
            'profile_id' => $profile->id,
            'search_name' => $validated['search_name'],
            'criteria' => $criteria,
        ]);

        return back()->with('success', 'Search saved as "' . $validated['search_name'] . '".');
    }

    public function destroy(SavedSearch $savedSearch)
    {
        $profile = auth()->user()->profile;

        if ($savedSearch->profile_id !== $profile->id) {
            abort(403);
        }

        $savedSearch->delete();

        return back()->with('success', 'Saved search deleted.');
    }

    /**
     * Load a saved search — redirects to search with saved criteria as query params.
     */
    public function load(SavedSearch $savedSearch)
    {
        $profile = auth()->user()->profile;

        if ($savedSearch->profile_id !== $profile->id) {
            abort(403);
        }

        $params = array_merge($savedSearch->criteria ?? [], ['search' => 1, 'tab' => 'partner']);

        return redirect()->route('search.index', $params);
    }
}
