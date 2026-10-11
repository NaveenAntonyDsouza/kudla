{{--
    Parish hero artwork (Oct 2026, "Parish" homepage design for Catholic
    sites): light falling through a stained-glass gothic window with a cross,
    a dove, white lilies. Pure SVG, decorative only.
--}}
<svg class="home-art home-art-wide" viewBox="0 0 1440 600" preserveAspectRatio="xMinYMid slice" aria-hidden="true" focusable="false"
     style="position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none;">
    <defs>
        <clipPath id="pa-arch"><path d="M40 600 V250 Q40 70 190 70 Q340 70 340 250 V600 Z"/></clipPath>
        <linearGradient id="pa-ray" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#fff8e1" stop-opacity="0.16"/>
            <stop offset="1" stop-color="#fff8e1" stop-opacity="0"/>
        </linearGradient>
        <linearGradient id="pa-read" x1="0" x2="1">
            <stop offset="0" stop-color="#0b1a36" stop-opacity="0.4"/>
            <stop offset="0.6" stop-color="#0b1a36" stop-opacity="0.05"/>
            <stop offset="1" stop-color="#0b1a36" stop-opacity="0"/>
        </linearGradient>
        <g id="pa-lily">
            <path d="M0 0 C -6 -18, -22 -30, -34 -30 C -24 -22, -16 -10, -12 4 Z" fill="#fffdf6"/>
            <path d="M0 0 C 6 -18, 22 -30, 34 -30 C 24 -22, 16 -10, 12 4 Z" fill="#fffdf6"/>
            <path d="M0 2 C -4 -16, -2 -34, 0 -42 C 2 -34, 4 -16, 0 2 Z" fill="#f4efe0"/>
            <path d="M-2 -6 V-26 M2 -6 V-24" stroke="#d9a441" stroke-width="1.6"/>
        </g>
    </defs>

    {{-- Light through the window --}}
    <polygon points="80,90 320,90 980,600 460,600" fill="url(#pa-ray)"/>

    {{-- Stained-glass window --}}
    <g clip-path="url(#pa-arch)" opacity="0.32">
        @php $paColours = ['#3b6fb6', '#b33a3a', '#d4a94a', '#3f8a5a', '#7b4fa0', '#3b6fb6']; @endphp
        @for($r = 0; $r < 9; $r++)
            @for($c = 0; $c < 6; $c++)
                <rect x="{{ 40 + $c * 50 }}" y="{{ 70 + $r * 60 }}" width="50" height="60" fill="{{ $paColours[($r + $c * 2) % 6] }}"/>
            @endfor
        @endfor
        <circle cx="190" cy="200" r="56" fill="#f2d37a"/>
    </g>
    <g fill="none" stroke="#e7c873" stroke-opacity="0.7">
        <path d="M40 600 V250 Q40 70 190 70 Q340 70 340 250 V600" stroke-width="7"/>
        <path d="M140 600 V160 M240 600 V160 M40 330 H340 M40 460 H340" stroke-width="3"/>
        <circle cx="190" cy="200" r="56" stroke-width="3"/>
    </g>
    <g fill="#f3d27a" opacity="0.85"><rect x="184" y="160" width="12" height="80" rx="2"/><rect x="162" y="182" width="56" height="12" rx="2"/></g>

    {{-- Dove --}}
    <g transform="translate(620 96) scale(1.25)" opacity="0.8">
        <path d="M30 14 C 30 -2, 40 -14, 56 -20 C 50 -8, 50 2, 50 12 Z" fill="#e6ecf7"/>
        <path d="M0 20 L-18 12 L-14 22 L-20 30 L2 26 Z" fill="#ffffff"/>
        <path d="M0 20 C 10 10, 30 8, 46 14 C 54 17, 60 16, 66 12 C 64 20, 58 26, 50 28 C 36 32, 16 30, 0 20 Z" fill="#ffffff"/>
        <path d="M18 17 C 12 0, 20 -18, 38 -32 C 36 -14, 40 -2, 44 13 Z" fill="#ffffff"/>
        <circle cx="64" cy="10" r="6.5" fill="#ffffff"/>
        <circle cx="66" cy="8.5" r="1.2" fill="#1d3768"/>
        <path d="M70 8.5 L78 11 L70 13 Z" fill="#e8b84a"/>
        <path d="M77 12 C 84 16, 89 22, 92 30" stroke="#7fae6a" stroke-width="1.8" fill="none"/>
        <ellipse cx="83" cy="15" rx="4" ry="1.8" transform="rotate(35 83 15)" fill="#7fae6a"/>
        <ellipse cx="88" cy="21" rx="4" ry="1.8" transform="rotate(-20 88 21)" fill="#7fae6a"/>
        <ellipse cx="91" cy="27" rx="4" ry="1.8" transform="rotate(50 91 27)" fill="#7fae6a"/>
    </g>

    {{-- Lilies --}}
    <g transform="translate(560 604)">
        <path d="M0 0 C -10 -60, -30 -110, -52 -150 M-14 0 C -20 -50, -6 -96, 12 -128 M-26 0 C -40 -40, -70 -70, -98 -88" stroke="#4d7d4a" stroke-width="4" fill="none"/>
        <path d="M-20 -40 C -40 -60, -60 -62, -70 -58 C -54 -50, -40 -44, -24 -30 Z M-6 -70 C 14 -86, 30 -86, 40 -80 C 26 -74, 12 -66, 0 -56 Z" fill="#4d7d4a"/>
        <use href="#pa-lily" transform="translate(-52 -150) rotate(-24)"/>
        <use href="#pa-lily" transform="translate(12 -128) rotate(18) scale(0.85)"/>
        <use href="#pa-lily" transform="translate(-98 -88) rotate(-58) scale(0.8)"/>
    </g>

    <rect width="1440" height="600" fill="url(#pa-read)"/>
    <path d="M0 596 H1440" stroke="#d9b45a" stroke-width="3" opacity="0.6"/>
</svg>

{{-- Phones: a small gold cross with light and a dove above the headline --}}
<svg class="home-art-narrow" viewBox="0 0 400 70" preserveAspectRatio="xMidYMin meet" aria-hidden="true" focusable="false"
     style="position: absolute; top: 0; left: 0; width: 100%; height: 70px; pointer-events: none;">
    <circle cx="200" cy="34" r="30" fill="#f3d27a" opacity="0.14"/>
    <rect x="197" y="12" width="6" height="44" rx="1.5" fill="#e7c873"/>
    <rect x="185" y="24" width="30" height="6" rx="1.5" fill="#e7c873"/>
    <g transform="translate(262 22) scale(0.55)" opacity="0.85">
        <path d="M0 20 L-18 12 L-14 22 L-20 30 L2 26 Z" fill="#ffffff"/>
        <path d="M0 20 C 10 10, 30 8, 46 14 C 54 17, 60 16, 66 12 C 64 20, 58 26, 50 28 C 36 32, 16 30, 0 20 Z" fill="#ffffff"/>
        <path d="M18 17 C 12 0, 20 -18, 38 -32 C 36 -14, 40 -2, 44 13 Z" fill="#ffffff"/>
        <circle cx="64" cy="10" r="6.5" fill="#ffffff"/>
        <path d="M70 8.5 L78 11 L70 13 Z" fill="#e8b84a"/>
    </g>
</svg>
