<?php

namespace App\Http\Controllers;

use App\Models\Interest;
use App\Models\Profile;
use App\Services\InterestService;
use Illuminate\Http\Request;

class InterestController extends Controller
{
    public function __construct(
        private InterestService $interestService
    ) {}

    /**
     * Interest inbox — list view with tabs and filters.
     */
    public function inbox(Request $request)
    {
        $profile = auth()->user()->profile;
        $tab = $request->get('tab', 'all');
        $filter = $request->get('filter');

        $query = Interest::query()
            ->with(['senderProfile.primaryPhoto', 'receiverProfile.primaryPhoto', 'replies'])
            ->orderBy('updated_at', 'desc');

        // Tab filtering
        switch ($tab) {
            case 'received':
                $query->where('receiver_profile_id', $profile->id)
                    ->where('is_trashed_by_receiver', false);
                break;
            case 'sent':
                $query->where('sender_profile_id', $profile->id)
                    ->where('is_trashed_by_sender', false);
                break;
            case 'starred':
                $query->where(function ($q) use ($profile) {
                    $q->where(fn($q2) => $q2->where('sender_profile_id', $profile->id)->where('is_starred_by_sender', true))
                      ->orWhere(fn($q2) => $q2->where('receiver_profile_id', $profile->id)->where('is_starred_by_receiver', true));
                });
                break;
            case 'trash':
                $query->where(function ($q) use ($profile) {
                    $q->where(fn($q2) => $q2->where('sender_profile_id', $profile->id)->where('is_trashed_by_sender', true))
                      ->orWhere(fn($q2) => $q2->where('receiver_profile_id', $profile->id)->where('is_trashed_by_receiver', true));
                });
                break;
            default: // all
                $query->where(function ($q) use ($profile) {
                    $q->where(fn($q2) => $q2->where('sender_profile_id', $profile->id)->where('is_trashed_by_sender', false))
                      ->orWhere(fn($q2) => $q2->where('receiver_profile_id', $profile->id)->where('is_trashed_by_receiver', false));
                });
                break;
        }

        // Sub-filter
        if ($filter) {
            switch ($filter) {
                case 'interest_received':
                    $query->where('receiver_profile_id', $profile->id)->where('status', 'pending');
                    break;
                case 'interest_sent':
                    $query->where('sender_profile_id', $profile->id)->where('status', 'pending');
                    break;
                case 'i_accepted':
                    $query->where('receiver_profile_id', $profile->id)->where('status', 'accepted');
                    break;
                case 'accepted_me':
                    $query->where('sender_profile_id', $profile->id)->where('status', 'accepted');
                    break;
                case 'i_declined':
                    $query->where('receiver_profile_id', $profile->id)->where('status', 'declined');
                    break;
                case 'declined_me':
                    $query->where('sender_profile_id', $profile->id)->where('status', 'declined');
                    break;
                case 'expired':
                    $query->where(function ($q) use ($profile) {
                        $q->where('sender_profile_id', $profile->id)
                          ->orWhere('receiver_profile_id', $profile->id);
                    })->where('status', 'expired');
                    break;
            }
        }

        $interests = $query->paginate(20)->withQueryString();

        // Counts for sidebar
        $counts = $this->getInboxCounts($profile);

        return view('interests.inbox', compact('interests', 'profile', 'tab', 'filter', 'counts'));
    }

    /**
     * Interest detail view with conversation thread.
     */
    public function show(Interest $interest)
    {
        $profile = auth()->user()->profile;

        // Authorization: must be sender or receiver
        if ($interest->sender_profile_id !== $profile->id && $interest->receiver_profile_id !== $profile->id) {
            abort(403);
        }

        $interest->load([
            'senderProfile.primaryPhoto',
            'senderProfile.religiousInfo',
            'senderProfile.educationDetail',
            'senderProfile.locationInfo',
            'receiverProfile.primaryPhoto',
            'receiverProfile.religiousInfo',
            'receiverProfile.educationDetail',
            'receiverProfile.locationInfo',
            'replies.replierProfile',
        ]);

        // Determine the "other" profile
        $isSender = $interest->sender_profile_id === $profile->id;
        $otherProfile = $isSender ? $interest->receiverProfile : $interest->senderProfile;

        return view('interests.show', compact('interest', 'profile', 'isSender', 'otherProfile'));
    }

    /**
     * Send interest to a profile.
     */
    public function send(Request $request, Profile $profile)
    {
        $request->validate([
            'template_id' => 'nullable|string|max:30',
            'custom_message' => 'nullable|string|max:500',
        ]);

        $sender = auth()->user()->profile;

        try {
            $this->interestService->send(
                $sender,
                $profile,
                $request->template_id,
                $request->custom_message
            );

            return back()->with('success', 'Interest sent successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['interest' => $e->getMessage()]);
        }
    }

    /**
     * Accept an interest.
     */
    public function accept(Request $request, Interest $interest)
    {
        $profile = auth()->user()->profile;
        if ($interest->receiver_profile_id !== $profile->id) {
            abort(403);
        }

        $request->validate([
            'template_id' => 'nullable|string|max:30',
            'custom_message' => 'nullable|string|max:500',
        ]);

        try {
            $this->interestService->accept($interest, $request->template_id, $request->custom_message);
            return back()->with('success', 'Interest accepted!');
        } catch (\Exception $e) {
            return back()->withErrors(['interest' => $e->getMessage()]);
        }
    }

    /**
     * Decline an interest.
     */
    public function decline(Request $request, Interest $interest)
    {
        $profile = auth()->user()->profile;
        if ($interest->receiver_profile_id !== $profile->id) {
            abort(403);
        }

        $request->validate([
            'template_id' => 'nullable|string|max:30',
            'custom_message' => 'nullable|string|max:250',
            'silent' => 'nullable|boolean',
        ]);

        try {
            $this->interestService->decline(
                $interest,
                $request->template_id,
                $request->custom_message,
                $request->boolean('silent')
            );
            return back()->with('success', 'Interest declined.');
        } catch (\Exception $e) {
            return back()->withErrors(['interest' => $e->getMessage()]);
        }
    }

    /**
     * Send a chat message in an accepted interest thread.
     */
    public function sendMessage(Request $request, Interest $interest)
    {
        $profile = auth()->user()->profile;

        if ($interest->sender_profile_id !== $profile->id && $interest->receiver_profile_id !== $profile->id) {
            abort(403);
        }

        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        try {
            $this->interestService->sendMessage($interest, $profile, $request->message);
            return back()->with('success', 'Message sent!');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /**
     * Cancel a sent interest.
     */
    public function cancel(Interest $interest)
    {
        $profile = auth()->user()->profile;
        if ($interest->sender_profile_id !== $profile->id) {
            abort(403);
        }

        try {
            $this->interestService->cancel($interest);
            return back()->with('success', 'Interest cancelled.');
        } catch (\Exception $e) {
            return back()->withErrors(['interest' => $e->getMessage()]);
        }
    }

    /**
     * Toggle star on an interest.
     */
    public function toggleStar(Interest $interest)
    {
        $profile = auth()->user()->profile;

        if ($interest->sender_profile_id === $profile->id) {
            $interest->update(['is_starred_by_sender' => ! $interest->is_starred_by_sender]);
        } elseif ($interest->receiver_profile_id === $profile->id) {
            $interest->update(['is_starred_by_receiver' => ! $interest->is_starred_by_receiver]);
        } else {
            abort(403);
        }

        return back();
    }

    /**
     * Move interest to trash.
     */
    public function trash(Interest $interest)
    {
        $profile = auth()->user()->profile;

        if ($interest->sender_profile_id === $profile->id) {
            $interest->update(['is_trashed_by_sender' => true]);
        } elseif ($interest->receiver_profile_id === $profile->id) {
            $interest->update(['is_trashed_by_receiver' => true]);
        } else {
            abort(403);
        }

        return redirect()->route('interests.inbox')->with('success', 'Moved to trash.');
    }

    /**
     * Get counts for the inbox sidebar.
     */
    /**
     * Get all inbox counts in 2 queries instead of 13.
     * Uses conditional aggregation (SUM + CASE) for efficiency.
     */
    private function getInboxCounts(Profile $profile): array
    {
        $id = $profile->id;

        $sent = \DB::selectOne("
            SELECT
                SUM(CASE WHEN is_trashed_by_sender = 0 THEN 1 ELSE 0 END) as not_trashed,
                SUM(CASE WHEN is_trashed_by_sender = 0 AND status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
                SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined,
                SUM(CASE WHEN is_starred_by_sender = 1 THEN 1 ELSE 0 END) as starred,
                SUM(CASE WHEN is_trashed_by_sender = 1 THEN 1 ELSE 0 END) as trashed,
                SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired
            FROM interests WHERE sender_profile_id = ?
        ", [$id]);

        $received = \DB::selectOne("
            SELECT
                SUM(CASE WHEN is_trashed_by_receiver = 0 THEN 1 ELSE 0 END) as not_trashed,
                SUM(CASE WHEN is_trashed_by_receiver = 0 AND status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
                SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined,
                SUM(CASE WHEN is_starred_by_receiver = 1 THEN 1 ELSE 0 END) as starred,
                SUM(CASE WHEN is_trashed_by_receiver = 1 THEN 1 ELSE 0 END) as trashed,
                SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired
            FROM interests WHERE receiver_profile_id = ?
        ", [$id]);

        return [
            'all' => ($sent->not_trashed ?? 0) + ($received->not_trashed ?? 0),
            'received' => $received->not_trashed ?? 0,
            'sent' => $sent->not_trashed ?? 0,
            'starred' => ($sent->starred ?? 0) + ($received->starred ?? 0),
            'trash' => ($sent->trashed ?? 0) + ($received->trashed ?? 0),
            'interest_received' => $received->pending ?? 0,
            'interest_sent' => $sent->pending ?? 0,
            'i_accepted' => $received->accepted ?? 0,
            'accepted_me' => $sent->accepted ?? 0,
            'i_declined' => $received->declined ?? 0,
            'declined_me' => $sent->declined ?? 0,
            'expired' => ($sent->expired ?? 0) + ($received->expired ?? 0),
        ];
    }
}
