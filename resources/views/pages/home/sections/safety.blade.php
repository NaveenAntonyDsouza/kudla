{{--
    "Your safety comes first" (Oct 2026; Admin → Homepage Content → Garland &
    Harmony sections). Only what the site really does: profiles and photos are
    approved before they show, four photo privacy levels, contact details for
    paid plans only, blocking and Report Misuse. Plus fraud tips and the
    "for marriage, not dating" note.
--}}
@php
    $safetySite = \App\Models\SiteSetting::getValue('site_name', 'our site');
    // Say only what is true on this site: profiles are checked BEFORE they show only when
    // automatic approval is off; contacts need a paid plan only when Free Membership mode is off.
    $reviewedFirst = \App\Models\SiteSetting::getValue('auto_approve_profiles', '1') === '0';
    $paidContacts = ! \App\Models\User::freeMembershipEnabled();
    $safetyItems = [
        $reviewedFirst
            ? ['Screened profiles', 'Our team checks every new profile and photo before it appears on the site.', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z']
            : ['Fake profiles removed', 'Our team removes fake or misleading profiles, and you can report anyone to us.', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
        ['Your photos, your choice', 'Show your photos to everyone, only to members whose interest you accept, only to premium members, or keep them hidden.', 'M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'],
        ['Contact details protected', $paidContacts ? 'Phone numbers and WhatsApp are shown only after an interest is accepted, and only to members on a paid plan.' : 'Phone numbers and WhatsApp are shown only after both of you accept an interest.', 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
        ['Block and report', 'Block any member at any time, and tell our team about anything that worries you.', 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
    ];
@endphp
<style>
    .home-garland .g-safe { display: grid; grid-template-columns: 1fr; gap: 18px; margin-top: 44px; }
    @media (min-width: 640px) { .home-garland .g-safe { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .home-garland .g-safe { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .home-garland .g-safe-card { background: #fff; border: 1px solid var(--g-line); border-radius: 16px; padding: 22px; }
    .home-garland .g-safe-card h3 { margin: 14px 0 6px; font-size: 1rem; font-weight: 700; color: var(--g-ink); }
    .home-garland .g-safe-card p { margin: 0; font-size: 0.9rem; line-height: 1.6; color: var(--g-muted); }
    .home-garland .g-tip { margin: 26px auto 0; max-width: 56rem; display: flex; gap: 14px; align-items: flex-start; background: #fff; border: 1px solid rgba(var(--g-a-rgb), .35); border-left: 4px solid var(--g-a); border-radius: 12px; padding: 16px 18px; }
    .home-garland .g-tip svg { flex: none; color: var(--g-a-ink); margin-top: 1px; }
    .home-garland .g-tip p { margin: 0; font-size: 0.92rem; line-height: 1.6; color: var(--g-ink); }
    .home-garland .g-not-dating { margin: 18px auto 0; text-align: center; font-size: 0.88rem; color: var(--g-muted); }
    .home-garland .g-not-dating a { color: var(--g-p); font-weight: 600; }
</style>
<section class="g-section g-ivory">
    <div class="g-wrap">
        <x-dynamic-component :component="$divider" />
        <h2 class="g-h2">Your Safety Comes First</h2>
        <p class="g-lead">Finding a life partner is a family decision. Here is how we look after your profile, your photos and your privacy.</p>

        <div class="g-safe">
            @foreach($safetyItems as [$title, $text, $icon])
                <div class="g-safe-card">
                    <div class="g-why-icon" style="margin-bottom: 0;"><svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/></svg></div>
                    <h3>{{ $title }}</h3>
                    <p>{{ $text }}</p>
                </div>
            @endforeach
        </div>

        <div class="g-tip" role="note">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <p><b>Stay safe:</b> {{ $safetySite }} will never ask for your password or OTP. Never send money to someone you have not met, and meet for the first time in a public place with your family.</p>
        </div>

        {{-- "not a dating site" is in the footer of every page, so not repeated here (Oct 2026) --}}
        <p class="g-not-dating"><a href="{{ route('report-misuse') }}">Report misuse</a> &middot; <a href="{{ route('privacy') }}">Privacy policy</a></p>
    </div>
</section>
