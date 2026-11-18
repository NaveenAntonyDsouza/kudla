<?php

namespace App\Support;

use App\Models\MemberEvent;
use App\Models\Profile;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The numbers behind Money → Payment Funnel and the weekly review (18 Nov 2026).
 *
 * Payment attempts come from `subscriptions` (one row per checkout: pending, paid,
 * failed), which exist since the gateways went in; the steps before and around them
 * (plans page, plan chosen, coupon tries, payment window closed, paywall moments) come
 * from `member_events`, recorded since 18 Nov 2026 — so those rows read 0 before then.
 */
class PaymentFunnel
{
    public function __construct(public int $days = 7)
    {
        $this->days = max(1, min(90, $days));
    }

    public function since(): Carbon
    {
        return now()->subDays($this->days);
    }

    /**
     * Where the four-step row starts: the period start, or the first recorded step if
     * that is later. Steps 1–2 exist only from 18 Nov 2026, so counting steps 3–4 from
     * further back made the row read "0 → 0 → 6 → 2".
     */
    public function stepsFrom(): Carbon
    {
        $first = MemberEvent::min('created_at');

        return $first ? max($this->since(), Carbon::parse($first)) : now();
    }

    /** Gateway slugs as their makers write them. */
    public static function gatewayName(?string $slug): string
    {
        return ['phonepe' => 'PhonePe', 'razorpay' => 'Razorpay', 'paypal' => 'PayPal', 'stripe' => 'Stripe', 'paytm' => 'Paytm'][$slug] ?? ucfirst((string) $slug);
    }

    private function events(string $event)
    {
        return MemberEvent::where('event', $event)->where('created_at', '>=', $this->since());
    }

    private function checkouts()
    {
        return Subscription::where('created_at', '>=', $this->since())->where('amount', '>', 0);
    }

    /** Members at each step (each member counted once per step). */
    public function steps(): array
    {
        $from = $this->stepsFrom();
        $checkouts = Subscription::where('created_at', '>=', $from)->where('amount', '>', 0);
        $events = fn (string $e) => MemberEvent::where('event', $e)->where('created_at', '>=', $from);

        return [
            ['Saw the plans page', $events(MemberEvents::PLANS_VIEWED)->whereNotNull('user_id')->distinct()->count('user_id')],
            ['Pressed a plan', $events(MemberEvents::PLAN_CHOSEN)->distinct()->count('user_id')],
            ['Opened the payment', (clone $checkouts)->distinct()->count('user_id')],
            ['Paid', (clone $checkouts)->where('payment_status', 'paid')->distinct()->count('user_id')],
        ];
    }

    public function visitorPlanViews(): int
    {
        return (clone $this->events(MemberEvents::PLANS_VIEWED))->whereNull('user_id')->count();
    }

    /** Checkouts by gateway: started / paid / failed / unfinished, and revenue. */
    public function byGateway(): array
    {
        $rows = [];
        foreach ((clone $this->checkouts())->get(['gateway', 'payment_status', 'amount']) as $s) {
            $g = self::gatewayName($s->gateway ?: 'unknown');
            $rows[$g] ??= ['started' => 0, 'paid' => 0, 'failed' => 0, 'unfinished' => 0, 'revenue' => 0];
            $rows[$g]['started']++;
            match ($s->payment_status) {
                'paid' => [$rows[$g]['paid']++, $rows[$g]['revenue'] += (int) round($s->amount / 100)],
                'failed' => $rows[$g]['failed']++,
                'pending' => $rows[$g]['unfinished']++,
                default => null,
            };
        }

        return $rows;
    }

    /** Why payments failed, as the gateway (or its payment window) said. */
    public function failureReasons(): array
    {
        $reasons = [];
        foreach ((clone $this->checkouts())->where('payment_status', 'failed')->get(['gateway', 'gateway_metadata']) as $s) {
            $r = trim((string) ($s->gateway_metadata['failure_reason'] ?? '')) ?: 'no reason given';
            $key = self::gatewayName($s->gateway ?: '?').': '.$r;
            $reasons[$key] = ($reasons[$key] ?? 0) + 1;
        }
        // reasons the payment window reported for checkouts not (yet) marked failed
        foreach ((clone $this->events(MemberEvents::PAYMENT_FAILED))->get(['subject', 'data']) as $e) {
            if (($e->data['from'] ?? '') !== 'browser') {
                continue;
            }
            $key = self::gatewayName($e->subject ?: '?').': '.(($e->data['reason'] ?? '') ?: 'no reason given').' (in the payment window)';
            $reasons[$key] = ($reasons[$key] ?? 0) + 1;
        }
        arsort($reasons);

        return $reasons;
    }

    /** Coupon codes typed: tries, worked, did not work (with the last reason). */
    public function coupons(): array
    {
        $out = [];
        foreach ((clone $this->events(MemberEvents::COUPON_TRIED))->get(['subject', 'data']) as $e) {
            $code = $e->subject ?: '(blank)';
            $out[$code] ??= ['tries' => 0, 'worked' => 0, 'failed' => 0, 'reason' => null, 'paid' => 0];
            $out[$code]['tries']++;
            if ($e->data['valid'] ?? false) {
                $out[$code]['worked']++;
            } else {
                $out[$code]['failed']++;
                $out[$code]['reason'] = $e->data['reason'] ?? $out[$code]['reason'];
            }
        }
        foreach ((clone $this->checkouts())->where('payment_status', 'paid')->whereNotNull('coupon_code')->pluck('coupon_code') as $code) {
            $out[$code] ??= ['tries' => 0, 'worked' => 0, 'failed' => 0, 'reason' => null, 'paid' => 0];
            $out[$code]['paid']++;
        }
        uasort($out, fn ($a, $b) => $b['tries'] <=> $a['tries']);

        return $out;
    }

    /** Paid features free members were stopped by: times, members, and how many of those members paid. */
    public function paywall(): array
    {
        $paidUsers = (clone $this->checkouts())->where('payment_status', 'paid')->pluck('user_id')->unique()->all();
        $out = [];
        foreach (MemberEvents::FEATURES as $feature => $label) {
            $q = (clone $this->events(MemberEvents::PAYWALL))->where('subject', $feature);
            $users = (clone $q)->whereNotNull('user_id')->distinct()->pluck('user_id')->all();
            $out[$feature] = [
                'label' => $label,
                'times' => (clone $q)->count(),
                'members' => count($users),
                'paid' => count(array_intersect($users, $paidUsers)),
            ];
        }

        return $out;
    }

    /**
     * Members worth a call: started a payment and did not pay (with their last failure
     * reason), and free members stopped by paid features 3+ times without paying.
     */
    public function toCall(): array
    {
        $started = (clone $this->checkouts())->orderBy('created_at')->get(['user_id', 'plan_name', 'gateway', 'payment_status', 'coupon_code', 'gateway_metadata', 'created_at', 'amount'])->groupBy('user_id');
        $unfinished = [];
        foreach ($started as $userId => $rows) {
            if ($rows->contains('payment_status', 'paid') || $this->hasActivePlan((int) $userId)) {
                continue;
            }
            $last = $rows->last();
            $profile = $this->profileOf((int) $userId);
            $unfinished[] = [
                'profile' => $profile,
                'tries' => $rows->count(),
                'plans' => $rows->pluck('plan_name')->unique()->implode(', '),
                'gateway' => $rows->pluck('gateway')->unique()->map(fn ($g) => self::gatewayName($g))->implode(', '),
                // a Call note after the last try: someone has already phoned them
                'called' => $profile ? \App\Models\ProfileNote::where('profile_id', $profile->id)->where('note_type', 'call')
                    ->where('created_at', '>=', $last->created_at)->max('created_at') : null,
                'coupon' => $rows->pluck('coupon_code')->filter()->unique()->implode(', '),
                'amount' => (int) round($last->amount / 100),
                'reason' => $rows->map(fn ($r) => $r->gateway_metadata['failure_reason'] ?? null)->filter()->last()
                    ?? ($last->payment_status === 'pending' ? 'left the payment unfinished' : 'failed, no reason given'),
                'last' => $last->created_at,
            ];
        }

        $already = collect($unfinished)->map(fn ($r) => $r['profile']?->user_id)->filter()->all();
        $keen = [];
        foreach ((clone $this->events(MemberEvents::PAYWALL))->whereNotNull('user_id')->get(['user_id', 'subject', 'created_at'])->groupBy('user_id') as $userId => $rows) {
            if ($rows->count() < 3 || in_array((int) $userId, $already, true) || $this->hasActivePlan((int) $userId)) {
                continue;
            }
            $keen[] = [
                'profile' => $this->profileOf((int) $userId),
                'times' => $rows->count(),
                'features' => $rows->pluck('subject')->countBy()->map(fn ($n, $f) => (MemberEvents::FEATURES[$f] ?? $f).' ×'.$n)->implode(', '),
                'last' => $rows->max('created_at'),
            ];
        }
        usort($keen, fn ($a, $b) => $b['times'] <=> $a['times']);

        return ['unfinished' => $unfinished, 'keen' => $keen];
    }

    private function hasActivePlan(int $userId): bool
    {
        return \App\Models\UserMembership::where('user_id', $userId)->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->whereHas('plan', fn ($p) => $p->where('price_inr', '>', 0))
            ->exists();
    }

    private function profileOf(int $userId): ?Profile
    {
        return Profile::withTrashed()->where('user_id', $userId)->first(['id', 'user_id', 'matri_id', 'full_name', 'deleted_at']);
    }

    /** Unfinished payments for the Work Queue: started 30+ minutes ago, not paid, no paid one since. */
    public static function unfinishedQuery()
    {
        return Subscription::query()
            ->where('payment_status', 'pending')
            ->where('amount', '>', 0)
            ->where('created_at', '>=', now()->subDays(7))
            ->where('created_at', '<=', now()->subMinutes(30))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('subscriptions as later')
                ->whereColumn('later.user_id', 'subscriptions.user_id')
                ->where('later.payment_status', 'paid')
                ->whereColumn('later.created_at', '>=', 'subscriptions.created_at'));
    }
}
