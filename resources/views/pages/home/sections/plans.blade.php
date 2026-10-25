{{--
    Membership plans preview (Oct 2026; Admin → Homepage Content → Garland &
    Harmony sections). Live from Membership Plans (active plans, in their own
    order): name, price, struck-out price, length and features as set there.
    In Free Membership mode (Settings → Membership Mode) everyone already has
    full access and /membership-plans says so, so this shows the same message
    instead of prices.
--}}
@php
    $freeMode = \App\Models\User::freeMembershipEnabled();
    $homePlans = $freeMode ? collect() : \App\Models\MembershipPlan::where('is_active', true)->orderBy('sort_order')->orderBy('price_inr')->get();
@endphp
@if($freeMode)
<section class="g-section g-lav">
    <div class="g-wrap" style="text-align: center;">
        <x-dynamic-component :component="$divider" />
        <h2 class="g-h2">Everything Is Free Right Now</h2>
        <p class="g-lead">Every feature is free for all members. No plan or payment needed.</p>
        <div style="max-width: 34rem; margin: 32px auto 0; background: #fff; border: 1px solid var(--g-line); border-radius: 18px; padding: 24px 22px; text-align: left;">
            <ul style="list-style: none; margin: 0; padding: 0; display: grid; gap: 10px;">
                @foreach([
                    'Create your profile and add photos',
                    'Search and view profiles',
                    'Send up to ' . \App\Services\InterestService::FREE_DAILY_LIMIT . ' interests a day, with your own message',
                    'Chat, and see each other\'s phone and WhatsApp once you both accept',
                ] as $freeItem)
                    <li style="display: flex; gap: 9px; align-items: flex-start; font-size: 0.95rem; line-height: 1.45; color: var(--g-ink);"><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" style="flex: none; margin-top: 3px; color: var(--g-a-ink);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M5 13l4 4L19 7"/></svg>{{ $freeItem }}</li>
                @endforeach
            </ul>
            @guest
                <a href="/register" class="g-btn g-btn-primary" style="width: 100%; margin-top: 20px;">Register Free</a>
            @endguest
        </div>
    </div>
</section>
@elseif($homePlans->count())
<style>
    .home-garland .g-plans { display: grid; grid-template-columns: 1fr; gap: 18px; margin-top: 44px; align-items: stretch; }
    @media (min-width: 640px) { .home-garland .g-plans { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .home-garland .g-plans.n-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } .home-garland .g-plans.n-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .home-garland .g-plan { position: relative; display: flex; flex-direction: column; background: #fff; border: 1px solid var(--g-line); border-radius: 18px; padding: 26px 22px 22px; }
    .home-garland .g-plan.popular { border: 2px solid var(--g-p); box-shadow: 0 22px 44px -24px rgba(40,6,50,.45); }
    .home-garland .g-plan-badge { position: absolute; top: -13px; left: 50%; transform: translateX(-50%); white-space: nowrap; padding: 4px 14px; border-radius: 999px; background: linear-gradient(180deg, #f7b51e, var(--g-a)); color: var(--g-deep); font-size: 0.72rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
    .home-garland .g-plan h3 { margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--g-plum); }
    .home-garland .g-plan .price { margin-top: 12px; display: flex; align-items: baseline; flex-wrap: wrap; gap: 6px 8px; }
    .home-garland .g-plan .price b { font-family: var(--brand-font-serif, Georgia, serif); font-size: 2rem; font-weight: 700; color: var(--g-ink); font-variant-numeric: lining-nums; }
    .home-garland .g-plan .price s { font-size: 0.9rem; color: var(--g-muted); }
    .home-garland .g-plan .price span { font-size: 0.85rem; color: var(--g-muted); }
    .home-garland .g-plan ul { list-style: none; margin: 18px 0 22px; padding: 0; display: grid; gap: 9px; flex: 1; }
    .home-garland .g-plan li { display: flex; gap: 9px; align-items: flex-start; font-size: 0.9rem; line-height: 1.45; color: var(--g-ink); }
    .home-garland .g-plan li svg { flex: none; margin-top: 2px; color: var(--g-a-ink); }
    .home-garland .g-plan .g-btn { width: 100%; }
</style>
<section class="g-section g-lav">
    <div class="g-wrap">
        <x-dynamic-component :component="$divider" />
        <h2 class="g-h2">Membership Plans</h2>
        @php $plansSoon = \App\Support\PaidPlans::comingSoon(); @endphp
        <p class="g-lead">{{ $plansSoon ? 'Start free. Paid plans are coming soon.' : 'Start free. Upgrade when you are ready to contact the families you like.' }}</p>

        <div class="g-plans n-{{ min($homePlans->count(), 4) }}">
            @foreach($homePlans as $plan)
                <div class="g-plan {{ $plan->is_popular ? 'popular' : '' }}">
                    @if($plan->is_popular)
                        <span class="g-plan-badge">Most popular</span>
                    @endif
                    <h3>{{ $plan->plan_name }}</h3>
                    <div class="price">
                        @if((int) $plan->price_inr === 0)
                            <b>Free</b>
                        @else
                            <b>&#8377;{{ number_format((int) $plan->price_inr) }}</b>
                            @if($plan->strike_price_inr && $plan->strike_price_inr > $plan->price_inr)
                                <s>&#8377;{{ number_format((int) $plan->strike_price_inr) }}</s>
                            @endif
                            @if($plan->duration_months)
                                <span>for {{ $plan->duration_months }} {{ \Illuminate\Support\Str::plural('month', (int) $plan->duration_months) }}</span>
                            @endif
                        @endif
                    </div>
                    @php
                        // features are a list; some older plans saved it as JSON text
                        $features = $plan->features;
                        if (is_string($features)) {
                            $decoded = json_decode($features, true);
                            $features = is_array($decoded) ? $decoded : [$features];
                        }
                    @endphp
                    <ul>
                        @foreach(array_slice(array_filter((array) $features, 'is_string'), 0, 6) as $feature)
                            <li><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M5 13l4 4L19 7"/></svg>{{ $feature }}</li>
                        @endforeach
                    </ul>
                    @if((int) $plan->price_inr === 0)
                        <a href="/register" class="g-btn g-btn-outline">Register Free</a>
                    @elseif($plansSoon)
                        <span class="g-btn g-btn-outline" style="opacity: .6; cursor: default;">Coming soon</span>
                    @else
                        <a href="{{ route('membership.index') }}" class="g-btn {{ $plan->is_popular ? 'g-btn-primary' : 'g-btn-outline' }}">Choose {{ $plan->plan_name }}</a>
                    @endif
                </div>
            @endforeach
        </div>

        <div style="text-align: center; margin-top: 26px;">
            <a href="{{ route('membership.index') }}" class="g-link">Compare all plans &rarr;</a>
        </div>
    </div>
</section>
@endif
