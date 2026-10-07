{{--
    WhatsApp + Call for an unlocked contact (App\Support\ContactAccess OPEN).
    WhatsApp leads — the owner wants members to talk there — and opens with
    a short intro already typed (Matri IDs only, nothing personal). Clicks
    are counted (contact_whatsapp_click / contact_call_click, by source).
    Inline styles: the live CSS build is not rebuilt on deploy.
--}}
@props(['contact', 'source' => 'profile', 'compact' => false])

@if(!empty($contact['whatsapp_link']) || !empty($contact['tel']))
    <div class="cb-row {{ $compact ? 'cb-compact' : '' }}">
        @if(!empty($contact['whatsapp_link']))
            <a href="{{ $contact['whatsapp_link'] }}" target="_blank" rel="noopener" class="cb-btn cb-wa"
               onclick="cbTrack('contact_whatsapp_click', @js($source))">
                <x-icons.whatsapp :size="18" />
                WhatsApp
            </a>
        @endif
        @if(!empty($contact['tel']))
            <a href="{{ $contact['tel'] }}" class="cb-btn cb-call" onclick="cbTrack('contact_call_click', @js($source))">
                <svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                Call
            </a>
        @endif
    </div>
@endif

@once
    <style>
        .cb-row { display: flex; gap: 8px; }
        .cb-btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 44px; padding: 0 12px; border-radius: 10px; font-size: 14px; font-weight: 600; text-decoration: none; white-space: nowrap; transition: filter .15s, background-color .15s; }
        .cb-compact .cb-btn { min-height: 38px; font-size: 13px; }
        .cb-wa { background: #25D366; color: #fff; border: 1px solid #25D366; }
        .cb-wa:hover { filter: brightness(.95); }
        .cb-call { background: #fff; color: #166534; border: 1px solid #86efac; }
        .cb-call:hover { background: #f0fdf4; }
        .cb-btn:focus-visible { outline: 3px solid #128C7E; outline-offset: 2px; }
        /* A member's WhatsApp is on this page: the site's floating help
           button (also green WhatsApp) would be mistaken for it */
        .wa-fab { display: none !important; }
    </style>
    <script>
        function cbTrack(name, source) {
            var p = { source: source };
            try { if (window.gtag) { gtag('event', name, p); } if (window.posthog) { posthog.capture(name, p); } } catch (e) {}
        }
    </script>
@endonce
