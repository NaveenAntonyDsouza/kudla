<?php

namespace App\Support;

use App\Filament\Pages\MembersWithoutPhoto;
use App\Http\Middleware\AppOnlyMode;
use App\Models\MembershipPlan;
use App\Models\Profile;
use App\Models\ProfileNote;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\VerificationCall;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The one way staff record a contact with a member (simpler staff screens, owner 25 Nov 2026).
 * Log call and Log WhatsApp on the member page, Notes on All Members and the View page, and
 * Done on the Follow-up Report all come here, so a contact is written down the same way
 * wherever it is typed.
 *
 * On 13–25 Nov 2026 the calls were typed as plain notes, which never touched the calls list
 * (216 of 216 calls still said "To call"). So a call on a new member's verification call goes
 * through VerificationCalls (tries, verified, call back, the week's counts); anything else is
 * a note in the member's history. Nothing is sent to the member from here: staff send a
 * WhatsApp from their own WhatsApp, then record what they sent.
 */
final class MemberContacts
{
    public const TYPES = [
        'call' => 'Call',
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
        'meeting' => 'Met in person',
        'general' => 'Other',
    ];

    public const OUTCOMES = [
        'spoke' => 'Spoke to them',
        'no_answer' => 'No answer',
        'call_back' => 'Busy, call back',
        'wrong_number' => 'Wrong number',
        'not_looking' => 'Not looking any more',
        'mismatch' => 'Details did not match',
    ];

    /** Only on a new member's verification call: they end it for a manager to look at (Needs a check). */
    public const LIST_ONLY = ['not_looking', 'mismatch'];

    public const WHATSAPP = [
        'missed' => 'We tried to call you',
        'photo' => 'Asked for a photo',
        'link' => 'Search or community link',
        'other' => 'Other',
    ];

    public const NOTE_MAX = 1000;

    /** The member's open verification call, whoever it is given to (null when calls are off). */
    public static function openCall(Profile $profile): ?VerificationCall
    {
        if (! VerificationCalls::enabled()) {
            return null;
        }
        $row = VerificationCall::with('caller')->where('profile_id', $profile->getKey())
            ->whereIn('status', VerificationCall::OPEN)->first();

        return $row?->setRelation('profile', $profile);
    }

    /** The open verification call this staff member may record, or null. */
    public static function workableCall(?User $staff, Profile $profile): ?VerificationCall
    {
        $row = self::openCall($profile);

        return $row && VerificationCalls::mayWork($staff, $row) ? $row : null;
    }

    /** The outcomes offered for this member: the last two only on a verification call they may record. */
    public static function outcomesFor(?User $staff, Profile $profile): array
    {
        return self::workableCall($staff, $profile)
            ? self::OUTCOMES
            : array_diff_key(self::OUTCOMES, array_flip(self::LIST_ONLY));
    }

    public static function whatsappKinds(): array
    {
        return AppOnlyMode::on() ? array_merge(self::WHATSAPP, ['link' => 'App download link']) : self::WHATSAPP;
    }

    /** "+919876543210" for Copy number: the member's WhatsApp number, else their phone. */
    public static function whatsappNumber(Profile $profile): ?string
    {
        $digits = MembersWithoutPhoto::whatsappDigits($profile);

        return $digits ? '+'.$digits : null;
    }

    /** The sign-in phone as "+919876543210" (null when there is none). */
    public static function phoneNumber(Profile $profile): ?string
    {
        $phone = (string) ($profile->user?->phone ?? '');
        $digits = $phone !== '' ? WhatsApp::digits($phone) : null;   // never the site's own number

        return $digits ? '+'.$digits : null;
    }

    /** The ready-made text for a WhatsApp message (null for Other). */
    public static function readyText(Profile $profile, ?string $kind): ?string
    {
        return match ($kind) {
            'missed' => VerificationCalls::message($profile, 'missed'),
            'photo' => VerificationCalls::message($profile, 'photo'),
            'link' => VerificationCalls::message($profile, 'matches'),
            default => null,
        };
    }

    /** Hints under three of the checklist items: how many members, the link, the plans. The items' words never change. */
    public static function taskHints(Profile $profile): array
    {
        $hints = [];
        try {
            $members = Profile::where('is_active', true)->approved()
                ->whereHas('user', fn ($q) => $q->whereNull('staff_role_id'))->count();
            $hints['members'] = number_format($members).' members on '.SiteSetting::getValue('site_name', config('app.name')).' today';

            $plans = User::freeMembershipEnabled() ? collect() : MembershipPlan::where('is_active', true)->where('price_inr', '>', 0)
                ->orderBy('sort_order')->get(['plan_name', 'price_inr', 'duration_months']);
            if ($plans->isNotEmpty()) {
                $hints['plans'] = $plans->map(fn ($p) => $p->plan_name.' ₹'.number_format((int) $p->price_inr)
                    .($p->duration_months ? ' ('.$p->duration_months.' '.Str::plural('month', (int) $p->duration_months).')' : ''))->implode(' · ');
                $code = trim((string) SiteSetting::getValue('offer_banner_coupon_code', ''));
                if (SiteSetting::getValue('offer_banner_enabled', '0') === '1' && $code !== '') {
                    $offer = trim((string) SiteSetting::getValue('offer_banner_discount_text', ''));
                    $hints['plans'] .= ' · code '.$code.($offer !== '' ? ': '.$offer : '');
                }
            }
        } catch (\Throwable $e) {
            report($e);   // a hint must never stop the box from opening
        }

        return $hints;
    }

    /** The member's follow-up still to do (at most one: a new one replaces the old date). */
    public static function pendingFollowUp(Profile $profile): ?ProfileNote
    {
        return ProfileNote::where('profile_id', $profile->getKey())->pendingFollowUp()->orderBy('follow_up_date')->first();
    }

    /**
     * Record one contact and return the message to show. $data, as the box sends it:
     * type; for a call outcome, tasks[], details_checked, sent_whatsapp, at (call back);
     * for a WhatsApp whatsapp_kind; note; date ("call again / follow up on");
     * complete_follow_up.
     */
    public static function log(User $staff, Profile $profile, array $data): string
    {
        $type = (string) ($data['type'] ?? '');
        if (! array_key_exists($type, self::TYPES)) {
            throw new DomainException('Pick what it was: a call, WhatsApp, email, a meeting or other.');
        }
        $note = Str::limit(trim((string) ($data['note'] ?? '')), self::NOTE_MAX, '');
        $date = self::day($data['date'] ?? null);

        return DB::transaction(function () use ($staff, $profile, $data, $type, $note, $date) {
            // the follow-up first: a new date on this note then starts a fresh one
            $closed = ! empty($data['complete_follow_up']) ? self::completeFollowUp($staff, $profile) : false;

            $message = match ($type) {
                'call' => self::call($staff, $profile, $data, $note, $date),
                'whatsapp' => self::whatsapp($staff, $profile, (string) ($data['whatsapp_kind'] ?? ''), $note, $date),
                default => self::plain($staff, $profile, $type, $note, $date),
            };

            return $message.($closed ? ' Follow-up marked done.' : '');
        });
    }

    private static function call(User $staff, Profile $profile, array $data, string $note, ?Carbon $date): string
    {
        $outcome = (string) ($data['outcome'] ?? '');
        if (! array_key_exists($outcome, self::OUTCOMES)) {
            throw new DomainException('Pick what happened on the call.');
        }
        $at = $outcome === 'call_back' ? self::moment($data['at'] ?? null) : null;

        // a new member's verification call: the calls list follows the call
        if ($row = self::workableCall($staff, $profile)) {
            $message = match ($outcome) {
                'spoke' => VerificationCalls::finishCall($staff, $row, (array) ($data['tasks'] ?? []), $note, $date),
                'call_back' => VerificationCalls::record($staff, $row, 'call_back', $note, $at),
                default => VerificationCalls::record($staff, $row, $outcome, $note, null, $date),
            };
            if ($outcome === 'no_answer' && ! empty($data['sent_whatsapp'])) {
                self::whatsappNote($staff, $profile, 'missed', '', null);
                // she has sent it already: no "send the WhatsApp message too"
                $message = str_replace(' Send the WhatsApp message too.', '', $message).' The WhatsApp message is noted too.';
            }

            return $message;
        }

        if (in_array($outcome, self::LIST_ONLY, true)) {
            throw new DomainException('"'.self::OUTCOMES[$outcome].'" is only for a new member\'s verification call. Pick "Spoke to them" and write what they said.');
        }
        if ($outcome === 'call_back' && (! $at || $at->lte(now()))) {
            throw new DomainException('Pick when to call back: a time later than now.');
        }

        // any other call: a note in the member's history
        $checked = $outcome === 'spoke' && ! empty($data['details_checked']);
        $text = match ($outcome) {
            'spoke' => 'Call: spoke to them.'.($checked ? ' Went through their details with them.' : ''),
            'no_answer' => 'Call: no answer.',
            'call_back' => 'Call: busy, call back on '.$at->displayTz()->format('j M, g:i A').'.',
            default => 'Call: wrong number.',
        };
        ProfileNote::create([
            'profile_id' => $profile->getKey(),
            'admin_user_id' => $staff->id,
            'note_type' => 'call',
            'outcome' => $outcome,
            'note' => trim($text.' '.$note),
            'follow_up_date' => ($outcome === 'call_back' ? $at->displayTz() : $date)?->toDateString(),
        ]);
        if ($checked) {
            // the "Details checked" column and filter on All Members
            DB::table('profiles')->where('id', $profile->getKey())->update(['details_checked_at' => now(), 'details_checked_by' => $staff->id]);
            StaffActionLog::record('member_details_checked', $profile->getKey(), ['fields_changed' => 0]);
        }
        if ($outcome === 'no_answer' && ! empty($data['sent_whatsapp'])) {
            self::whatsappNote($staff, $profile, 'missed', '', null);
        }

        $open = self::openCall($profile);

        return 'Call noted in the member\'s history.'
            .($open && $open->caller ? ' Their verification call is given to '.$open->caller->name.', so it stays on that list.' : '');
    }

    private static function whatsapp(User $staff, Profile $profile, string $kind, string $note, ?Carbon $date): string
    {
        if (! array_key_exists($kind, self::WHATSAPP)) {
            throw new DomainException('Pick what you sent on WhatsApp.');
        }
        if ($kind === 'other' && $note === '') {
            throw new DomainException('Write what you sent.');
        }
        self::whatsappNote($staff, $profile, $kind, $note, $date);

        return 'WhatsApp noted in the member\'s history.';
    }

    private static function whatsappNote(User $staff, Profile $profile, string $kind, string $note, ?Carbon $date): void
    {
        $text = match ($kind) {
            'missed' => 'Sent the "we tried to call you" message on WhatsApp.',
            // the Members Without Photo list reads this prefix as "asked"
            'photo' => MembersWithoutPhoto::NOTE_PREFIX.' asked on WhatsApp to send a profile photo.',
            'link' => AppOnlyMode::on() ? 'Sent the app download link on WhatsApp.' : 'Sent the search or community link on WhatsApp.',
            default => '',
        };
        ProfileNote::create([
            'profile_id' => $profile->getKey(),
            'admin_user_id' => $staff->id,
            'note_type' => 'whatsapp',
            'note' => trim($text.' '.$note),
            'follow_up_date' => $date?->toDateString(),
        ]);
    }

    private static function plain(User $staff, Profile $profile, string $type, string $note, ?Carbon $date): string
    {
        if ($note === '') {
            throw new DomainException('Write what happened.');
        }
        ProfileNote::create([
            'profile_id' => $profile->getKey(),
            'admin_user_id' => $staff->id,
            'note_type' => $type,
            'note' => $note,
            'follow_up_date' => $date?->toDateString(),
        ]);

        return 'Noted in the member\'s history.';
    }

    /** Mark the member's open follow-up done (by this staff member). */
    private static function completeFollowUp(User $staff, Profile $profile): bool
    {
        return ProfileNote::where('profile_id', $profile->getKey())->pendingFollowUp()
            ->update(['follow_up_completed_at' => now(), 'follow_up_completed_by' => $staff->id]) > 0;
    }

    /** A "call again / follow up on" day (Y-m-d from the date picker), today or later. */
    private static function day(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }
        $day = rescue(fn () => Carbon::parse((string) $value, StaffActivity::tz())->startOfDay(), null, false);
        if (! $day || $day->lt(now(StaffActivity::tz())->startOfDay())) {
            throw new DomainException('Pick today or a later day to call again.');
        }

        return $day;
    }

    /** A call-back time from the date-time picker (stored in the app's time zone). */
    private static function moment(mixed $value): ?Carbon
    {
        return blank($value) ? null : rescue(fn () => Carbon::parse((string) $value, config('app.timezone')), null, false);
    }
}
