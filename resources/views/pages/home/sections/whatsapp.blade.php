{{--
    "Follow us on WhatsApp" (Nov 2026; Admin → Homepage Content → Garland & Harmony
    sections). Shown only when the site has a WhatsApp channel link (Site Settings →
    Social Links), so sites without a channel show nothing. Phones get a big button
    (it opens WhatsApp); computers also get a QR code to scan with the phone camera.
--}}
@php
    $waChannel = \App\Support\WhatsApp::channelUrl();
    $waSite = \App\Models\SiteSetting::getValue('site_name', 'our site');
    $waQr = $waChannel ? \Illuminate\Support\Facades\Cache::rememberForever(
        'home-wa-qr:' . md5($waChannel),
        fn () => app(\App\Services\QrCodeService::class)->dataUri($waChannel, 180)
    ) : null;
@endphp
@if($waChannel)
<style>
    .home-garland .g-wa { display: grid; grid-template-columns: 1fr; gap: 32px; align-items: center; max-width: 60rem; margin: 0 auto; }
    @media (min-width: 768px) { .home-garland .g-wa { grid-template-columns: minmax(0, 1fr) auto; gap: 48px; } }
    .home-garland .g-wa .g-h2, .home-garland .g-wa .g-lead { text-align: left; margin-left: 0; }
    /* the ornament sits at the start of the left-aligned heading, its fine line trailing right */
    .home-garland .g-wa .g-ornament { justify-content: flex-start !important; margin-left: 0 !important; }
    .home-garland .g-wa .g-ornament > span:first-child { display: none; }
    .home-garland .g-wa ul { list-style: none; margin: 22px 0 28px; padding: 0; display: grid; gap: 12px; }
    .home-garland .g-wa li { display: flex; gap: 12px; align-items: flex-start; font-size: 0.95rem; line-height: 1.55; color: var(--g-ink); }
    .home-garland .g-wa li svg { flex: none; margin-top: 2px; color: #128C4B; }
    .home-garland .g-btn-wa { background: #0B7A4B; color: #fff; padding: 14px 26px; font-size: 1rem; }
    .home-garland .g-btn-wa:hover { background: #086640; }
    .home-garland .g-wa-qr { display: none; text-align: center; background: #fff; border: 1px solid var(--g-line); border-radius: 18px; padding: 18px 18px 14px; box-shadow: 0 18px 36px -24px rgba(40,6,50,.35); }
    @media (min-width: 768px) { .home-garland .g-wa-qr { display: block; } }
    .home-garland .g-wa-qr img { display: block; width: 180px; height: 180px; }
    .home-garland .g-wa-qr p { margin: 10px 0 0; font-size: 0.82rem; color: var(--g-muted); }
</style>
<section class="g-section g-white">
    <div class="g-wrap">
        <div class="g-wa">
            <div>
                <x-dynamic-component :component="$divider" />
                <h2 class="g-h2">Follow {{ $waSite }} on WhatsApp</h2>
                <p class="g-lead">Join our official WhatsApp channel for new profiles, tips and news, right in your WhatsApp.</p>
                <ul>
                    <li><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"/></svg><span>Your phone number stays private: we and the other followers cannot see it.</span></li>
                    <li><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"/></svg><span>Only updates from us. It is not a group, so no messages from strangers.</span></li>
                    <li><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"/></svg><span>Free. Mute or leave whenever you like.</span></li>
                </ul>
                <a href="{{ $waChannel }}" target="_blank" rel="noopener" class="g-btn g-btn-wa">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Follow on WhatsApp
                </a>
            </div>
            <div class="g-wa-qr">
                <img src="{{ $waQr }}" alt="QR code for the {{ $waSite }} WhatsApp channel" width="180" height="180" loading="lazy">
                <p>On a computer? Scan with<br>your phone's camera.</p>
            </div>
        </div>
    </div>
</section>
@endif
