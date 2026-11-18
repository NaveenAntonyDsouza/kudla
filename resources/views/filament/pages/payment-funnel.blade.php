{{-- Money → Payment Funnel (18 Nov 2026). Inline styles only: custom panel views get no CSS build. --}}
@php
    $box = 'background: rgba(128,128,128,0.05); border: 1px solid rgba(128,128,128,0.2); border-radius: 8px; padding: 16px;';
    $h = 'font-size: 14px; font-weight: 600; margin: 0 0 10px;';
    $th = 'text-align: left; padding: 6px 8px; font-size: 12px; opacity: .7; font-weight: 600; border-bottom: 1px solid rgba(128,128,128,0.2);';
    $td = 'padding: 6px 8px; font-size: 13px; border-bottom: 1px solid rgba(128,128,128,0.1); vertical-align: top;';
    $muted = 'font-size: 12px; opacity: .65;';
    $top = max(1, (int) ($steps[0][1] ?? 0));
@endphp
<x-filament-panels::page>
    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px; justify-content: space-between;">
        <p style="{{ $muted }} margin: 0; max-width: 60rem;">
            From the plans page to a paid plan. Payment attempts go back to the start; the steps around them
            (plans page, plan pressed, coupon codes, closed payment window, paid features that stopped a free member)
            are recorded from 18 Nov 2026. Each member is counted once per step.
        </p>
        <label style="font-size: 13px;">Period
            <select wire:model.live="days" style="margin-left: 6px; border-radius: 6px; padding: 4px 8px; font-size: 13px; background: transparent; border: 1px solid rgba(128,128,128,.35);">
                <option value="1">Today and yesterday</option>
                <option value="7">Last 7 days</option>
                <option value="30">Last 30 days</option>
                <option value="90">Last 90 days</option>
            </select>
        </label>
    </div>

    {{-- The steps --}}
    <div style="{{ $box }}">
        <p style="{{ $h }}">Members at each step</p>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px;">
            @foreach ($steps as $i => [$label, $n])
                <div style="padding: 12px; border-radius: 8px; background: rgba(128,128,128,0.06);">
                    <div style="{{ $muted }}">{{ $i + 1 }}. {{ $label }}</div>
                    <div style="font-size: 26px; font-weight: 700; margin-top: 4px;">{{ number_format($n) }}</div>
                    @if ($i > 0)
                        <div style="{{ $muted }}">{{ $steps[$i - 1][1] > 0 ? round($n / $steps[$i - 1][1] * 100) . '% of the step before' : '—' }}</div>
                    @endif
                </div>
            @endforeach
        </div>
        <p style="{{ $muted }} margin: 10px 0 0;">Counted from {{ $stepsFrom->displayTz()->format('d M Y') }}{{ $stepsFrom->gt(now()->subDays($days)->addMinute()) ? ', when recording of the first two steps began' : '' }}. Visitors who were not signed in opened the plans page {{ number_format($visitorViews) }} {{ \Illuminate\Support\Str::plural('time', $visitorViews) }}.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 16px;">
        {{-- Gateways --}}
        <div style="{{ $box }}">
            <p style="{{ $h }}">Payments by gateway</p>
            <table style="width: 100%; border-collapse: collapse;">
                <thead><tr><th style="{{ $th }}">Gateway</th><th style="{{ $th }}">Started</th><th style="{{ $th }}">Paid</th><th style="{{ $th }}">Failed</th><th style="{{ $th }}">Unfinished</th><th style="{{ $th }}">Revenue</th></tr></thead>
                <tbody>
                @forelse ($gateways as $g => $r)
                    <tr><td style="{{ $td }}">{{ $g }}</td><td style="{{ $td }}">{{ $r['started'] }}</td><td style="{{ $td }} color: #10B981; font-weight: 600;">{{ $r['paid'] }}</td><td style="{{ $td }} color: #EF4444;">{{ $r['failed'] }}</td><td style="{{ $td }} color: #F59E0B;">{{ $r['unfinished'] }}</td><td style="{{ $td }}">₹{{ number_format($r['revenue']) }}</td></tr>
                @empty
                    <tr><td colspan="6" style="{{ $td }} {{ $muted }}">No payments started in this period.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Failure reasons --}}
        <div style="{{ $box }}">
            <p style="{{ $h }}">Why payments failed</p>
            <table style="width: 100%; border-collapse: collapse;">
                <tbody>
                @forelse ($reasons as $reason => $n)
                    <tr><td style="{{ $td }}">{{ $reason }}</td><td style="{{ $td }} text-align: right; width: 40px;">{{ $n }}</td></tr>
                @empty
                    <tr><td style="{{ $td }} {{ $muted }}">No failed payments in this period.</td></tr>
                @endforelse
                </tbody>
            </table>
            <p style="{{ $muted }} margin: 8px 0 0;">The gateway's own words. Reasons are kept from 18 Nov 2026.</p>
        </div>

        {{-- Paywall --}}
        <div style="{{ $box }}">
            <p style="{{ $h }}">Paid features that stopped free members</p>
            <table style="width: 100%; border-collapse: collapse;">
                <thead><tr><th style="{{ $th }}">Feature</th><th style="{{ $th }}">Times</th><th style="{{ $th }}">Members</th><th style="{{ $th }}">Of them paid</th></tr></thead>
                <tbody>
                @foreach ($paywall as $r)
                    <tr><td style="{{ $td }}">{{ $r['label'] }}</td><td style="{{ $td }}">{{ $r['times'] }}</td><td style="{{ $td }}">{{ $r['members'] }}</td><td style="{{ $td }}">{{ $r['paid'] }}</td></tr>
                @endforeach
                </tbody>
            </table>
            <p style="{{ $muted }} margin: 8px 0 0;">Counted once a day per member and feature (for phone numbers, per profile). Shows which feature makes members want to pay.</p>
        </div>

        {{-- Coupons --}}
        <div style="{{ $box }}">
            <p style="{{ $h }}">Coupon codes typed</p>
            <table style="width: 100%; border-collapse: collapse;">
                <thead><tr><th style="{{ $th }}">Code</th><th style="{{ $th }}">Tries</th><th style="{{ $th }}">Worked</th><th style="{{ $th }}">Refused</th><th style="{{ $th }}">Paid with it</th></tr></thead>
                <tbody>
                @forelse ($coupons as $code => $r)
                    <tr>
                        <td style="{{ $td }} font-family: monospace;">{{ $code }}</td>
                        <td style="{{ $td }}">{{ $r['tries'] }}</td>
                        <td style="{{ $td }}">{{ $r['worked'] }}</td>
                        <td style="{{ $td }}">{{ $r['failed'] }}@if ($r['reason'])<div style="{{ $muted }}">{{ $r['reason'] }}</div>@endif</td>
                        <td style="{{ $td }}">{{ $r['paid'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="{{ $td }} {{ $muted }}">No coupon codes typed in this period.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- To call --}}
    <div style="{{ $box }}">
        <p style="{{ $h }}">Started a payment but did not pay — worth a call ({{ count($toCall['unfinished']) }})</p>
        <table style="width: 100%; border-collapse: collapse;">
            <thead><tr><th style="{{ $th }}">Member</th><th style="{{ $th }}">Plan</th><th style="{{ $th }}">Tries</th><th style="{{ $th }}">Gateway</th><th style="{{ $th }}">What happened</th><th style="{{ $th }}">Last try</th><th style="{{ $th }}">Called</th></tr></thead>
            <tbody>
            @forelse ($toCall['unfinished'] as $r)
                @php $url = \App\Filament\Resources\UserResource::profileWorkUrl($r['profile']); @endphp
                <tr>
                    <td style="{{ $td }}">@if ($url)<a href="{{ $url }}" style="color: var(--primary-600, #8B1D91); font-weight: 600;">{{ $r['profile']->matri_id }}</a>@else — @endif<div style="{{ $muted }}">{{ $r['profile']?->full_name }}</div></td>
                    <td style="{{ $td }}">{{ $r['plans'] }} · ₹{{ number_format($r['amount']) }}@if ($r['coupon'])<div style="{{ $muted }}">coupon {{ $r['coupon'] }}</div>@endif</td>
                    <td style="{{ $td }}">{{ $r['tries'] }}</td>
                    <td style="{{ $td }}">{{ $r['gateway'] }}</td>
                    <td style="{{ $td }}">{{ $r['reason'] }}</td>
                    <td style="{{ $td }} white-space: nowrap;">{{ $r['last']?->displayTz()->format('d M, H:i') }}</td>
                    <td style="{{ $td }} white-space: nowrap;">@if ($r['called'])<span style="color: #10B981; font-weight: 600;">✓ {{ \Illuminate\Support\Carbon::parse($r['called'])->displayTz()->format('d M') }}</span>@else<span style="color: #F59E0B;">Not yet</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="7" style="{{ $td }} {{ $muted }}">Nobody left a payment unfinished in this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="{{ $box }}">
        <p style="{{ $h }}">Free members stopped by paid features 3+ times — likely to buy ({{ count($toCall['keen']) }})</p>
        <table style="width: 100%; border-collapse: collapse;">
            <thead><tr><th style="{{ $th }}">Member</th><th style="{{ $th }}">Times</th><th style="{{ $th }}">What they tried</th><th style="{{ $th }}">Last</th></tr></thead>
            <tbody>
            @forelse ($toCall['keen'] as $r)
                @php $url = \App\Filament\Resources\UserResource::profileWorkUrl($r['profile']); @endphp
                <tr>
                    <td style="{{ $td }}">@if ($url)<a href="{{ $url }}" style="color: var(--primary-600, #8B1D91); font-weight: 600;">{{ $r['profile']->matri_id }}</a>@else — @endif<div style="{{ $muted }}">{{ $r['profile']?->full_name }}</div></td>
                    <td style="{{ $td }}">{{ $r['times'] }}</td>
                    <td style="{{ $td }}">{{ $r['features'] }}</td>
                    <td style="{{ $td }} white-space: nowrap;">{{ \Illuminate\Support\Carbon::parse($r['last'])->displayTz()->format('d M, H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="{{ $td }} {{ $muted }}">No one yet in this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
