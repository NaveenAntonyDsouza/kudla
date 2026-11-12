<?php

namespace App\Support;

use App\Exceptions\Interest\DailyLimitReachedException;
use App\Models\AdminRecommendation;
use App\Models\AssistedEvent;
use App\Models\BlockedProfile;
use App\Models\Interest;
use App\Models\InterestReply;
use App\Models\MembershipPlan;
use App\Models\Profile;
use App\Models\User;
use App\Services\InterestService;
use App\Services\NotificationService;
use App\Services\ProfileAccessService;
use DomainException;

/**
 * Assisted service: every rule in one place (12 Nov 2026, the way the big
 * portals run it: BharatMatrimony Assisted, Jeevansathi Exclusive, Shaadi VIP).
 *
 * - A plan marked "assisted" gets one named relationship manager (a staff member).
 * - The member gives consent themselves, on their Assisted page (dated tick;
 *   withdrawing it stops all staff work at once).
 * - The manager suggests matches. The member says Yes or No; Yes sends the
 *   interest in the member's name, marked as via the manager. A member who ticked
 *   "may send without asking" has suggestions sent straight away.
 * - Staff answer an interest the member received only on the member's own
 *   instruction (how they gave it and what they said), kept and shown to the member.
 * - Everything is written to the member's history (assisted_events) and the staff
 *   member's Activity Log.
 *
 * Staff may act only while the member is on an active assisted plan, has consent
 * ticked, has an active profile, and only the named manager (or the owner).
 */
class Assisted
{
    /** Suggestions per member per week the staff page asks for (BharatMatrimony shares 4–5, Jeevansathi 2–3). */
    public const WEEKLY_TARGET = 3;

    /** How the member gave an instruction to answer an interest. */
    public const HOW = [
        'phone' => 'Phone call',
        'whatsapp' => 'WhatsApp',
        'in_person' => 'In person',
        'email' => 'Email',
    ];

    /** The member's active plan when it is an assisted one. */
    public static function plan(Profile $member): ?MembershipPlan
    {
        $plan = $member->user?->activeMembership()?->plan;

        return $plan?->is_assisted ? $plan : null;
    }

    public static function isAssisted(Profile $member): bool
    {
        return self::plan($member) !== null;
    }

    public static function hasConsent(Profile $member): bool
    {
        return $member->assisted_consent_at !== null;
    }

    /** The member menu shows "My Assisted Service" (asked once per request). */
    public static function inMenu(?User $user): bool
    {
        static $answers = [];
        $profile = $user?->profile;
        if (! $profile) {
            return false;
        }

        try {
            return $answers[$profile->id] ??= ($profile->relationship_manager_id !== null || self::isAssisted($profile));
        } catch (\Throwable) {
            return false; // before the migration
        }
    }

    /** Sees every assisted member and chooses managers: staff who may change plans. */
    public static function seesAll(): bool
    {
        return Permissions::can('assign_member_plan');
    }

    /** Any plan marked assisted (the staff menu shows only then). */
    public static function offered(): bool
    {
        try {
            return MembershipPlan::where('is_assisted', true)->exists();
        } catch (\Throwable) {
            return false; // before the migration
        }
    }

    /** Staff who can be a relationship manager: active staff accounts. */
    public static function staffOptions(): array
    {
        return User::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNotNull('staff_role_id')->orWhere('role', 'admin'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** Why this staff member may not work for this member now, or null when they may. */
    public static function refusal(?User $staff, Profile $member): ?string
    {
        if (! $staff || ($staff->staff_role_id === null && $staff->role !== 'admin')) {
            return 'Sign in as staff first.';
        }
        if (! $member->is_active || $member->trashed() || app(ProfileAccessService::class)->isSuspended($member)) {
            return 'This member\'s profile is not active.';
        }
        if (! self::isAssisted($member)) {
            return 'This member is not on an assisted plan now.';
        }
        if (! $member->relationship_manager_id) {
            return 'Choose a relationship manager for this member first.';
        }
        if ($member->relationship_manager_id !== $staff->id && ! $staff->isSuperAdmin()) {
            $name = $member->relationshipManager?->name ?? 'another staff member';

            return "Only the member's relationship manager ({$name}) can do this.";
        }
        if (! self::hasConsent($member)) {
            return 'The member has not given consent yet. They tick it on their Assisted page (Menu → My Assisted Service).';
        }

        return null;
    }

    private static function guard(?User $staff, Profile $member): void
    {
        if ($reason = self::refusal($staff, $member)) {
            throw new DomainException($reason);
        }
    }

    /** Set or change the member's manager. Callers check seesAll(). */
    public static function setManager(Profile $member, ?User $manager): void
    {
        if ($member->relationship_manager_id === $manager?->id) {
            return;
        }
        $member->forceFill(['relationship_manager_id' => $manager?->id])->save();
        $member->setRelation('relationshipManager', $manager);

        self::event($member, 'manager_set', auth()->id(), null, ['name' => $manager?->name]);
        StaffActionLog::record('assisted_manager_set', $member->id, ['manager' => $manager?->name ?? 'none']);

        if ($manager && $member->user) {
            app(NotificationService::class)->send($member->user, 'assisted_update', 'Your relationship manager',
                "{$manager->name} is your relationship manager. Open My Assisted Service to see what they do for you.");
        }
    }

    // ── The member's own choices (member site) ─────────────────────────

    public static function giveConsent(Profile $member, bool $autoSend): void
    {
        $member->forceFill(['assisted_consent_at' => now(), 'assisted_auto_send' => $autoSend])->save();
        self::event($member, 'consent_given', null, null, ['auto_send' => $autoSend]);
    }

    public static function setAutoSend(Profile $member, bool $autoSend): void
    {
        if ($member->assisted_auto_send === $autoSend) {
            return;
        }
        $member->forceFill(['assisted_auto_send' => $autoSend])->save();
        self::event($member, $autoSend ? 'auto_send_on' : 'auto_send_off');
    }

    public static function withdrawConsent(Profile $member): void
    {
        $member->forceFill(['assisted_consent_at' => null, 'assisted_auto_send' => false])->save();
        self::event($member, 'consent_withdrawn');
    }

    /** The member's Yes / No on a suggestion. Returns what to tell them. */
    public static function answer(Profile $member, AdminRecommendation $rec, bool $yes): string
    {
        if ($rec->for_profile_id !== $member->id) {
            throw new DomainException('This suggestion is not yours.');
        }
        if ($rec->member_decision !== null) {
            throw new DomainException('You have already answered this one.');
        }
        $rec->update(['member_decision' => $yes ? 'yes' : 'no', 'decided_at' => now(), 'is_viewed' => true]);
        self::event($member, $yes ? 'member_yes' : 'member_no', null, $rec->recommended_profile_id);

        if (! $yes) {
            return 'Noted. Your manager will not contact this member.';
        }
        $error = self::send($rec, $rec->admin_user_id);

        return $error === null
            ? 'Interest sent. We will tell you when they reply.'
            : "Noted, but the interest could not go yet: {$error} Your manager will send it.";
    }

    // ── Staff work (admin panel) ───────────────────────────────────────

    /** Suggest a match. Sent straight away when the member ticked "may send without asking". */
    public static function suggest(User $staff, Profile $member, Profile $other, ?string $note = null): AdminRecommendation
    {
        self::guard($staff, $member);
        if ($reason = self::pairRefusal($member, $other)) {
            throw new DomainException($reason);
        }
        $earlier = AdminRecommendation::where('for_profile_id', $member->id)->where('recommended_profile_id', $other->id)->first();
        if ($earlier) {
            throw new DomainException('Already suggested on '.$earlier->created_at->displayTz()->format('d M Y').'.');
        }

        $rec = AdminRecommendation::create([
            'for_profile_id' => $member->id,
            'recommended_profile_id' => $other->id,
            'admin_user_id' => $staff->id,
            'admin_note' => filled($note) ? mb_substr(trim($note), 0, 500) : null,
            'priority' => 'normal',
        ]);
        self::event($member, 'suggested', $staff->id, $other->id, array_filter(['note' => $rec->admin_note]));
        StaffActionLog::record('assisted_suggested', $member->id, ['match' => $other->matri_id]);

        if ($member->assisted_auto_send) {
            $rec->update(['member_decision' => 'auto', 'decided_at' => now()]);
            if (self::send($rec, $staff->id) === null) {
                StaffActionLog::record('assisted_interest_sent', $member->id, ['to' => $other->matri_id]);
            }
        } elseif ($member->user) {
            app(NotificationService::class)->send($member->user, 'assisted_suggestion', 'A match from your manager',
                // no Matri ID: the notice outlives a block; the Assisted page names only who she may still see
                "{$staff->name} suggested a match for you. Say yes or no on My Assisted Service.");
        }

        return $rec;
    }

    /** Send the interest for a suggestion the member said yes to (or "may send without asking"). */
    public static function sendApproved(User $staff, AdminRecommendation $rec): void
    {
        $member = $rec->forProfile;
        self::guard($staff, $member);
        if (! in_array($rec->member_decision, ['yes', 'auto'], true)) {
            throw new DomainException('The member has not said yes to this match.');
        }
        if ($rec->interest_id) {
            throw new DomainException('The interest has already been sent.');
        }
        if ($error = self::send($rec, $staff->id)) {
            throw new DomainException($error);
        }
        StaffActionLog::record('assisted_interest_sent', $member->id, ['to' => $rec->recommendedProfile?->matri_id]);
    }

    /** Accept or decline an interest the member received, on the member's own instruction. */
    public static function reply(User $staff, Profile $member, Interest $interest, bool $accept, string $how, string $said): InterestReply
    {
        self::guard($staff, $member);
        if ($interest->receiver_profile_id !== $member->id) {
            throw new DomainException('This interest was not sent to this member.');
        }
        if (! isset(self::HOW[$how])) {
            throw new DomainException('Choose how the member told you.');
        }
        $said = trim($said);
        if (mb_strlen($said) < 3) {
            throw new DomainException('Write what the member said, in a few words.');
        }
        $instruction = mb_substr(self::HOW[$how].': '.$said, 0, 255);

        $service = app(InterestService::class);
        $reply = $accept
            ? $service->accept($interest, null, null, 'manager')
            : $service->decline($interest, null, null, false, 'manager');
        $reply->forceFill(['replied_by_admin_id' => $staff->id, 'member_instruction' => $instruction])->save();

        $from = $interest->senderProfile;
        self::event($member, $accept ? 'accepted_for_member' : 'declined_for_member', $staff->id, $from?->id, ['instruction' => $instruction]);
        StaffActionLog::record($accept ? 'assisted_accepted' : 'assisted_declined', $member->id, ['from' => $from?->matri_id, 'instruction' => $instruction]);

        if ($member->user) {
            app(NotificationService::class)->send($member->user, 'assisted_update', $accept ? 'Interest accepted for you' : 'Interest declined for you',
                "{$staff->name} ".($accept ? 'accepted' : 'declined').' an interest for you, as you asked. Details on My Assisted Service.');
        }

        return $reply;
    }

    /** Note a meeting arranged with a match whose family accepted. */
    public static function meeting(User $staff, AdminRecommendation $rec, string $date, ?string $note = null): void
    {
        $member = $rec->forProfile;
        self::guard($staff, $member);
        if ($rec->interest?->status !== 'accepted') {
            throw new DomainException('Arrange a meeting once the interest is accepted.');
        }
        $rec->update(['meeting_on' => $date, 'meeting_note' => filled($note) ? mb_substr(trim($note), 0, 255) : null]);
        self::event($member, 'meeting', $staff->id, $rec->recommended_profile_id, array_filter(['on' => $date, 'note' => $rec->meeting_note]));
        StaffActionLog::record('assisted_meeting', $member->id, ['with' => $rec->recommendedProfile?->matri_id, 'on' => $date]);

        if ($member->user) {
            app(NotificationService::class)->send($member->user, 'assisted_update', 'Meeting arranged',
                "{$staff->name} arranged a meeting for you on ".\Illuminate\Support\Carbon::parse($date)->format('d M Y').'. Details on My Assisted Service.');
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────

    /** Why these two cannot be matched, or null. The member must be allowed to see the match. */
    public static function pairRefusal(Profile $member, Profile $other): ?string
    {
        if ($member->id === $other->id) {
            return 'That is the member themselves.';
        }
        if ($member->gender === $other->gender) {
            return 'That profile is the same gender as the member.';
        }
        if (! $other->is_active || $other->trashed()) {
            return 'That profile is not active.';
        }
        if (! app(ProfileAccessService::class)->canAccess($member, $other)) {
            return 'The member cannot see that profile (not approved, hidden, blocked or limited by its privacy settings).';
        }
        $blocked = BlockedProfile::where(fn ($q) => $q->where('profile_id', $member->id)->where('blocked_profile_id', $other->id))
            ->orWhere(fn ($q) => $q->where('profile_id', $other->id)->where('blocked_profile_id', $member->id))->exists();
        if ($blocked) {
            return 'The member cannot see that profile (not approved, hidden, blocked or limited by its privacy settings).';
        }
        $active = Interest::where(fn ($q) => $q->where('sender_profile_id', $member->id)->where('receiver_profile_id', $other->id))
            ->orWhere(fn ($q) => $q->where('sender_profile_id', $other->id)->where('receiver_profile_id', $member->id))
            ->whereIn('status', ['pending', 'accepted'])->exists();
        if ($active) {
            return 'These two already have an interest between them.';
        }

        return null;
    }

    /** Send the suggestion's interest in the member's name, marked as via the manager. Null when sent, else why not. */
    private static function send(AdminRecommendation $rec, ?int $byAdminId): ?string
    {
        $member = $rec->forProfile;
        $other = $rec->recommendedProfile;
        if (! $member || ! $other) {
            return 'That profile is no longer on the site.';
        }

        try {
            $interest = app(InterestService::class)->send($member, $other, null, null);
        } catch (DailyLimitReachedException) {
            return 'today\'s interest limit on the plan is reached; it can go tomorrow.';
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $e->getMessage();
        }

        $interest->forceFill(['sent_by_admin_id' => $byAdminId])->save();
        Interest::recordSource($interest, 'assisted');
        $rec->update(['interest_id' => $interest->id, 'interest_sent' => true]);
        self::event($member, 'interest_sent', $byAdminId, $other->id);

        return null;
    }

    public static function event(Profile $member, string $action, ?int $adminId = null, ?int $otherProfileId = null, array $details = []): void
    {
        AssistedEvent::create([
            'profile_id' => $member->id,
            'admin_user_id' => $adminId,
            'action' => $action,
            'other_profile_id' => $otherProfileId,
            'details' => $details ?: null,
        ]);
    }

    /**
     * The member's history in their words. $visibleIds: on the member's own page, the
     * profiles they may still see; anyone else (blocked them since, deleted) shows as
     * "a member". Null on the staff page.
     */
    public static function describe(AssistedEvent $e, ?array $visibleIds = null): string
    {
        $who = $e->admin?->name ?? 'Your manager';
        $other = $e->other_profile_id && ($visibleIds === null || in_array($e->other_profile_id, $visibleIds, true))
            ? $e->otherProfile?->matri_id
            : 'a member';
        $d = $e->details ?? [];

        return match ($e->action) {
            'consent_given' => 'You allowed your manager to work on your profile'.(($d['auto_send'] ?? false) ? ', and to send interests without asking you' : '').'.',
            'consent_withdrawn' => 'You stopped the assisted service. Your manager can no longer act for you.',
            'auto_send_on' => 'You let your manager send interests without asking you.',
            'auto_send_off' => 'You asked your manager to ask you before sending interests.',
            'manager_set' => ($d['name'] ?? null) ? "{$d['name']} became your relationship manager." : 'Your relationship manager was removed.',
            'suggested' => "{$who} suggested {$other}.",
            'member_yes' => "You said yes to {$other}.",
            'member_no' => "You said no to {$other}.",
            'interest_sent' => "Interest sent to {$other}".($e->admin_user_id ? " by {$who}" : '').'.',
            'accepted_for_member' => "{$who} accepted the interest from {$other}. Your instruction: ".($d['instruction'] ?? ''),
            'declined_for_member' => "{$who} declined the interest from {$other}. Your instruction: ".($d['instruction'] ?? ''),
            'meeting' => "{$who} arranged a meeting with {$other}".(isset($d['on']) ? ' on '.\Illuminate\Support\Carbon::parse($d['on'])->format('d M Y') : '').'.',
            default => $e->action,
        };
    }
}
