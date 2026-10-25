{{-- Slim strip above the visitor header (Oct 2026, like the big portals): call / WhatsApp / help. Only when a phone or WhatsApp number is set. --}}
@php
    $csPhone = \App\Support\SiteContact::phoneDisplay();
    $csTel = \App\Support\SiteContact::phoneHref();
    $csWa = \App\Support\SiteContact::whatsappHref();
@endphp
@if($csTel || $csWa)
    <div style="background: var(--color-primary-light, #f3e8f7); border-bottom: 1px solid rgba(0,0,0,.05);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" style="display: flex; align-items: center; justify-content: flex-end; gap: 18px; min-height: 32px; font-size: 12.5px; color: #4b5563;">
            @if($csTel)
                <a href="{{ $csTel }}" style="display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;" class="hover:underline">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <span><span class="hidden sm:inline">Call </span><b style="color: #1f2937;">{{ $csPhone }}</b></span>
                </a>
            @endif
            @if($csWa)
                <a href="{{ $csWa }}" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; gap: 6px; color: #128c4b; font-weight: 600; white-space: nowrap;" class="hover:underline">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.21 3.08.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2.01-1.41.25-.69.25-1.29.17-1.41-.07-.12-.27-.2-.57-.35zM12.05 21.79h-.01a9.87 9.87 0 01-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 01-1.51-5.26c0-5.45 4.44-9.89 9.89-9.89 2.64 0 5.12 1.03 6.99 2.9a9.82 9.82 0 012.89 6.99c0 5.45-4.44 9.89-9.89 9.89zm8.41-18.3A11.82 11.82 0 0012.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.95L.06 24l6.3-1.65a11.88 11.88 0 005.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.89 0-3.18-1.24-6.17-3.48-8.42z"/></svg>
                    WhatsApp
                </a>
            @endif
            <a href="/faq" class="hover:underline" style="white-space: nowrap;">Help</a>
        </div>
    </div>
@endif
