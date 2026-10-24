{{--
    Personal matchmaking (Oct 2026; Admin → Homepage Content → Garland & Harmony
    sections; OFF by default: Kudla plans to offer it later). Title, text and
    button are editable; the button opens WhatsApp, else calls the office.
--}}
@php
    $mmTitle = \App\Models\SiteSetting::getValue('assisted_title', '') ?: 'Personal Matchmaking';
    $mmText = \App\Models\SiteSetting::getValue('assisted_text', '') ?: 'Busy, or living abroad? Let our team search for you. A relationship advisor learns what you and your family are looking for, shortlists suitable profiles and speaks to families on your behalf.';
    $mmButton = \App\Models\SiteSetting::getValue('assisted_button_text', '') ?: 'Talk to Our Team';
    $mmPhone = \App\Support\WhatsApp::digits(\App\Models\SiteSetting::getValue('phone', ''));
    $mmHref = \App\Support\WhatsApp::link('Hi, I would like to know about personal matchmaking.') ?: ($mmPhone ? 'tel:+' . $mmPhone : null);
    $mmSteps = [
        ['Meet your advisor', 'A member of our team calls you to understand your family and your expectations.'],
        ['Share your preferences', 'Tell us what matters most: education, work, location, family values.'],
        ['Receive hand-picked matches', 'We shortlist suitable profiles and help both families connect.'],
    ];
@endphp
<style>
    .home-garland .g-mm { display: grid; grid-template-columns: 1fr; gap: 32px; align-items: center; }
    @media (min-width: 900px) { .home-garland .g-mm { grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr); gap: 56px; } }
    .home-garland .g-mm .g-h2, .home-garland .g-mm .g-lead { text-align: left; margin-left: 0; }
    .home-garland .g-mm .g-ornament { justify-content: flex-start !important; margin-left: 0 !important; }
    .home-garland .g-mm .g-ornament > span:first-child { display: none; }
    .home-garland .g-mm-steps { display: grid; gap: 14px; counter-reset: mm; }
    .home-garland .g-mm-step { display: flex; gap: 14px; align-items: flex-start; background: #fff; border: 1px solid var(--g-line); border-radius: 16px; padding: 16px 18px; }
    .home-garland .g-mm-step::before { counter-increment: mm; content: counter(mm); flex: none; width: 34px; height: 34px; border-radius: 999px; display: flex; align-items: center; justify-content: center; background: var(--g-tint); color: var(--g-p); font-weight: 800; font-variant-numeric: lining-nums; }
    .home-garland .g-mm-step b { display: block; font-size: 0.98rem; color: var(--g-ink); }
    .home-garland .g-mm-step span { display: block; margin-top: 3px; font-size: 0.9rem; line-height: 1.55; color: var(--g-muted); }
</style>
<section class="g-section g-white">
    <div class="g-wrap">
        <div class="g-mm">
            <div>
                <x-dynamic-component :component="$divider" />
                <h2 class="g-h2">{{ $mmTitle }}</h2>
                <p class="g-lead">{{ $mmText }}</p>
                @if($mmHref)
                    <a href="{{ $mmHref }}" class="g-btn g-btn-primary" style="margin-top: 24px;" @if(str_starts_with($mmHref, 'https://')) target="_blank" rel="noopener" @endif>{{ $mmButton }}</a>
                @endif
            </div>
            <div class="g-mm-steps">
                @foreach($mmSteps as [$title, $text])
                    <div class="g-mm-step"><span style="margin: 0;"><b>{{ $title }}</b><span>{{ $text }}</span></span></div>
                @endforeach
            </div>
        </div>
    </div>
</section>
