{{-- Harmony section ornament: two short ribbons, marigold and purple, that cross between two fine lines. No ids, so it can repeat on a page. --}}
@props(['light' => false])
@php
    $line = $light ? 'rgba(255,255,255,0.55)' : 'rgba(227,116,4,0.55)';
    $second = $light ? '#ffffff' : 'var(--g-p, #8b1d91)';
@endphp
<div class="g-ornament" aria-hidden="true" style="display: flex; align-items: center; justify-content: center; gap: 10px; margin: 0 auto 14px;">
    <span style="flex: 0 1 72px; height: 1px; background: linear-gradient(90deg, transparent, {{ $line }});"></span>
    <svg width="64" height="20" viewBox="0 0 64 20" fill="none" stroke-linecap="round">
        <path d="M3 13 C 15 2, 25 2, 32 10 S 49 18, 61 7" stroke="#e8890f" stroke-width="2.6"/>
        <path d="M3 7 C 15 18, 25 18, 32 10 S 49 2, 61 13" stroke="{{ $second }}" stroke-width="2.6" opacity="0.85"/>
    </svg>
    <span style="flex: 0 1 72px; height: 1px; background: linear-gradient(90deg, {{ $line }}, transparent);"></span>
</div>
