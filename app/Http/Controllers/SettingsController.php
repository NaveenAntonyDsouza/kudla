<?php

namespace App\Http\Controllers;

use App\Services\MemberEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $profile = $user->profile;
        $section = $request->get('section', 'search_visibility');
        // Redirect old profile_filters URL to merged section
        if ($section === 'profile_filters') {
            return redirect()->route('settings.index', ['section' => 'search_visibility']);
        }
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
            'show_profile_to' => $request->input('show_profile_to', 'all'),
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
            'new_password' => 'required|min:6|max:64|confirmed',
        ]);

        $user = auth()->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => Hash::make($request->new_password)]);
        app(MemberEmailService::class)->passwordChanged($user);

        return back()->with('success', 'Password changed successfully.');
    }

    public function deleteProfile(Request $request)
    {
        $data = $request->validate([
            'reason' => 'required|string|in:I am Married,My Marriage is Fixed,Other Reasons',
            'met_via' => 'nullable|string|in:this_site,other_site,other',
            'wedding_date' => 'nullable|date|after:2000-01-01|before:+3 years',
            'partner_matri_id' => 'nullable|string|max:20',
            'other_site' => 'nullable|string|max:60',
            'other_reason' => 'nullable|string|max:190',
        ]);

        $user = auth()->user();
        $profile = $user->profile;

        $profile->update([
            'is_active' => false,
            'is_hidden' => true,
            'deletion_reason' => self::deletionReason($data),
        ]);

        // Met their partner here: a possible success story for staff to follow up (never published by itself)
        if (($data['met_via'] ?? null) === 'this_site') {
            try {
                \App\Models\AdminActivityLog::create([
                    'action' => 'married_through_site',
                    'model_type' => \App\Models\Profile::class,
                    'model_id' => $profile->id,
                    'changes' => array_filter([
                        'matri_id' => $profile->matri_id,
                        'partner_matri_id' => isset($data['partner_matri_id']) ? strtoupper(trim($data['partner_matri_id'])) : null,
                        'wedding_date' => $data['wedding_date'] ?? null,
                        'reason' => $data['reason'],
                    ]),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Your profile has been deactivated. Contact support to reactivate.');
    }

    /**
     * One line for profiles.deletion_reason (≤ 200 chars), e.g.
     * "I am Married · met on Kudla Matrimony · wedding 2026-11-20 · partner KM100123".
     */
    public static function deletionReason(array $data): string
    {
        if (($data['reason'] ?? '') === 'Other Reasons') {
            $text = trim((string) ($data['other_reason'] ?? ''));

            return mb_substr($text !== '' ? 'Other: '.$text : 'Other Reasons', 0, 200);
        }

        $parts = [$data['reason'] ?? 'Unknown'];
        $parts[] = match ($data['met_via'] ?? null) {
            'this_site' => 'met on '.config('app.name'),
            'other_site' => 'met on another site'.(trim((string) ($data['other_site'] ?? '')) !== '' ? ' ('.trim($data['other_site']).')' : ''),
            'other' => 'met by other means',
            default => null,
        };
        $parts[] = ! empty($data['wedding_date']) ? 'wedding '.$data['wedding_date'] : null;
        $parts[] = ! empty($data['partner_matri_id']) ? 'partner '.strtoupper(trim($data['partner_matri_id'])) : null;

        return mb_substr(implode(' · ', array_filter($parts)), 0, 200);
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
