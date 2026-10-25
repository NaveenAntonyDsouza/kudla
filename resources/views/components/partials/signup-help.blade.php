{{-- Sign-up / onboarding header help (Oct 2026): show the number and WhatsApp instead of a bare "Call Us". --}}
@php
    $shPhone = \App\Support\SiteContact::phoneDisplay();
    $shTel = \App\Support\SiteContact::phoneHref();
    $shWa = \App\Support\SiteContact::whatsappHref();
@endphp
@if($shTel || $shWa)
    <div class="flex items-center gap-3 text-sm text-gray-500">
        <span class="hidden sm:inline">Need help to register?</span>
        @if($shTel)
            <a href="{{ $shTel }}" class="font-semibold text-(--color-primary) hover:underline" style="white-space: nowrap;">{{ $shPhone }}</a>
        @endif
        @if($shWa)
            <a href="{{ $shWa }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-medium hover:underline" style="color: #128c4b; white-space: nowrap;" aria-label="WhatsApp">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.21 3.08.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2.01-1.41.25-.69.25-1.29.17-1.41-.07-.12-.27-.2-.57-.35zM12.05 21.79h-.01a9.87 9.87 0 01-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 01-1.51-5.26c0-5.45 4.44-9.89 9.89-9.89 2.64 0 5.12 1.03 6.99 2.9a9.82 9.82 0 012.89 6.99c0 5.45-4.44 9.89-9.89 9.89zm8.41-18.3A11.82 11.82 0 0012.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.95L.06 24l6.3-1.65a11.88 11.88 0 005.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.89 0-3.18-1.24-6.17-3.48-8.42z"/></svg>
                <span class="hidden sm:inline">WhatsApp</span>
            </a>
        @endif
    </div>
@endif
