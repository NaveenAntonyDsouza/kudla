{{-- Tulu Nadu section divider: a short mango-leaf and marigold string with a deepa in the middle. --}}
<div class="home-divider" aria-hidden="true" style="display: flex; align-items: center; justify-content: center; gap: 14px; padding: 6px 16px 0;">
    <span style="flex: 0 1 140px; height: 1px; background: linear-gradient(90deg, transparent, #d9a441);"></span>
    <svg width="132" height="28" viewBox="0 0 132 28">
        <path d="M2 6 Q 66 22 130 6" stroke="#d9a441" stroke-width="1.5" fill="none"/>
        @foreach([[14, 9], [30, 12], [46, 14], [86, 14], [102, 12], [118, 9]] as $i => [$x, $y])
            @if($i === 1 || $i === 4)
                <circle cx="{{ $x }}" cy="{{ $y + 5 }}" r="4.5" fill="#f39c12"/>
            @else
                <path d="M{{ $x }} {{ $y }} C {{ $x + 4 }} {{ $y + 5 }}, {{ $x + 4 }} {{ $y + 13 }}, {{ $x }} {{ $y + 18 }} C {{ $x - 4 }} {{ $y + 13 }}, {{ $x - 4 }} {{ $y + 5 }}, {{ $x }} {{ $y }} Z" fill="#2f7a3a"/>
            @endif
        @endforeach
        <path d="M66 4 C 69 9, 70 12, 66 15 C 62 12, 63 9, 66 4 Z" fill="#f5a623"/>
        <path d="M57 15 H75 C 73 21, 69 23, 66 23 C 63 23, 59 21, 57 15 Z" fill="#c9822a"/>
    </svg>
    <span style="flex: 0 1 140px; height: 1px; background: linear-gradient(90deg, #d9a441, transparent);"></span>
</div>
