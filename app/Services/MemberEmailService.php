<?php

namespace App\Services;

use App\Mail\InterestAcceptedMail;
use App\Mail\InterestDeclinedMail;
use App\Mail\InterestReceivedMail;
use App\Mail\MembershipActivatedMail;
use App\Mail\MembershipExpiringMail;
use App\Mail\PhotoApprovedMail;
use App\Mail\PhotoRejectedMail;
use App\Mail\ProfileApprovedMail;
use App\Mail\ProfileRejectedMail;
use App\Mail\WelcomeMail;
use App\Models\Interest;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserMembership;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Single place that sends member-facing lifecycle emails.
 *
 * Every send goes through deliver(), which:
 *  - skips members without an email address (email is optional on profiles);
 *  - honours the member's own email preferences (Settings → Alerts and the
 *    one-click unsubscribe link) for the preference-backed types;
 *  - queues the mail — the database queue + cron worker on Bluehost, inline
 *    on the Hostinger sites (QUEUE_CONNECTION=sync);
 *  - never throws. A mail-server hiccup must not fail the action that
 *    triggered the email (approving a photo, expressing interest, paying),
 *    so failures are logged and swallowed.
 */
class MemberEmailService
{
    /**
     * Only accounts created this recently get the "welcome" email when they
     * finish registering — someone who signed up months ago and completes
     * their profile today shouldn't receive a "Welcome!" out of the blue.
     */
    public const WELCOME_WINDOW_DAYS = 7;

    /**
     * Called from the Profile `updated` hook once the relevant flags changed:
     * an admin approved the profile, and/or the member finished registering.
     */
    public function profileUpdated(Profile $profile, bool $approved, bool $registrationCompleted): void
    {
        $this->attempt('profileUpdated', function () use ($profile, $approved, $registrationCompleted) {
            $user = $profile->user;
            if (! $user) {
                return;
            }

            if ($approved) {
                $this->profileApproved($user);
            }
            if ($registrationCompleted) {
                $this->welcome($user);
            }
        });
    }

    public function welcome(User $user): void
    {
        $this->attempt('welcome', function () use ($user) {
            if ($user->created_at && $user->created_at->lt(now()->subDays(self::WELCOME_WINDOW_DAYS))) {
                return;
            }

            $this->deliver($user, new WelcomeMail($user));
        });
    }

    public function profileApproved(User $user): void
    {
        $this->attempt('profileApproved', fn () => $this->deliver($user, new ProfileApprovedMail($user)));
    }

    /** Admin sent a pending profile back ("Request changes") with a reason. */
    public function profileChangesRequested(User $user, string $reason): void
    {
        $this->attempt('profileChangesRequested', fn () => $this->deliver($user, new ProfileRejectedMail($user, $reason)));
    }

    /** One email per member, however many of their photos were approved at once. */
    public function photosApproved(User $user): void
    {
        $this->attempt('photosApproved', fn () => $this->deliver($user, new PhotoApprovedMail($user)));
    }

    public function photoRejected(User $user, string $reason): void
    {
        $this->attempt('photoRejected', fn () => $this->deliver($user, new PhotoRejectedMail($user, $reason)));
    }

    public function membershipActivated(UserMembership $membership): void
    {
        $this->attempt('membershipActivated', function () use ($membership) {
            $user = $membership->user;
            if (! $user) {
                return;
            }

            $this->deliver($user, new MembershipActivatedMail(
                $user,
                $membership->plan?->plan_name ?? 'Premium',
                $membership->ends_at?->format('d M Y') ?? 'No expiry',
            ));
        });
    }

    public function membershipExpiring(UserMembership $membership): void
    {
        $this->attempt('membershipExpiring', function () use ($membership) {
            $user = $membership->user;
            if (! $user) {
                return;
            }

            $this->deliver($user, new MembershipExpiringMail(
                $user,
                $membership->plan?->plan_name ?? 'Premium',
                $membership->ends_at?->format('d M Y') ?? '',
            ));
        });
    }

    public function interestReceived(User $receiver, Interest $interest): void
    {
        $this->attempt('interestReceived', fn () => $this->deliver($receiver, new InterestReceivedMail($interest), 'email_interest'));
    }

    public function interestAccepted(User $sender, Interest $interest): void
    {
        $this->attempt('interestAccepted', fn () => $this->deliver($sender, new InterestAcceptedMail($interest), 'email_accepted'));
    }

    public function interestDeclined(User $sender, Interest $interest): void
    {
        $this->attempt('interestDeclined', fn () => $this->deliver($sender, new InterestDeclinedMail($interest), 'email_declined'));
    }

    protected function deliver(User $user, Mailable $mail, ?string $prefKey = null): void
    {
        if (blank($user->email)) {
            return;
        }

        if ($prefKey !== null && ! $user->wantsNotification($prefKey)) {
            return;
        }

        Mail::to($user->email)->queue($mail);
    }

    /**
     * Run one email end to end — looking up the member and plan, building
     * the mail, sending it — so that NOTHING in it can throw into the caller.
     * These run from model hooks and after-commit callbacks on paths like
     * payment confirmation; a missing plan or a mail-server hiccup must never
     * turn a successful payment or approval into an error page.
     */
    protected function attempt(string $email, callable $send): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            Log::warning('Member email failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
