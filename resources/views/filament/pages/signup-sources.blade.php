{{-- Sign-up Sources (App\Support\MemberFunnel::sources). Inline styles only:
     custom admin views get no Tailwind build. --}}
@php
    $pct = fn ($v) => $v === null ? '—' : $v . '%';
    $total = collect($sources)->sum('joined');
@endphp

<x-filament-panels::page>
    <x-filament::section
        heading="How did you hear about us?"
        description="The answer members give when they sign up, and how members from each source got on. Sources that bring members who finish and connect are the ones to spend on.">
        @include('filament.partials.period-picker')

        @if ($total === 0)
            <p style="margin:0;opacity:.8;">Nobody signed up in this period.</p>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                    <thead>
                        <tr style="text-align:left;opacity:.7;font-size:.75rem;text-transform:uppercase;letter-spacing:.03em;">
                            <th style="padding:.4rem .5rem .4rem 0;font-weight:600;">Source</th>
                            <th style="padding:.4rem .5rem;font-weight:600;text-align:right;">Sign-ups</th>
                            <th style="padding:.4rem .5rem;font-weight:600;">Share</th>
                            <th style="padding:.4rem .5rem;font-weight:600;text-align:right;">Finished</th>
                            <th style="padding:.4rem .5rem;font-weight:600;text-align:right;">With photo</th>
                            <th style="padding:.4rem .5rem;font-weight:600;text-align:right;">Connected</th>
                            <th style="padding:.4rem 0 .4rem .5rem;font-weight:600;text-align:right;">Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sources as $s)
                            <tr style="border-top:1px solid rgba(127,127,127,.2);{{ in_array($s['source'], ['Not answered', 'Added by staff'], true) ? 'opacity:.75;' : '' }}">
                                <td style="padding:.55rem .5rem .55rem 0;font-weight:600;">{{ $s['source'] }}</td>
                                <td style="padding:.55rem .5rem;text-align:right;font-weight:700;">{{ number_format($s['joined']) }}</td>
                                <td style="padding:.55rem .5rem;min-width:8rem;">
                                    <div style="display:flex;align-items:center;gap:.5rem;">
                                        <div style="flex:1;height:.5rem;border-radius:9999px;background:rgba(127,127,127,.18);overflow:hidden;">
                                            <div style="height:100%;width:{{ (int) $s['share'] }}%;background:rgba(139,29,145,.75);"></div>
                                        </div>
                                        <span style="min-width:2.3rem;text-align:right;">{{ $pct($s['share']) }}</span>
                                    </div>
                                </td>
                                <td style="padding:.55rem .5rem;text-align:right;">{{ $pct($s['finished']) }}</td>
                                <td style="padding:.55rem .5rem;text-align:right;">{{ $pct($s['photo']) }}</td>
                                <td style="padding:.55rem .5rem;text-align:right;">{{ $pct($s['connected']) }}</td>
                                <td style="padding:.55rem 0 .55rem .5rem;text-align:right;">{{ number_format($s['paid']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p style="margin:1rem 0 0;font-size:.8rem;opacity:.7;">
                Finished, With photo and Connected are shares of that source's own sign-ups. "Not answered": the question is optional.
                The answer list is in Site &amp; Content → Lists &amp; Options → Reference Data ("How Did You Hear Options").
            </p>
        @endif
    </x-filament::section>

    @if (count($devices) > 0)
        <x-filament::section heading="Signed up on" description="The device used for registration.">
            <div style="display:flex;gap:1.5rem;flex-wrap:wrap;font-size:.9rem;">
                @foreach ($devices as $d)
                    <div><span style="font-weight:700;font-size:1.1rem;">{{ $pct($d['share']) }}</span> <span style="opacity:.75;">{{ $d['device'] }} ({{ number_format($d['joined']) }})</span></div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
