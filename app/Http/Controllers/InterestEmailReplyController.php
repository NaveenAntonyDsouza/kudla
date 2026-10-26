<?php

namespace App\Http\Controllers;

use App\Models\Interest;
use App\Models\User;
use App\Services\InterestService;
use App\Support\Analytics;
use App\Support\MemberSummary;
use Illuminate\Http\Request;

/**
 * Accept / Decline straight from the interest emails, without logging in
 * (weekly review, Oct 2026: most members who owe a reply never log in).
 * The signed link only shows the choice (mail scanners open every link, so a
 * GET must change nothing); the button POSTs to the same signed URL. The link
 * stops working once the interest is answered or the member's email changes.
 */
class InterestEmailReplyController extends Controller
{
    public function show(Interest $interest, Request $request)
    {
        return view('interests.email-reply', $this->state($interest, $request) + [
            'action' => $request->fullUrl(),
            'chosen' => in_array($request->query('reply'), ['accept', 'decline'], true) ? $request->query('reply') : null,
            'done' => null,
        ]);
    }

    public function store(Interest $interest, Request $request, InterestService $interests)
    {
        $request->validate(['choice' => 'required|in:accept,decline']);
        $state = $this->state($interest, $request);

        $done = null;
        if ($state['status'] === 'ready') {
            try {
                if ($request->input('choice') === 'accept') {
                    $interests->accept($interest, null, null);
                    Analytics::track('interest_accepted', ['via' => 'email_link'], 'InterestAccepted');
                } else {
                    $interests->decline($interest, null, null);
                    Analytics::track('interest_declined', ['via' => 'email_link']);
                }
                $done = $request->input('choice');
            } catch (\RuntimeException) {
                // answered a moment ago in another tab or on the site
            }
            $state = $this->state($interest->fresh(), $request);
        }

        return view('interests.email-reply', $state + ['action' => null, 'chosen' => null, 'done' => $done]);
    }

    /** @return array{status: string, senderId: ?string, summary: string, answer: ?string, pageUrl: string} */
    private function state(Interest $interest, Request $request): array
    {
        $receiver = $interest->receiverProfile?->user;
        $sender = $interest->senderProfile;

        $status = match (true) {
            ! $receiver || ! hash_equals(User::emailFingerprint((string) $receiver->email), (string) $request->query('e')) => 'expired',
            $receiver->blockedStatus() !== null => 'expired',
            $interest->status !== 'pending' => 'answered',
            ! $sender || ! $sender->user => 'gone',
            default => 'ready',
        };

        return [
            'status' => $status,
            'senderId' => $sender?->matri_id,
            'summary' => $sender ? MemberSummary::line($sender) : '',
            'answer' => in_array($interest->status, ['accepted', 'declined'], true) ? $interest->status : null,
            'pageUrl' => route('interests.show', $interest),
        ];
    }
}
