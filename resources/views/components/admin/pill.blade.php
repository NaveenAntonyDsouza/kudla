{{--
    Small status pill for admin list rows. Inline styles (translucent bg + mid-
    tone text) so it renders without a Filament theme build and stays legible in
    both light and dark mode. Usage: <x-admin.pill color="success">Paid</x-admin.pill>
    Colors: gray | primary | success | warning | danger | info | pink
--}}
@props(['color' => 'gray'])
@php
    $map = [
        'gray'    => ['bg' => 'rgba(113,113,122,0.16)', 'fg' => '#52525b'],
        'primary' => ['bg' => 'rgba(139,29,145,0.14)',  'fg' => '#8B1D91'],
        'success' => ['bg' => 'rgba(22,163,74,0.16)',   'fg' => '#15803d'],
        'warning' => ['bg' => 'rgba(217,119,6,0.16)',   'fg' => '#b45309'],
        'danger'  => ['bg' => 'rgba(220,38,38,0.16)',   'fg' => '#dc2626'],
        'info'    => ['bg' => 'rgba(37,99,235,0.16)',   'fg' => '#1d4ed8'],
        'pink'    => ['bg' => 'rgba(219,39,119,0.16)',  'fg' => '#be185d'],
    ];
    $c = $map[$color] ?? $map['gray'];
@endphp
<span style="display:inline-flex;align-items:center;padding:.1rem .5rem;border-radius:.375rem;font-size:.7rem;font-weight:600;line-height:1.4;white-space:nowrap;background:{{ $c['bg'] }};color:{{ $c['fg'] }};">{{ $slot }}</span>
