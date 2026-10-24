{{--
    "We're here to help" (Oct 2026; Admin → Homepage Content → Garland & Harmony
    sections). From Site Settings: phone, WhatsApp, email, address; a card shows
    only when its setting is filled in.
--}}
@php
    $helpPhone = \App\Support\WhatsApp::digits(\App\Models\SiteSetting::getValue('phone', ''));
    $helpWhatsApp = \App\Support\WhatsApp::link();
    $helpEmail = \App\Models\SiteSetting::getValue('email', '');
    $helpAddress = \App\Models\SiteSetting::getValue('address', '');
    $pretty = fn (?string $digits) => $digits && strlen($digits) === 12 && str_starts_with($digits, '91')
        ? '+91 ' . substr($digits, 2, 5) . ' ' . substr($digits, 7)
        : ($digits ? '+' . $digits : '');
    $helpCards = array_filter([
        $helpPhone ? ['Call us', $pretty($helpPhone), 'tel:+' . $helpPhone, 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'] : null,
        $helpWhatsApp ? ['WhatsApp', 'Chat with our team', $helpWhatsApp, 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'] : null,
        filled($helpEmail) ? ['Email', $helpEmail, 'mailto:' . $helpEmail, 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'] : null,
        filled($helpAddress) ? ['Visit our office', $helpAddress, null, 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'] : null,
    ]);
@endphp
@if(count($helpCards))
<style>
    .home-garland .g-help { display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 40px; }
    @media (min-width: 640px) { .home-garland .g-help { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .home-garland .g-help.n-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } .home-garland .g-help.n-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .home-garland .g-help-card { display: flex; gap: 14px; align-items: flex-start; background: #fff; border: 1px solid var(--g-line); border-radius: 16px; padding: 18px; color: inherit; text-decoration: none; min-width: 0; transition: border-color .15s, box-shadow .15s; }
    .home-garland a.g-help-card:hover { border-color: var(--g-p); box-shadow: 0 12px 26px -18px rgba(40,6,50,.45); }
    .home-garland .g-help-card .g-why-icon { margin: 0; flex: none; }
    .home-garland .g-help-card b { display: block; font-size: 0.95rem; color: var(--g-ink); }
    .home-garland .g-help-card span { display: block; margin-top: 3px; font-size: 0.9rem; line-height: 1.5; color: var(--g-muted); overflow-wrap: anywhere; }
</style>
<section class="g-section g-ivory">
    <div class="g-wrap">
        <x-dynamic-component :component="$divider" />
        <h2 class="g-h2">We're Here to Help</h2>
        <p class="g-lead">Questions about registering, your profile or our plans? Talk to our team.</p>
        <div class="g-help n-{{ count($helpCards) }}">
            @foreach($helpCards as [$title, $text, $href, $icon])
                @if($href)
                    <a class="g-help-card" href="{{ $href }}" @if(str_starts_with($href, 'https://')) target="_blank" rel="noopener" @endif>
                @else
                    <div class="g-help-card">
                @endif
                    <span class="g-why-icon"><svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/></svg></span>
                    <span style="margin: 0;"><b>{{ $title }}</b><span>{{ $text }}</span></span>
                @if($href) </a> @else </div> @endif
            @endforeach
        </div>
    </div>
</section>
@endif
