{{-- Funnel & Retention (App\Support\MemberFunnel). Inline styles only: custom
     admin views get no Tailwind build. --}}
@php
    $pct = fn ($v) => $v === null ? '—' : $v . '%';
    $bar = fn (?int $v) => '<div style="height:.5rem;border-radius:9999px;background:rgba(127,127,127,.18);overflow:hidden;min-width:6rem;"><div style="height:100%;width:' . max(0, min(100, (int) $v)) . '%;background:rgba(139,29,145,.75);"></div></div>';
    $joined = $steps[0]['count'] ?? 0;
@endphp

<x-filament-panels::page>
    <x-filament::section
        heading="From sign-up to connection"
        description="Members who joined in the period, and how far they have got so far. The biggest drop is where help pays off most.">
        @include('filament.partials.period-picker')

        @if ($joined === 0)
            <p style="margin:0;opacity:.8;">Nobody signed up in this period.</p>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                    <thead>
                        <tr style="text-align:left;opacity:.7;font-size:.75rem;text-transform:uppercase;letter-spacing:.03em;">
                            <th style="padding:.4rem .5rem .4rem 0;font-weight:600;">Step</th>
                            <th style="padding:.4rem .5rem;font-weight:600;text-align:right;">Members</th>
                            <th style="padding:.4rem 0 .4rem .5rem;font-weight:600;">Of those who signed up</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($steps as $s)
                            <tr style="border-top:1px solid rgba(127,127,127,.2);">
                                <td style="padding:.6rem .5rem .6rem 0;font-weight:600;">{{ $s['label'] }}</td>
                                <td style="padding:.6rem .5rem;text-align:right;font-weight:700;font-size:1rem;">{{ number_format($s['count']) }}</td>
                                <td style="padding:.6rem 0 .6rem .5rem;">
                                    <div style="display:flex;align-items:center;gap:.6rem;">
                                        <div style="flex:1;">{!! $bar($s['key'] === 'joined' ? 100 : $s['of_joined']) !!}</div>
                                        <span style="min-width:2.5rem;text-align:right;">{{ $s['key'] === 'joined' ? '100%' : $pct($s['of_joined']) }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p style="margin:1rem 0 0;font-size:.8rem;opacity:.7;">Each step is counted on its own: a member can connect without a photo, or pay without connecting. Counts members who have deleted their account since, too: they did sign up. "Bought a paid plan" stays low while the site runs in free mode.</p>
        @endif
    </x-filament::section>

    <x-filament::section
        heading="Still around after 1 and 3 months"
        description="Each month's new members: how many used the site again 30 and 90 days after joining. Newest month first.">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                <thead>
                    <tr style="text-align:left;opacity:.7;font-size:.75rem;text-transform:uppercase;letter-spacing:.03em;">
                        <th style="padding:.4rem .5rem .4rem 0;font-weight:600;">Joined in</th>
                        <th style="padding:.4rem .5rem;font-weight:600;text-align:right;">New members</th>
                        <th style="padding:.4rem .5rem;font-weight:600;text-align:right;">Finished registration</th>
                        <th style="padding:.4rem .5rem;font-weight:600;">Back after 1 month</th>
                        <th style="padding:.4rem 0 .4rem .5rem;font-weight:600;">Back after 3 months</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($months as $m)
                        <tr style="border-top:1px solid rgba(127,127,127,.2);">
                            <td style="padding:.55rem .5rem .55rem 0;font-weight:600;white-space:nowrap;">{{ $m['label'] }}</td>
                            <td style="padding:.55rem .5rem;text-align:right;">{{ number_format($m['joined']) }}</td>
                            <td style="padding:.55rem .5rem;text-align:right;">{{ $pct($m['finished']) }}</td>
                            @foreach (['after_1m' => 'eligible_1m', 'after_3m' => 'eligible_3m'] as $col => $eligible)
                                <td style="padding:.55rem .5rem;">
                                    @if ($m[$col] === null)
                                        <span style="opacity:.6;font-size:.8rem;">{{ $m['joined'] > 0 ? 'too early' : '—' }}</span>
                                    @else
                                        <div style="display:flex;align-items:center;gap:.6rem;">
                                            <div style="flex:1;">{!! $bar($m[$col]) !!}</div>
                                            <span style="min-width:2.5rem;text-align:right;">{{ $m[$col] }}%</span>
                                        </div>
                                        @if ($m[$eligible] < $m['joined'])
                                            <div style="font-size:.7rem;opacity:.6;">of the {{ $m[$eligible] }} who joined long enough ago</div>
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p style="margin:1rem 0 0;font-size:.8rem;opacity:.7;">
            "Back" = used the site again on or after that day. Every visit counts since 2 Oct 2026; before that only logins were recorded,
            so months before October may read a little low.
        </p>
    </x-filament::section>
</x-filament-panels::page>
