{{--
    Floating "Chat on WhatsApp" button (bottom-right). Shown when the site has
    a WhatsApp number and Site Settings → "WhatsApp chat button" is on. The
    chat opens with a message saying what the visitor is looking at and their
    member ID, so whoever answers can help straight away.
    Inline styles: the live CSS build is not rebuilt on deploy.
--}}
@if (\App\Support\WhatsApp::buttonEnabled())
    <a href="{{ \App\Support\WhatsApp::link(\App\Support\WhatsApp::prefilledMessage(request())) }}"
       class="wa-fab" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp" title="Chat with us on WhatsApp"
       onclick="if (window.gtag) { gtag('event', 'whatsapp_click', { page_route: @js((string) request()->route()?->getName()) }); }">
        <svg viewBox="0 0 32 32" width="30" height="30" aria-hidden="true" fill="#fff"><path d="M16.04 3C9 3 3.28 8.72 3.28 15.76c0 2.25.59 4.45 1.71 6.39L3.17 29l7.03-1.84a12.7 12.7 0 0 0 5.84 1.42h.01c7.04 0 12.76-5.72 12.76-12.76C28.81 8.72 23.08 3 16.04 3zm0 23.43h-.01a10.6 10.6 0 0 1-5.4-1.48l-.39-.23-4.17 1.09 1.11-4.06-.25-.42a10.58 10.58 0 0 1-1.62-5.6c0-5.86 4.77-10.63 10.64-10.63 2.84 0 5.51 1.11 7.52 3.12a10.56 10.56 0 0 1 3.11 7.52c0 5.87-4.77 10.64-10.62 10.64zm5.83-7.96c-.32-.16-1.89-.93-2.18-1.04-.29-.11-.5-.16-.72.16-.21.32-.82 1.04-1.01 1.25-.19.21-.37.24-.69.08-.32-.16-1.35-.5-2.57-1.59-.95-.85-1.59-1.9-1.78-2.22-.19-.32-.02-.49.14-.65.14-.14.32-.37.48-.56.16-.19.21-.32.32-.53.11-.21.05-.4-.03-.56-.08-.16-.72-1.73-.98-2.37-.26-.62-.52-.54-.72-.55h-.61c-.21 0-.56.08-.85.4-.29.32-1.12 1.09-1.12 2.66s1.15 3.09 1.31 3.3c.16.21 2.26 3.45 5.47 4.84.76.33 1.36.53 1.83.68.77.24 1.47.21 2.02.13.62-.09 1.89-.77 2.16-1.52.27-.75.27-1.39.19-1.52-.08-.13-.29-.21-.61-.37z"/></svg>
    </a>
    <style>
        .wa-fab { position: fixed; right: 18px; bottom: calc(18px + env(safe-area-inset-bottom, 0px)); z-index: 40; width: 56px; height: 56px; border-radius: 50%; background: #25D366; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(0,0,0,0.22); transition: transform 0.15s ease, box-shadow 0.15s ease; }
        .wa-fab:hover { transform: scale(1.06); box-shadow: 0 6px 18px rgba(0,0,0,0.28); }
        .wa-fab:focus-visible { outline: 3px solid #128C7E; outline-offset: 3px; }
        @media (max-width: 640px) { .wa-fab { right: 14px; bottom: calc(14px + env(safe-area-inset-bottom, 0px)); width: 52px; height: 52px; } }
        @media print { .wa-fab { display: none; } }
    </style>
@endif
