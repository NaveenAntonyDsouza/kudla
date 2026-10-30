<?php

namespace App\Support;

use App\Models\ContactSubmission;
use App\Models\Interest;
use App\Models\Profile;
use App\Models\ProfileNote;
use App\Models\ProfilePhoto;
use App\Models\SiteSetting;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The owner's weekly scorecard (admin audit, Oct 2026): "is it working", not
 * "how many". Each measure for this week (the last 7 days) and the week
 * before; a few that can only be read as they stand today have no last week.
 * Interest rates use the Engagement Reports definitions: answered = accepted
 * or declined, of interests not withdrawn; accepted, of those answered.
 */
class Scorecard
{
    /**
     * @return list<array{key: string, label: string, how: string, this: int|float|null, last: int|float|null, format: string, money: bool, note?: string}>
     */
    public static function rows(?Carbon $now = null): array
    {
        $now ??= now();
        $thisWeek = [$now->copy()->subDays(7), $now];
        $lastWeek = [$now->copy()->subDays(14), $now->copy()->subDays(7)];

        $rows = [];
        $add = function (string $key, string $label, string $how, \Closure $measure, string $format = 'count', bool $money = false, bool $weekly = true) use (&$rows, $thisWeek, $lastWeek) {
            try {
                $rows[] = [
                    'key' => $key, 'label' => $label, 'how' => $how, 'format' => $format, 'money' => $money,
                    'this' => $measure(...$thisWeek),
                    'last' => $weekly ? $measure(...$lastWeek) : null,
                ];
            } catch (\Throwable) {
                // a table this site doesn't have: leave the measure out
            }
        };

        $joined = fn (Carbon $from, Carbon $to) => Profile::withTrashed()->whereNotNull('full_name')->where('created_at', '>=', $from)->where('created_at', '<', $to);
        $sent = fn (Carbon $from, Carbon $to) => Interest::where('created_at', '>=', $from)->where('created_at', '<', $to)->where('status', '!=', 'cancelled');

        $add('signups', 'Sign-ups', 'New member accounts', fn ($f, $t) => $joined($f, $t)->count());
        $add('finished', 'Finished registration', 'Of that week\'s sign-ups, finished every step', fn ($f, $t) => self::pct($joined($f, $t)->where('onboarding_completed', true)->count(), $joined($f, $t)->count()), 'percent');
        $add('photo', 'New members with a photo', 'Of that week\'s sign-ups, have an approved photo now', fn ($f, $t) => self::pct(
            ProfilePhoto::where('approval_status', 'approved')->whereIn('profile_id', $joined($f, $t)->select('id'))->distinct()->count('profile_id'),
            $joined($f, $t)->count()
        ), 'percent');
        $add('active7', 'Members active, last 7 days', 'Live profiles used the site in the last 7 days (today\'s figure)', fn ($f, $t) => self::active(7), weekly: false);
        $add('active30', 'Members active, last 30 days', 'Live profiles used the site in the last 30 days (today\'s figure)', fn ($f, $t) => self::active(30), weekly: false);
        $add('interests', 'Interests sent', 'Sent that week (not withdrawn)', fn ($f, $t) => $sent($f, $t)->count());
        $add('answered', 'Answer rate', 'Of that week\'s interests, answered so far (recent weeks are still being answered)', fn ($f, $t) => self::pct($sent($f, $t)->whereIn('status', ['accepted', 'declined'])->count(), $sent($f, $t)->count()), 'percent');
        $add('accepted', 'Acceptance rate', 'Of that week\'s answered interests, accepted', fn ($f, $t) => self::pct($sent($f, $t)->where('status', 'accepted')->count(), $sent($f, $t)->whereIn('status', ['accepted', 'declined'])->count()), 'percent');
        $add('connections', 'Connections', 'Interests accepted that week', fn ($f, $t) => DB::table('interest_replies')->where('reply_type', 'accept')->where('created_at', '>=', $f)->where('created_at', '<', $t)->count());
        $add('paid', 'Paid plans bought', 'Payments received that week', fn ($f, $t) => Subscription::where('payment_status', 'paid')->where('created_at', '>=', $f)->where('created_at', '<', $t)->count(), money: true);
        $add('revenue', 'Revenue', 'Paid that week, in rupees', fn ($f, $t) => (int) round(Subscription::where('payment_status', 'paid')->where('created_at', '>=', $f)->where('created_at', '<', $t)->sum('amount') / 100), 'rupees', money: true);
        $add('backlog', 'Support backlog', 'Contact messages not answered + follow-ups overdue (today\'s figure)', fn ($f, $t) => ContactSubmission::where('status', 'new')->count()
            + ProfileNote::pendingFollowUp()->whereHas('profile')->where('follow_up_date', '<', today())->count(), weekly: false);

        return $rows;
    }

    public static function freeMode(): bool
    {
        return SiteSetting::getValue('free_membership_enabled', '0') === '1';
    }

    private static function active(int $days): int
    {
        return Profile::where('is_active', true)->where('is_approved', true)
            ->whereHas('user', fn ($q) => $q->whereNull('staff_role_id')->where('last_login_at', '>=', now()->subDays($days)))
            ->count();
    }

    private static function pct(int $part, int $whole): ?int
    {
        return $whole > 0 ? (int) round($part / $whole * 100) : null;
    }
}
