{{-- Owner's weekly scorecard (App\Support\Scorecard). Inline styles only: custom
     admin views get no Tailwind build. --}}
@php
    $show = function ($value, string $format) {
        if ($value === null) {
            return '—';
        }
        return match ($format) {
            'percent' => $value . '%',
            'rupees' => '₹' . number_format($value),
            default => number_format($value),
        };
    };
    $change = function (array $r) {
        if ($r['this'] === null || $r['last'] === null) {
            return null;
        }
        $d = $r['this'] - $r['last'];
        if ($d == 0) {
            return ['text' => 'same', 'color' => 'gray'];
        }
        $text = ($d > 0 ? '+' : '−') . match ($r['format']) {
            'percent' => abs($d) . ' points',
            'rupees' => '₹' . number_format(abs($d)),
            default => number_format(abs($d)),
        };
        // the backlog is the one measure where less is better
        $good = $r['key'] === 'backlog' ? $d < 0 : $d > 0;
        return ['text' => $text, 'color' => $good ? 'success' : 'danger'];
    };
@endphp

<x-filament-panels::page>
    <x-filament::section
        heading="This week against last week"
        :description="'This week = the last 7 days, since ' . $from->format('D j M') . '. Last week = the 7 days before.'">
        @if ($freeMode)
            <p style="margin:0 0 1rem;font-size:.85rem;padding:.6rem .8rem;border-radius:.5rem;background:rgba(217,119,6,.12);">
                Free mode is on: every member gets all features without paying, so paid plans and revenue stay at zero until it is switched off.
            </p>
        @endif

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                <thead>
                    <tr style="text-align:left;opacity:.7;font-size:.75rem;text-transform:uppercase;letter-spacing:.03em;">
                        <th style="padding:.4rem .5rem .4rem 0;font-weight:600;">Measure</th>
                        <th style="padding:.4rem .5rem;font-weight:600;text-align:right;">This week</th>
                        <th style="padding:.4rem .5rem;font-weight:600;text-align:right;">Last week</th>
                        <th style="padding:.4rem 0 .4rem .5rem;font-weight:600;">Change</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        @php($c = $change($r))
                        <tr style="border-top:1px solid rgba(127,127,127,.2);">
                            <td style="padding:.6rem .5rem .6rem 0;">
                                <div style="font-weight:600;">{{ $r['label'] }}</div>
                                <div style="font-size:.75rem;opacity:.65;">{{ $r['how'] }}</div>
                            </td>
                            <td style="padding:.6rem .5rem;text-align:right;font-weight:700;font-size:1rem;white-space:nowrap;">{{ $show($r['this'], $r['format']) }}</td>
                            <td style="padding:.6rem .5rem;text-align:right;white-space:nowrap;opacity:.8;">{{ $show($r['last'], $r['format']) }}</td>
                            <td style="padding:.6rem 0 .6rem .5rem;white-space:nowrap;">
                                @if ($c)
                                    <x-admin.pill :color="$c['color']">{{ $c['text'] }}</x-admin.pill>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p style="margin:1rem 0 0;font-size:.8rem;opacity:.7;">
            Coming next: how many of each month's new members are still active 1 and 3 months later, and where sign-ups come from.
        </p>
    </x-filament::section>
</x-filament-panels::page>
