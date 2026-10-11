{{--
    Tulu Nadu hero artwork (Oct 2026, "Tulu Nadu" homepage design): a mango-leaf
    thorana with marigolds across the top, strings of mallige (jasmine), a
    kalasha with coconut and two deepas, coconut palms and the Arabian Sea.
    Coastal Hindu wedding symbols, no deity images, so every member feels
    welcome. Pure SVG: no photo licence, light on phones. Decorative only.
--}}
@php
    $tnLeaves = [];
    for ($i = 0; $i <= 36; $i++) {
        $x = 10 + $i * 40;
        $tnLeaves[] = [$x, round(14 + 34 * (1 - pow(($x - 720) / 720, 2)), 1)];
    }
@endphp
<svg class="home-art home-art-wide" viewBox="0 0 1440 600" preserveAspectRatio="xMinYMid slice" aria-hidden="true" focusable="false"
     style="position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none;">
    <defs>
        <radialGradient id="tn-sun" cx="0.72" cy="0.55" r="0.55">
            <stop offset="0" stop-color="#f6c453" stop-opacity="0.32"/>
            <stop offset="1" stop-color="#f6c453" stop-opacity="0"/>
        </radialGradient>
        <linearGradient id="tn-read" x1="0" x2="1">
            <stop offset="0" stop-color="#3d0a12" stop-opacity="0.45"/>
            <stop offset="0.6" stop-color="#3d0a12" stop-opacity="0.08"/>
            <stop offset="1" stop-color="#3d0a12" stop-opacity="0"/>
        </linearGradient>
        <path id="tn-leaf" d="M0 0 C 10 15, 10 42, 0 62 C -10 42, -10 15, 0 0 Z"/>
        <path id="tn-frond" d="M0 0 C 45 -24, 95 -20, 130 8 C 92 -6, 46 -5, 0 0 Z"/>
        <g id="tn-bud"><ellipse cx="0" cy="0" rx="3.2" ry="4.4"/></g>
    </defs>

    <rect width="1440" height="600" fill="url(#tn-sun)"/>

    {{-- Arabian Sea --}}
    <path d="M0 512 C 120 494, 240 530, 360 512 S 600 494, 720 512 S 960 530, 1080 512 S 1320 494, 1440 512 V600 H0 Z" fill="#f8e6c2" opacity="0.13"/>
    <path d="M0 548 C 140 532, 260 566, 400 548 S 660 532, 800 548 S 1060 566, 1200 548 S 1380 536, 1440 544 V600 H0 Z" fill="#f8e6c2" opacity="0.10"/>

    {{-- Coconut palms --}}
    <g fill="#16351d" opacity="0.55">
        <path d="M58 600 C 66 520, 84 458, 118 398" stroke="#16351d" stroke-width="11" stroke-linecap="round" fill="none"/>
        <g transform="translate(118 398)">
            @foreach([-168, -132, -96, -58, -22, 14, 52] as $a)<use href="#tn-frond" transform="rotate({{ $a }})"/>@endforeach
            <circle cx="4" cy="8" r="7" fill="#3a2a12"/><circle cx="-6" cy="10" r="6" fill="#3a2a12"/>
        </g>
        <path d="M1396 600 C 1386 530, 1366 470, 1330 418" stroke="#16351d" stroke-width="10" stroke-linecap="round" fill="none"/>
        <g transform="translate(1330 418) scale(-0.9 0.9)">
            @foreach([-168, -132, -96, -58, -22, 14, 52] as $a)<use href="#tn-frond" transform="rotate({{ $a }})"/>@endforeach
        </g>
    </g>

    {{-- Kalasha: copper pot, mango leaves, coconut --}}
    <g transform="translate(250 456)" opacity="0.9">
        <g fill="#2f7a3a">
            @foreach([-62, -34, -8, 18, 44] as $a)<use href="#tn-leaf" transform="rotate({{ $a + 180 }}) scale(0.62)"/>@endforeach
        </g>
        <circle cx="0" cy="-14" r="17" fill="#7a4a1d"/>
        <path d="M-6 -30 C -2 -38, 4 -38, 8 -30" stroke="#5b3612" stroke-width="3" fill="none"/>
        <path d="M-24 0 H24 L18 10 C 46 22, 50 62, 26 80 H-26 C -50 62, -46 22, -18 10 Z" fill="#d38b2a"/>
        <path d="M-30 36 H30" stroke="#f3c56b" stroke-width="3"/>
        <circle cx="0" cy="50" r="6" fill="#a8261c"/>
    </g>

    {{-- Deepas --}}
    @foreach([[372, 520], [436, 528]] as [$dx, $dy])
        <g transform="translate({{ $dx }} {{ $dy }})">
            <ellipse cx="0" cy="-14" rx="16" ry="20" fill="#ffd77a" opacity="0.18"/>
            <path d="M0 -26 C 6 -16, 7 -9, 0 -4 C -7 -9, -6 -16, 0 -26 Z" fill="#ffb21c"/>
            <path d="M0 -20 C 3 -14, 3 -10, 0 -7 C -3 -10, -3 -14, 0 -20 Z" fill="#fff1b8"/>
            <path d="M-18 -4 H18 C 14 6, 6 10, 0 10 C -6 10, -14 6, -18 -4 Z" fill="#d38b2a"/>
            <rect x="-4" y="9" width="8" height="8" fill="#b6731f"/>
        </g>
    @endforeach

    {{-- Thorana: rope, mango leaves, marigolds --}}
    <path d="M0 14 Q 720 82 1440 14" stroke="#e9b44c" stroke-width="3" fill="none"/>
    @foreach($tnLeaves as $i => [$lx, $ly])
        @if($i % 3 === 2)
            <circle cx="{{ $lx }}" cy="{{ $ly + 9 }}" r="8" fill="#f39c12"/>
            <circle cx="{{ $lx }}" cy="{{ $ly + 9 }}" r="4" fill="#ffc94a"/>
        @else
            <use href="#tn-leaf" transform="translate({{ $lx }} {{ $ly }})" fill="{{ $i % 2 ? '#2f7a3a' : '#3c9147' }}"/>
            <path d="M{{ $lx }} {{ $ly + 4 }} V{{ $ly + 54 }}" stroke="#1f5a29" stroke-width="1"/>
        @endif
    @endforeach

    {{-- Strings of mallige (jasmine) --}}
    <g fill="#fffaf0">
        @foreach([200 => 12, 520 => 9, 920 => 9, 1240 => 12] as $jx => $count)
            @php $jy = 14 + 34 * (1 - pow(($jx - 720) / 720, 2)); @endphp
            @for($k = 1; $k <= $count; $k++)
                <use href="#tn-bud" transform="translate({{ round($jx + 3 * sin($k / 2), 1) }} {{ round($jy + 12 + $k * 9, 1) }})"/>
            @endfor
            <circle cx="{{ $jx }}" cy="{{ round($jy + 20 + $count * 9, 1) }}" r="5" fill="#f39c12"/>
        @endforeach
    </g>

    <rect width="1440" height="600" fill="url(#tn-read)"/>
</svg>

{{-- Phones: a thorana strip across the top (the wide scene would be blown up and cropped) --}}
<svg class="home-art-narrow" viewBox="0 0 400 74" preserveAspectRatio="xMidYMin slice" aria-hidden="true" focusable="false"
     style="position: absolute; top: 0; left: 0; width: 100%; height: 74px; pointer-events: none;">
    <path d="M0 6 Q 200 26 400 6" stroke="#e9b44c" stroke-width="2" fill="none"/>
    @for($i = 0; $i <= 18; $i++)
        @php $x = 6 + $i * 22; $y = round(6 + 20 * (1 - pow(($x - 200) / 200, 2)) * 0.5 + 5 * (1 - pow(($x - 200) / 200, 2)), 1); @endphp
        @if($i % 3 === 1)
            <circle cx="{{ $x }}" cy="{{ $y + 6 }}" r="5.5" fill="#f39c12"/><circle cx="{{ $x }}" cy="{{ $y + 6 }}" r="2.6" fill="#ffc94a"/>
        @else
            <path d="M{{ $x }} {{ $y }} C {{ $x + 6 }} {{ $y + 9 }}, {{ $x + 6 }} {{ $y + 25 }}, {{ $x }} {{ $y + 37 }} C {{ $x - 6 }} {{ $y + 25 }}, {{ $x - 6 }} {{ $y + 9 }}, {{ $x }} {{ $y }} Z" fill="{{ $i % 2 ? '#2f7a3a' : '#3c9147' }}"/>
        @endif
    @endfor
    <g fill="#fffaf0">
        @foreach([50, 350] as $jx)
            @for($k = 1; $k <= 6; $k++)<ellipse cx="{{ $jx }}" cy="{{ 14 + $k * 8 }}" rx="2.4" ry="3.2"/>@endfor
            <circle cx="{{ $jx }}" cy="68" r="3.4" fill="#f39c12"/>
        @endforeach
    </g>
</svg>
