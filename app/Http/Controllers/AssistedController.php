<?php

namespace App\Http\Controllers;

use App\Models\AdminRecommendation;
use App\Models\AssistedEvent;
use App\Models\InterestReply;
use App\Support\Assisted;
use App\Support\ProfileVisibility;
use App\Support\WhatsApp;
use DomainException;
use Illuminate\Http\Request;

/**
 * The member's own "My Assisted Service" page (12 Nov 2026): who their
 * relationship manager is, their consent (given, and withdrawn, only here, by
 * the member), Yes / No on the matches the manager suggested, and a report of
 * everything the manager did for them. Rules in App\Support\Assisted.
 */
class AssistedController extends Controller
{
    public function index()
    {
        $profile = auth()->user()->profile;
        $plan = Assisted::plan($profile);
        $hasHistory = $profile->relationship_manager_id
            || AssistedEvent::where('profile_id', $profile->id)->exists();
        if (! $plan && ! $hasHistory) {
            return redirect()->route('membership.index')
                ->with('info', 'The assisted service, with your own relationship manager, comes with an assisted plan.');
        }

        $history = AssistedEvent::where('profile_id', $profile->id)->with(['admin', 'otherProfile'])->latest('id')->limit(60)->get();
        $replies = InterestReply::whereNotNull('replied_by_admin_id')
            ->whereHas('interest', fn ($q) => $q->where('receiver_profile_id', $profile->id))
            ->with(['interest.senderProfile'])->latest()->limit(50)->get();

        // only profiles this member may still see are named or shown with their details
        $visibleIds = ProfileVisibility::forMember(\App\Models\Profile::query(), $profile)
            ->where(fn ($q) => $q
                ->whereIn('profiles.id', AdminRecommendation::where('for_profile_id', $profile->id)->select('recommended_profile_id'))
                ->orWhereIn('profiles.id', $history->pluck('other_profile_id')->filter()->all() ?: [0])
                ->orWhereIn('profiles.id', $replies->pluck('interest.sender_profile_id')->filter()->all() ?: [0]))
            ->pluck('profiles.id')->all();

        $recs = AdminRecommendation::where('for_profile_id', $profile->id)
            ->with(['recommendedProfile' => fn ($q) => $q->with(['primaryPhoto', 'religiousInfo', 'educationDetail', 'locationInfo']), 'interest', 'adminUser'])
            ->latest()->get();

        $manager = $profile->relationshipManager;
        $managerDigits = $manager?->phone ? WhatsApp::digits($manager->phone) : null;

        return view('assisted.index', [
            'profile' => $profile,
            'plan' => $plan,
            'ends' => $plan ? auth()->user()->activeMembership()?->ends_at : null,
            'manager' => $manager,
            'whatsapp' => $managerDigits ? 'https://wa.me/'.$managerDigits : WhatsApp::link('Hi, about my assisted service ('.$profile->matri_id.').'),
            'waiting' => $recs->whereNull('member_decision')->filter(fn ($r) => in_array($r->recommended_profile_id, $visibleIds, true))->values(),
            'recs' => $recs,
            'visibleIds' => $visibleIds,
            'stages' => AdminRecommendation::STAGES,
            'replies' => $replies,
            'history' => $history,
            'describe' => fn (AssistedEvent $e) => Assisted::describe($e, $visibleIds),
        ]);
    }

    public function consent(Request $request)
    {
        $profile = auth()->user()->profile;
        if (! Assisted::isAssisted($profile)) {
            return back()->with('error', 'Your plan does not include the assisted service.');
        }
        $request->validate(['agree' => 'accepted'], ['agree.accepted' => 'Tick the box to allow your manager to work on your profile.']);
        Assisted::giveConsent($profile, $request->boolean('auto_send'));

        return back()->with('success', 'Thank you. Your manager can now work on your profile.');
    }

    public function autoSend(Request $request)
    {
        $profile = auth()->user()->profile;
        if (! Assisted::hasConsent($profile)) {
            return back();
        }
        Assisted::setAutoSend($profile, $request->boolean('auto_send'));

        return back()->with('success', $request->boolean('auto_send')
            ? 'Your manager may now send interests without asking you first.'
            : 'Your manager will ask you before sending each interest.');
    }

    public function withdraw()
    {
        $profile = auth()->user()->profile;
        if (Assisted::hasConsent($profile)) {
            Assisted::withdrawConsent($profile);
        }

        return back()->with('success', 'Done. Your manager can no longer act for you. You can allow it again here at any time.');
    }

    public function answer(Request $request, AdminRecommendation $recommendation)
    {
        $profile = auth()->user()->profile;
        abort_unless($recommendation->for_profile_id === $profile->id, 404);
        $request->validate(['answer' => 'required|in:yes,no']);

        try {
            $message = Assisted::answer($profile, $recommendation, $request->input('answer') === 'yes');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $message);
    }
}
