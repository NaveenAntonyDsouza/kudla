{{-- Garland section ornament: a marigold, a jasmine bud and a marigold between two fine lines. No ids, so it can repeat on a page. --}}
@props(['light' => false])
@php
    $petals = fn (float $cx, string $fill, string $edge) => collect(range(0, 330, 30))
        ->map(fn ($d) => sprintf('<circle cx="%.2f" cy="%.2f" r="3.1" fill="%s" stroke="%s" stroke-width="0.5"/>', $cx + 5.2 * cos(deg2rad($d)), 11 + 5.2 * sin(deg2rad($d)), $fill, $edge))
        ->implode('');
    $line = $light ? 'rgba(255,255,255,0.55)' : 'rgba(227,116,4,0.55)';
@endphp
<div class="g-ornament" aria-hidden="true" style="display: flex; align-items: center; justify-content: center; gap: 10px; margin: 0 auto 14px;">
    <span style="flex: 0 1 72px; height: 1px; background: linear-gradient(90deg, transparent, {{ $line }});"></span>
    <svg width="62" height="22" viewBox="0 0 62 22">
        {!! $petals(11, '#f28c1b', '#d9650a') !!}<circle cx="11" cy="11" r="4.6" fill="#f07c12"/><circle cx="11" cy="11" r="2.2" fill="#fba640"/>
        <ellipse cx="31" cy="11" rx="3" ry="6.2" fill="#fffdf5" stroke="#c9b27e" stroke-width="1"/>
        {!! $petals(51, '#f7b51e', '#e0950a') !!}<circle cx="51" cy="11" r="4.6" fill="#f4a614"/><circle cx="51" cy="11" r="2.2" fill="#fdd25a"/>
    </svg>
    <span style="flex: 0 1 72px; height: 1px; background: linear-gradient(90deg, {{ $line }}, transparent);"></span>
</div>
