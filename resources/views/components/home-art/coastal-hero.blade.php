{{--
    Coastal hero artwork (Oct 2026, "Coastal" homepage design for Kudla): sunset
    over the Arabian Sea, coconut palms and a Mangalore-tiled house. Secular on
    purpose (owner: "neutral, simple, clean, professional", like the big portals),
    so no religious symbols. Pure SVG: no photo licence, light on phones. Decorative only.
--}}
<svg class="home-art home-art-wide" viewBox="0 0 1440 600" preserveAspectRatio="xMinYMid slice" aria-hidden="true" focusable="false"
     style="position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none;">
    <defs>
        <radialGradient id="cs-glow" cx="0.45" cy="0.8" r="0.5">
            <stop offset="0" stop-color="#f6c453" stop-opacity="0.35"/>
            <stop offset="1" stop-color="#f6c453" stop-opacity="0"/>
        </radialGradient>
        <linearGradient id="cs-read" x1="0" x2="1">
            <stop offset="0" stop-color="#2f0810" stop-opacity="0.35"/>
            <stop offset="0.55" stop-color="#2f0810" stop-opacity="0.05"/>
            <stop offset="1" stop-color="#2f0810" stop-opacity="0"/>
        </linearGradient>
        <path id="cs-frond" d="M0 0 C 40 -26, 95 -24, 140 6 C 96 -8, 44 -6, 0 4 Z"/>
        <g id="cs-palm">
            <path d="M-7 0 C 0 -110, 18 -210, 38 -298 L 47 -296 C 28 -208, 12 -108, 8 0 Z"/>
            <g transform="translate(42 -298)">
                @foreach([12, -18, -50] as $a)
                    <use href="#cs-frond" transform="rotate({{ $a }})"/>
                    <use href="#cs-frond" transform="scale(-1 1) rotate({{ $a }})"/>
                @endforeach
                <use href="#cs-frond" transform="rotate(-86) scale(.8)"/>
                <circle cx="-6" cy="10" r="7"/><circle cx="7" cy="12" r="7"/>
            </g>
        </g>
    </defs>

    <rect width="1440" height="600" fill="url(#cs-glow)"/>

    {{-- The sun setting into the sea --}}
    <circle cx="660" cy="478" r="64" fill="#f2b84b" opacity="0.5"/>

    {{-- Sea --}}
    <rect x="0" y="476" width="1440" height="124" fill="#3a0a12" opacity="0.45"/>
    <path d="M0 476 H1440" stroke="#f2c46b" stroke-width="2" opacity="0.5"/>
    <g fill="none" stroke="#f2c46b" stroke-linecap="round">
        @foreach([[500, 500, 260, 0.45], [430, 524, 400, 0.35], [330, 552, 620, 0.28], [220, 584, 860, 0.2]] as [$x, $y, $len, $o])
            <path d="M{{ $x }} {{ $y }} q {{ $len / 8 }} -6 {{ $len / 4 }} 0 t {{ $len / 4 }} 0 t {{ $len / 4 }} 0 t {{ $len / 4 }} 0" stroke-width="2.5" opacity="{{ $o }}"/>
        @endforeach
        {{-- the sun's path on the water --}}
        @foreach([[640, 490, 40], [628, 508, 64], [650, 532, 48], [632, 562, 76]] as [$x, $y, $len])
            <path d="M{{ $x }} {{ $y }} h {{ $len }}" stroke-width="4" opacity="0.4"/>
        @endforeach
    </g>

    {{-- Shore with coconut palms and a Mangalore-tiled house --}}
    <g fill="#2a070d" opacity="0.6">
        <path d="M0 470 C 160 450, 420 452, 600 474 L 600 480 L 0 480 Z"/>
        <use href="#cs-palm" transform="translate(52 476) scale(.62)"/>
        <use href="#cs-palm" transform="translate(118 478) scale(-.48 .48)"/>
        <use href="#cs-palm" transform="translate(560 476) scale(.42)"/>
        {{-- house: walls, then a hipped roof of Mangalore tiles --}}
        <rect x="436" y="440" width="82" height="36"/>
        <path d="M422 444 L 450 414 L 504 414 L 532 444 Z"/>
    </g>
    <g stroke="#f2c46b" stroke-width="1.2" opacity="0.35">
        <path d="M434 436 H520 M440 428 H514 M446 420 H508"/>
    </g>

    <rect width="1440" height="600" fill="url(#cs-read)"/>
</svg>

{{-- Phones: a slim sunset-and-sea strip across the top (the wide scene would be blown up and cropped) --}}
<svg class="home-art-narrow" viewBox="0 0 400 70" preserveAspectRatio="xMidYMin slice" aria-hidden="true" focusable="false"
     style="position: absolute; top: 0; left: 0; width: 100%; height: 70px; pointer-events: none;">
    <circle cx="200" cy="50" r="22" fill="#f2b84b" opacity="0.55"/>
    <rect x="0" y="50" width="400" height="20" fill="#3a0a12" opacity="0.45"/>
    <path d="M0 50 H400" stroke="#f2c46b" stroke-width="1.5" opacity="0.55"/>
    <g fill="none" stroke="#f2c46b" stroke-linecap="round" opacity="0.4" stroke-width="2">
        <path d="M150 58 q 12 -4 25 0 t 25 0 t 25 0 t 25 0"/>
        <path d="M120 65 q 20 -4 40 0 t 40 0 t 40 0 t 40 0"/>
    </g>
    <g fill="#2a070d" opacity="0.55">
        <path d="M14 70 C 16 52, 22 40, 30 30 L 33 31 C 26 42, 21 54, 20 70 Z"/>
        <g transform="translate(31 30) scale(.32)">
            @foreach([12, -18, -50] as $a)
                <path d="M0 0 C 40 -26, 95 -24, 140 6 C 96 -8, 44 -6, 0 4 Z" transform="rotate({{ $a }})"/>
                <path d="M0 0 C 40 -26, 95 -24, 140 6 C 96 -8, 44 -6, 0 4 Z" transform="scale(-1 1) rotate({{ $a }})"/>
            @endforeach
        </g>
        <path d="M386 70 C 384 54, 378 44, 372 36 L 369 37 C 374 46, 379 56, 380 70 Z"/>
        <g transform="translate(370 36) scale(-.26 .26)">
            @foreach([12, -18, -50] as $a)
                <path d="M0 0 C 40 -26, 95 -24, 140 6 C 96 -8, 44 -6, 0 4 Z" transform="rotate({{ $a }})"/>
                <path d="M0 0 C 40 -26, 95 -24, 140 6 C 96 -8, 44 -6, 0 4 Z" transform="scale(-1 1) rotate({{ $a }})"/>
            @endforeach
        </g>
    </g>
</svg>
