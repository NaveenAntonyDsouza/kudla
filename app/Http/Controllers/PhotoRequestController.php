<?php

namespace App\Http\Controllers;

use App\Models\PhotoRequest;
use App\Models\Profile;
use App\Services\NotificationService;

class PhotoRequestController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    /**
     * List photo requests (received + sent tabs).
     */
    public function index()
    {
        $profile = auth()->user()->profile;
        $tab = request('tab', 'received');

        $received = PhotoRequest::where('target_profile_id', $profile->id)
            ->with(['requesterProfile' => fn($q) => $q->with(['primaryPhoto', 'religiousInfo', 'educationDetail', 'locationInfo'])])
            ->orderByDesc('created_at')
            ->paginate(20, ['*'], 'page');

        $sent = PhotoRequest::where('requester_profile_id', $profile->id)
            ->with(['targetProfile' => fn($q) => $q->with(['primaryPhoto', 'religiousInfo', 'educationDetail', 'locationInfo'])])
            ->orderByDesc('created_at')
            ->paginate(20, ['*'], 'page');

        $receivedCount = PhotoRequest::where('target_profile_id', $profile->id)->where('status', 'pending')->count();

        return view('photo-requests.index', compact('received', 'sent', 'tab', 'receivedCount'));
    }

    /**
     * Send a photo request to another profile.
     */
    public function send(Profile $profile)
    {
        $myProfile = auth()->user()->profile;

        // Can't request own photo
        if ($profile->id === $myProfile->id) {
            return back()->with('error', 'You cannot send a photo request to yourself.');
        }

        // Check if already sent
        $existing = PhotoRequest::where('requester_profile_id', $myProfile->id)
            ->where('target_profile_id', $profile->id)
            ->first();

        if ($existing) {
            return back()->with('info', 'You have already sent a photo request to this profile on ' . $existing->created_at->format('d M Y') . '.');
        }

        PhotoRequest::create([
            'requester_profile_id' => $myProfile->id,
            'target_profile_id' => $profile->id,
            'status' => 'pending',
        ]);

        // Notify the target user — asking to see a hidden photo, or asking a
        // member with no photo to add one. (The email is sent by the
        // PhotoRequest model hook, with the same two versions.)
        $this->notificationService->send(
            $profile->user,
            'photo_request',
            'Photo Request Received',
            $profile->primaryPhoto
                ? $myProfile->matri_id . ' has requested to view your photos.'
                : $myProfile->matri_id . ' would like you to add a photo.',
            $myProfile->id
        );

        return back()->with('success', 'Photo request sent successfully.');
    }

    /**
     * Approve a photo request.
     */
    public function approve(PhotoRequest $photoRequest)
    {
        $myProfile = auth()->user()->profile;

        if ($photoRequest->target_profile_id !== $myProfile->id) {
            abort(403);
        }

        // Nothing to approve without a photo — the requester is told
        // automatically ("photo added") once one is uploaded and visible.
        if (! $myProfile->primaryPhoto) {
            return redirect()->route('photos.manage')
                ->with('info', 'Add a photo first — members who asked will be notified automatically once it is approved.');
        }

        $photoRequest->update(['status' => 'approved']);

        // Notify the requester
        $this->notificationService->send(
            $photoRequest->requesterProfile->user,
            'photo_request_approved',
            'Photo Request Approved',
            $myProfile->matri_id . ' has approved your photo request.',
            $myProfile->id
        );

        return back()->with('success', 'Photo request approved.');
    }

    /**
     * Ignore a photo request.
     */
    public function ignore(PhotoRequest $photoRequest)
    {
        $myProfile = auth()->user()->profile;

        if ($photoRequest->target_profile_id !== $myProfile->id) {
            abort(403);
        }

        $photoRequest->update(['status' => 'ignored']);

        return back()->with('success', 'Photo request ignored.');
    }
}
