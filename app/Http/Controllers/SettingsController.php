<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $profile = $user->profile;
        $section = $request->get('section', 'profile_filters');
        $prefs = $user->notification_preferences ?? $this->defaultPrefs();

        return view('settings.index', compact('user', 'profile', 'section', 'prefs'));
    }

    public function updateFilters(Request $request)
    {
        $validated = $request->validate([
            'show_profile_to' => 'required|in:all,premium,matches',
        ]);

        auth()->user()->profile->update($validated);
        return back()->with('success', 'Profile filters updated.');
    }

    public function updateAlerts(Request $request)
    {
        $prefs = [
            'email_interest' => $request->boolean('email_interest'),
            'email_accepted' => $request->boolean('email_accepted'),
            'email_declined' => $request->boolean('email_declined'),
            'email_views' => $request->boolean('email_views'),
            'email_promotions' => $request->boolean('email_promotions'),
        ];

        auth()->user()->update(['notification_preferences' => $prefs]);
        return back()->with('success', 'Notification preferences updated.');
    }

    public function updateVisibility(Request $request)
    {
        auth()->user()->profile->update([
            'only_same_religion' => $request->boolean('only_same_religion'),
            'only_same_denomination' => $request->boolean('only_same_denomination'),
            'only_same_mother_tongue' => $request->boolean('only_same_mother_tongue'),
        ]);
        return redirect()->route('settings.index', ['section' => 'search_visibility'])->with('success', 'Profile visibility updated.');
    }

    public function hideProfile(Request $request)
    {
        $profile = auth()->user()->profile;
        $newState = ! $profile->is_hidden;
        $profile->update(['is_hidden' => $newState]);

        $msg = $newState ? 'Profile hidden from search.' : 'Profile is now visible in search.';
        return redirect()->route('settings.index', ['section' => 'hide_profile'])->with('success', $msg);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|max:14|confirmed',
        ]);

        $user = auth()->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return back()->with('success', 'Password changed successfully.');
    }

    public function deleteProfile(Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:200',
        ]);

        $user = auth()->user();
        $profile = $user->profile;

        $profile->update([
            'is_active' => false,
            'is_hidden' => true,
            'deletion_reason' => $request->reason,
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Your profile has been deactivated. Contact support to reactivate.');
    }

    private function defaultPrefs(): array
    {
        return [
            'email_interest' => true,
            'email_accepted' => true,
            'email_declined' => true,
            'email_views' => false,
            'email_promotions' => false,
        ];
    }
}
