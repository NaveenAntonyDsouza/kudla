<?php

namespace App\Support;

use App\Filament\Pages\FollowUpReport;
use App\Filament\Pages\VerificationCalls as CallsPage;
use App\Filament\Pages\WhatsAppInbox;
use App\Filament\Resources\ContactSubmissionResource;
use App\Filament\Resources\PhotoApprovalResource;
use App\Filament\Resources\UserResource;
use App\Models\ContactSubmission;
use App\Models\Profile;
use App\Models\ProfileNote;
use App\Models\ProfilePhoto;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\VerificationCall;
use App\Models\WhatsAppMessage;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Staff numbers (owner, 27 Nov 2026, Step 1 of the staff analytics plan, after a review by
 * ChatGPT): what is waiting now, and how long new members wait for their first call.
 * Switch: AdminModules::STAFF_NUMBERS. The counts per staff member are in StaffActivity.
 *
 * Rules agreed with the owner:
 *  - each "Waiting now" number is the same count as the list it opens (the calls tab, the
 *    Follow-up Report, the photo, Contact Inbox and WhatsApp menu badges, Pending Approval);
 *  - members who joined before the "Count new members from" day (FROM_SETTING; Kudla:
 *    1 Dec 2026) are shown apart and left out of the times, so an old backlog does not count
 *    as slow calling;
 *  - calls are late after 2 days and follow-ups after their day (as before), but only calls
 *    to members who joined from the start day are counted late; photos and Contact Inbox
 *    messages only show how old they are, never "late";
 *  - times are the middle value (median), not an average, so a few old calls don't skew them.
 * Nothing is recorded here; it only reads.
 */
final class StaffNumbers
{
    /** Site setting (Y-m-d): members who joined before this day count as the old backlog. */
    public const FROM_SETTING = 'staff_numbers_from';

    public static function on(): bool
    {
        return AdminModules::staffNumbers();
    }

    /** The first day new members are counted from (site time zone), or null when not set. */
    public static function from(): ?CarbonImmutable
    {
        try {
            $value = trim((string) SiteSetting::getValue(self::FROM_SETTING, ''));

            return $value !== '' ? CarbonImmutable::parse($value, StaffActivity::tz())->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * The "Waiting now" cards this staff member may open, in order:
     * [key, label, count, lines: [[text, tone]], url]. Tone 'danger' = late by the old rules.
     *
     * @return list<array<string, mixed>>
     */
    public static function waiting(User $viewer): array
    {
        $cards = [];
        foreach (['calls', 'followUps', 'photos', 'contactInbox', 'whatsapp', 'approvals'] as $card) {
            try {
                if ($made = self::$card($viewer)) {
                    $cards[] = $made;
                }
            } catch (\Throwable $e) {
                report($e);   // one card must never stop the page
            }
        }

        return $cards;
    }

    /** Calls still to make: Everyone's calls for a manager, My calls for a caller (the calls page's tab count). */
    private static function calls(User $viewer): ?array
    {
        if (! VerificationCalls::enabled() || ! VerificationCalls::canUse($viewer)) {
            return null;
        }
        $tab = VerificationCalls::managesAll($viewer) ? 'all' : (VerificationCalls::isCaller($viewer) ? 'mine' : 'anyone');
        $list = fn () => VerificationCalls::tab($viewer, $tab)->reorder();
        $since = self::from();
        // members who joined from the start day; the old backlog has its own line
        $newOnly = fn ($q) => $q->when($since, fn ($w) => $w->where('profiles.created_at', '>=', self::stored($since)));

        $count = $list()->count();
        $never = $list()->where('verification_calls.attempts', 0);
        $neverCount = (clone $never)->count();
        $due = $list()->where('verification_calls.due_at', '<=', now())->count();
        $late = $list()->where(fn ($w) => VerificationCalls::lateWhere($w, now()->subDays(VerificationCalls::ANYONE_AFTER_DAYS)))->tap($newOnly)->count();

        $lines = [];
        if ($due) {
            $lines[] = ['due now: '.number_format($due), null];
        }
        if ($late) {
            $lines[] = ['late ('.VerificationCalls::ANYONE_AFTER_DAYS.'+ days): '.number_format($late), 'danger'];
        }
        if ($neverCount) {
            $lines[] = ['not called yet: '.number_format($neverCount), null];
            // the longest wait among the new members only: the old backlog has its own line
            $oldest = (clone $never)->tap($newOnly)->min('profiles.created_at');
            if ($oldest) {
                $lines[] = ['longest wait for a first call: '.self::age($oldest), null];
            }
        }
        if ($since) {
            $before = $list()->where('profiles.created_at', '<', self::stored($since))->count();
            if ($before) {
                $lines[] = [number_format($before).' joined before '.$since->format('j M Y'), null];
            }
        }

        return [
            'key' => 'calls',
            'label' => $tab === 'all' ? 'Calls still to make' : ($tab === 'mine' ? 'Your calls' : 'Calls anyone can make'),
            'count' => $count,
            'lines' => $lines,
            'url' => CallsPage::getUrl(['tab' => $tab]),
        ];
    }

    /** Follow-ups due today and late: the Follow-up Report's own two lists. */
    private static function followUps(User $viewer): ?array
    {
        if (! FollowUpReport::canAccess()) {
            return null;
        }
        $today = FollowUpReport::todayQuery()->count();
        $late = FollowUpReport::overdueQuery()->count();

        return [
            'key' => 'follow_ups',
            'label' => 'Follow-ups due',
            'count' => $today + $late,
            'lines' => array_values(array_filter([
                ['today: '.number_format($today), null],
                $late ? ['late: '.number_format($late), 'danger'] : null,
            ])),
            'url' => FollowUpReport::getUrl(),
        ];
    }

    /** Photos waiting for approval (the Photo Approvals badge), with how old the oldest is. */
    private static function photos(User $viewer): ?array
    {
        if (! PhotoApprovalResource::canAccess()) {
            return null;
        }
        $pending = ProfilePhoto::where('approval_status', 'pending');
        $count = (clone $pending)->count();

        return [
            'key' => 'photos',
            'label' => 'Photos to approve',
            'count' => $count,
            'lines' => $count ? [['the oldest '.self::age((clone $pending)->min('created_at')), null]] : [],
            'url' => PhotoApprovalResource::getUrl('index', ['tab' => 'pending']),
        ];
    }

    /** People waiting for a first answer in the Contact Inbox (the menu badge), oldest message first. */
    private static function contactInbox(User $viewer): ?array
    {
        if (! ContactSubmissionResource::canViewAny()) {
            return null;
        }
        $count = ContactSubmission::sendersWaiting();

        return [
            'key' => 'contact_inbox',
            'label' => 'Contact Inbox: waiting for a reply',
            'count' => $count,
            'lines' => $count ? [['the oldest '.self::age(ContactSubmission::where('status', 'new')->min('created_at')), null]] : [],
            'url' => ContactSubmissionResource::getUrl('index', ['tab' => 'new']),
        ];
    }

    /** People with unread WhatsApp messages (the WhatsApp Inbox badge), only where the inbox is in the menu. */
    private static function whatsapp(User $viewer): ?array
    {
        if (! WhatsAppInbox::shouldRegisterNavigation()) {
            return null;
        }
        $unread = WhatsAppMessage::where('direction', WhatsAppMessage::IN)->whereNull('read_at');
        $count = (int) (clone $unread)->distinct()->count('wa_id');

        return [
            'key' => 'whatsapp',
            'label' => 'WhatsApp: unread',
            'count' => $count,
            'lines' => $count ? [['the oldest '.self::age((clone $unread)->min('created_at')), null]] : [],
            'url' => WhatsAppInbox::getUrl(),
        ];
    }

    /** Profiles waiting for approval (All Members → Pending Approval), only on sites that approve each profile. */
    private static function approvals(User $viewer): ?array
    {
        if (SiteSetting::getValue('auto_approve_profiles', '1') !== '0' || ! UserResource::canViewAny()) {
            return null;
        }
        $pending = Profile::query()->withoutDeletedAccounts()->whereNotNull('full_name')->where('is_approved', false);
        $count = (clone $pending)->count();

        return [
            'key' => 'approvals',
            'label' => 'Profiles to approve',
            'count' => $count,
            'lines' => $count ? [['the oldest '.self::age((clone $pending)->min('created_at')), null]] : [],
            'url' => UserResource::getUrl('index', ['tab' => 'pending']),
        ];
    }

    /**
     * How long new members waited, for the period: from joining to their first call (members
     * whose first call was in the period) and to Verified (members verified in the period).
     * Only members on the calls list who joined on or after from(). Middle values, in hours.
     *
     * @return array{first: array{n: int, hours: ?float}, verified: array{n: int, hours: ?float}}
     */
    public static function speed(CarbonImmutable $from, CarbonImmutable $to): array
    {
        [$a, $b] = [self::stored($from), self::stored($to)];
        $since = self::from();
        $joinedFrom = fn ($q) => $q->when($since, fn ($w) => $w->where('profiles.created_at', '>=', self::stored($since)));

        $firstCalls = ProfileNote::query()->where('note_type', 'call')
            ->groupBy('profile_id')->selectRaw('profile_id, MIN(created_at) as first_at');
        $first = DB::table('verification_calls')
            ->join('profiles', 'profiles.id', '=', 'verification_calls.profile_id')
            ->whereNull('profiles.deleted_at')
            ->joinSub($firstCalls, 'firsts', 'firsts.profile_id', '=', 'verification_calls.profile_id')
            ->whereBetween('firsts.first_at', [$a, $b])
            ->tap($joinedFrom)
            ->get(['profiles.created_at as joined_at', 'firsts.first_at as at']);

        $verified = DB::table('verification_calls')
            ->join('profiles', 'profiles.id', '=', 'verification_calls.profile_id')
            ->whereNull('profiles.deleted_at')
            ->where('verification_calls.status', 'verified')
            ->whereBetween('verification_calls.done_at', [$a, $b])
            ->tap($joinedFrom)
            ->get(['profiles.created_at as joined_at', 'verification_calls.done_at as at']);

        $hours = fn ($rows) => $rows->map(fn ($r) => max(0, self::parse($r->joined_at)->diffInMinutes(self::parse($r->at), false)) / 60)->all();

        return [
            'first' => ['n' => $first->count(), 'hours' => self::median($hours($first))],
            'verified' => ['n' => $verified->count(), 'hours' => self::median($hours($verified))],
        ];
    }

    /** The middle value of a list of numbers (the mean of the two middle ones for an even count), null when empty. */
    public static function median(array $values): ?float
    {
        if ($values === []) {
            return null;
        }
        sort($values);
        $n = count($values);
        $mid = intdiv($n, 2);

        return $n % 2 ? (float) $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2;
    }

    /** "under an hour", "5 hours", "1.5 days" (null: "—"). */
    public static function duration(?float $hours): string
    {
        return match (true) {
            $hours === null => '—',
            $hours < 1 => 'under an hour',
            $hours < 48 => (int) round($hours).' '.(round($hours) == 1 ? 'hour' : 'hours'),
            default => rtrim(rtrim(number_format($hours / 24, 1), '0'), '.').' days',
        };
    }

    /** How long ago a stored time was: "today" (under a day), "1 day", "4 days". */
    public static function age(mixed $stored): string
    {
        if (blank($stored)) {
            return '';
        }
        $days = (int) floor(self::parse($stored)->diffInMinutes(now(), false) / 1440);

        return match (true) {
            $days < 1 => 'today',
            $days === 1 => '1 day',
            default => $days.' days',
        };
    }

    /** A time as the database stores it (app time zone). */
    private static function stored(CarbonInterface $at): string
    {
        return CarbonImmutable::instance($at)->setTimezone((string) config('app.timezone', 'UTC'))->toDateTimeString();
    }

    private static function parse(mixed $stored): Carbon
    {
        return $stored instanceof CarbonInterface
            ? Carbon::instance($stored)
            : Carbon::parse((string) $stored, (string) config('app.timezone', 'UTC'));
    }
}
