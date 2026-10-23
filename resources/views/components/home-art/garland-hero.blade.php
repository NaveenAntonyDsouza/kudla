{{--
    Garland hero artwork (Oct 2026, "Garland" homepage design, first for Kudla in
    purple and marigold): marigold-and-jasmine garlands hung in swags across the
    top of the hero, with short strands dropping from each knot. Flowers that
    every community uses at weddings, so no religious symbols. The flowers keep
    their own colours (orange and yellow marigold, white jasmine); the sky behind
    them comes from the site theme. Pure SVG, decorative only.
--}}
@php
    // Bead positions are computed here so the garland stays even at any width.
    $garland = function (int $width, int $step, int $sag, int $perSwag, array $strands, float $knot) {
        $out = ['swags' => [], 'strands' => [], 'knots' => []];
        $cycle = ['o', 'y', 'o', 'y', 'j', 'j', 'j'];   // four marigolds, then a band of jasmine buds
        for ($a = 0; $a < $width; $a += $step) {
            $b = $a + $step;
            $cx = ($a + $b) / 2;
            for ($i = 0; $i < $perSwag; $i++) {
                $t = ($i + 0.5) / $perSwag;
                $x = (1 - $t) ** 2 * $a + 2 * (1 - $t) * $t * $cx + $t ** 2 * $b;
                $y = (1 - $t) ** 2 * 5 + 2 * (1 - $t) * $t * (5 + 2 * $sag) + $t ** 2 * 5;
                $dx = 2 * (1 - $t) * ($cx - $a) + 2 * $t * ($b - $cx);
                $dy = 2 * (1 - $t) * (2 * $sag) + 2 * $t * (-2 * $sag);
                $angle = rad2deg(atan2($dy, $dx)) + 90;   // jasmine buds lie along the string
                $kind = $cycle[$i % count($cycle)];
                $out['swags'][] = [round($x, 1), round($y, 1), $kind, round($angle)];
            }
        }
        foreach ($strands as $x => $length) {
            for ($y = 13, $n = 0; $y <= $length; $y += 9, $n++) {
                $out['strands'][] = [$x, $y, $cycle[$n % count($cycle)]];
            }
            $out['knots'][] = [$x, $length + 8, 'end'];
            $out['knots'][] = [$x, 6, 'knot'];
        }
        $out['knot'] = $knot;
        return $out;
    };
    $wide = $garland(1440, 180, 46, 26, [180 => 76, 360 => 112, 540 => 76, 720 => 130, 900 => 76, 1080 => 112, 1260 => 76], 1.2);
    $narrow = $garland(400, 100, 30, 15, [100 => 52, 200 => 76, 300 => 52], 1.15);
@endphp
@foreach(['w' => [$wide, 1440, 200, 'g-art-wide'], 'n' => [$narrow, 400, 130, 'g-art-narrow']] as $id => [$g, $vw, $vh, $class])
<svg class="g-art {{ $class }}" viewBox="0 0 {{ $vw }} {{ $vh }}" preserveAspectRatio="xMidYMin slice" aria-hidden="true" focusable="false">
    <defs>
        <g id="gl{{ $id }}-o">
            @foreach(range(0, 330, 30) as $deg)
                <circle cx="{{ round(6.2 * cos(deg2rad($deg)), 2) }}" cy="{{ round(6.2 * sin(deg2rad($deg)), 2) }}" r="3.7" fill="#f28c1b" stroke="#d9650a" stroke-width="0.6"/>
            @endforeach
            <circle r="5.6" fill="#f07c12"/><circle r="2.8" fill="#fba640"/>
        </g>
        <g id="gl{{ $id }}-y">
            @foreach(range(15, 345, 30) as $deg)
                <circle cx="{{ round(6.2 * cos(deg2rad($deg)), 2) }}" cy="{{ round(6.2 * sin(deg2rad($deg)), 2) }}" r="3.7" fill="#f7b51e" stroke="#e0950a" stroke-width="0.6"/>
            @endforeach
            <circle r="5.6" fill="#f4a614"/><circle r="2.8" fill="#fdd25a"/>
        </g>
        <filter id="gl{{ $id }}-shadow" x="-5%" y="-20%" width="110%" height="160%">
            <feDropShadow dx="0" dy="2.5" stdDeviation="1.6" flood-color="#14031a" flood-opacity="0.5"/>
        </filter>
        <g id="gl{{ $id }}-j">
            <ellipse rx="3.1" ry="6.4" fill="#fffdf5" stroke="#e6dcc0" stroke-width="0.7"/>
            <ellipse rx="1.3" ry="2.6" cy="-1.6" fill="#ffffff"/>
        </g>
    </defs>
    {{-- the cord the garlands hang from --}}
    <path d="M0 5 H{{ $vw }}" stroke="#f7d28a" stroke-width="2" opacity="0.65"/>
    <g filter="url(#gl{{ $id }}-shadow)">
    @foreach($g['swags'] as [$x, $y, $kind, $angle])
        @if($kind === 'j')
            <use href="#gl{{ $id }}-j" transform="translate({{ $x }} {{ $y }}) rotate({{ $angle }})"/>
        @else
            <use href="#gl{{ $id }}-{{ $kind }}" x="{{ $x }}" y="{{ $y }}"/>
        @endif
    @endforeach
    @foreach($g['strands'] as [$x, $y, $kind])
        <use href="#gl{{ $id }}-{{ $kind }}" x="{{ $x }}" y="{{ $y }}"/>
    @endforeach
    @foreach($g['knots'] as [$x, $y, $kind])
        <use href="#gl{{ $id }}-{{ $kind === 'end' ? 'y' : 'o' }}" transform="translate({{ $x }} {{ $y }}) scale({{ $kind === 'end' ? 1.3 : $g['knot'] }})"/>
    @endforeach
    </g>
</svg>
@endforeach
