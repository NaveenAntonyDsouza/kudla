{{--
    Admin member-list row (avatar · name + meta · actions).

    Filament v5 does not compile arbitrary Tailwind utilities used in custom
    page views (no dedicated theme build), so layout here uses inline styles,
    which always render. Drop body content (meta lines, notes, <x-admin.pill>)
    into the default slot; pass action buttons (<x-filament::button>) via the
    `actions` slot.
--}}
@props([
    'photo' => null,
    'name' => '',
    'subtitle' => null,
    'href' => null,
    'first' => false,
])
<div style="display:flex;align-items:center;gap:1rem;padding:0.8rem 0;{{ $first ? '' : 'border-top:1px solid rgba(128,128,128,0.18);' }}">
    <img src="{{ $photo ? asset('storage/' . $photo) : url('/images/default-avatar.svg') }}" alt=""
        style="width:2.75rem;height:2.75rem;border-radius:9999px;object-fit:cover;flex-shrink:0;background:rgba(128,128,128,0.12);">

    <div style="flex:1;min-width:0;">
        <div style="font-weight:600;line-height:1.3;">
            @if ($href)
                <a href="{{ $href }}" style="color:#8B1D91;text-decoration:none;">{{ $name }}</a>
            @else
                {{ $name }}
            @endif
            @if ($subtitle)
                <span style="opacity:.55;font-weight:400;">{{ $subtitle }}</span>
            @endif
        </div>
        <div style="opacity:.75;font-size:.8125rem;margin-top:.2rem;display:flex;flex-wrap:wrap;align-items:center;gap:.3rem .6rem;">
            {{ $slot }}
        </div>
    </div>

    @isset($actions)
        <div style="display:flex;flex-wrap:wrap;justify-content:flex-end;gap:.4rem;flex-shrink:0;">
            {{ $actions }}
        </div>
    @endisset
</div>
