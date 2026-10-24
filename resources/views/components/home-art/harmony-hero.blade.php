{{--
    Harmony hero artwork (Oct 2026, "Harmony" homepage design, Kudla): two flowing
    ribbons, marigold and soft white, that meet and twist together across the sky,
    "two lives, one journey". Abstract on purpose (owner: a culture- and
    religion-neutral homepage), so no flowers, symbols or people. Each ribbon is a
    bundle of fine lines that spread and pinch, which reads as a twisting silk band.
    Pure SVG, decorative only.
--}}
@php
    // One ribbon: $n lines sharing two cubic segments; each point is pushed
    // apart by its own amount ($spread), so the bundle widens and twists.
    $ribbon = function (array $p, array $spread, int $n) {
        $lines = [];
        for ($i = 0; $i < $n; $i++) {
            $t = $n > 1 ? $i / ($n - 1) * 2 - 1 : 0;                 // -1 .. 1 across the ribbon
            $q = array_map(fn ($pt, $s) => [round($pt[0], 1), round($pt[1] + $s * $t, 1)], $p, $spread);
            $lines[] = sprintf('M%s %s C%s %s %s %s %s %s C%s %s %s %s %s %s',
                $q[0][0], $q[0][1], $q[1][0], $q[1][1], $q[2][0], $q[2][1], $q[3][0], $q[3][1],
                $q[4][0], $q[4][1], $q[5][0], $q[5][1], $q[6][0], $q[6][1]);
        }
        return $lines;
    };
    $art = [
        'w' => [1440, 720, 'h-art-wide', [
            'gold' => $ribbon([[-60, 560], [260, 700], [470, 330], [760, 430], [1040, 520], [1180, 190], [1500, 250]], [10, 80, -46, 6, 70, -34, 14], 16),
            'pearl' => $ribbon([[-60, 290], [300, 130], [520, 640], [800, 470], [1080, 320], [1250, 650], [1500, 560]], [12, -70, 52, 5, -64, 40, 12], 16),
        ]],
        // phones: a slim band across the top of the hero, above the headline
        'n' => [400, 170, 'h-art-narrow', [
            'gold' => $ribbon([[-30, 120], [90, 172], [170, 18], [250, 72], [320, 112], [360, 150], [440, 60]], [5, 26, -16, 3, 22, -12, 5], 12),
            'pearl' => $ribbon([[-30, 58], [80, 8], [180, 150], [250, 100], [330, 50], [370, 18], [440, 112]], [5, -24, 18, 3, -20, 14, 5], 12),
        ]],
    ];
@endphp
@foreach($art as $id => [$vw, $vh, $class, $ribbons])
<svg class="h-art {{ $class }}" viewBox="0 0 {{ $vw }} {{ $vh }}" preserveAspectRatio="{{ $id === 'w' ? 'xMidYMid slice' : 'xMidYMin meet' }}" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="hr{{ $id }}-gold" x1="0" x2="1" y1="0" y2="0">
            <stop offset="0" stop-color="#f7b51e" stop-opacity="0.15"/>
            <stop offset="0.45" stop-color="#f7b51e" stop-opacity="0.85"/>
            <stop offset="1" stop-color="#ff8a1f" stop-opacity="0.55"/>
        </linearGradient>
        <linearGradient id="hr{{ $id }}-pearl" x1="0" x2="1" y1="0" y2="0">
            <stop offset="0" stop-color="#ffffff" stop-opacity="0.08"/>
            <stop offset="0.5" stop-color="#ffffff" stop-opacity="0.5"/>
            <stop offset="1" stop-color="#ffe9f7" stop-opacity="0.25"/>
        </linearGradient>
        <filter id="hr{{ $id }}-glow" x="-10%" y="-30%" width="120%" height="160%"><feGaussianBlur stdDeviation="{{ $id === 'w' ? 18 : 9 }}"/></filter>
    </defs>
    {{-- a soft glow under each ribbon --}}
    <path d="{{ $ribbons['gold'][intdiv(count($ribbons['gold']), 2)] }}" fill="none" stroke="#f7a21e" stroke-width="{{ $id === 'w' ? 70 : 34 }}" opacity="0.16" filter="url(#hr{{ $id }}-glow)"/>
    <path d="{{ $ribbons['pearl'][intdiv(count($ribbons['pearl']), 2)] }}" fill="none" stroke="#ffffff" stroke-width="{{ $id === 'w' ? 60 : 30 }}" opacity="0.08" filter="url(#hr{{ $id }}-glow)"/>
    <g fill="none" stroke-linecap="round">
        @foreach($ribbons['pearl'] as $d)
            <path d="{{ $d }}" stroke="url(#hr{{ $id }}-pearl)" stroke-width="1.1"/>
        @endforeach
        @foreach($ribbons['gold'] as $d)
            <path d="{{ $d }}" stroke="url(#hr{{ $id }}-gold)" stroke-width="1.3"/>
        @endforeach
    </g>
</svg>
@endforeach
