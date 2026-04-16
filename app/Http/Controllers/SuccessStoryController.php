<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SuccessStoryController extends Controller
{
    /**
     * Display all visible success stories (public page).
     */
    public function index()
    {
        $stories = Testimonial::where('is_visible', true)
            ->orderBy('display_order')
            ->orderByDesc('wedding_date')
            ->paginate(12);

        return view('success-stories.index', compact('stories'));
    }

    /**
     * Show the submission form (requires login).
     */
    public function create()
    {
        return view('success-stories.create');
    }

    /**
     * Store a user-submitted success story (pending admin approval).
     */
    public function store(Request $request)
    {
        $request->validate([
            'couple_names' => 'required|string|max:150',
            'location' => 'nullable|string|max:100',
            'wedding_date' => 'nullable|date|before_or_equal:today',
            'story' => 'required|string|min:20|max:2000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
        ]);

        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $photoUrl = $request->file('photo')->store('success-stories', 'public');
        }

        Testimonial::create([
            'couple_names' => $request->couple_names,
            'story' => $request->story,
            'photo_url' => $photoUrl,
            'wedding_date' => $request->wedding_date,
            'location' => $request->location,
            'submitted_by_user_id' => auth()->id(),
            'is_visible' => false, // Admin must approve
            'display_order' => 0,
        ]);

        return redirect()->route('success-stories.index')
            ->with('success', 'Thank you for sharing your success story! It will be visible after admin approval.');
    }
}
