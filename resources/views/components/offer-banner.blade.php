@php
    // Admin-managed promotional banner (Settings → Homepage Content → Offer Banner).
    // Shown on every app-layout page, or — when "membership only" is on — just the
    // Membership Plans page. All fields edited in the admin panel.
    $offerEnabled  = \App\Models\SiteSetting::getValue('offer_banner_enabled', '0') === '1';
    $offerTitle    = \App\Models\SiteSetting::getValue('offer_banner_title', '');
    $offerText     = \App\Models\SiteSetting::getValue('offer_banner_text', '');
    $offerCode     = trim((string) \App\Models\SiteSetting::getValue('offer_banner_coupon_code', ''));
    $offerDiscount = \App\Models\SiteSetting::getValue('offer_banner_discount_text', '');
    $offerCta      = \App\Models\SiteSetting::getValue('offer_banner_cta_text', 'View Plans');
    // When on, restrict the banner to the Membership Plans page only.
    $offerMembershipOnly = \App\Models\SiteSetting::getValue('offer_banner_membership_only', '0') === '1';
    $offerVisibleHere    = ! $offerMembershipOnly || request()->routeIs('membership.index');
@endphp

@if($offerEnabled && $offerVisibleHere && ($offerTitle || $offerText || $offerCode))
    <div class="text-white" style="background: linear-gradient(90deg, var(--color-primary), var(--color-primary-hover));">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-col sm:flex-row flex-wrap items-center justify-center gap-x-4 gap-y-2 text-center">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                @if($offerTitle)<span class="font-bold text-sm sm:text-base">{{ $offerTitle }}</span>@endif
            </div>
            @if($offerText)<span class="text-xs sm:text-sm text-white/90">{{ $offerText }}</span>@endif
            @if($offerDiscount)<span class="text-xs sm:text-sm font-semibold bg-white/20 px-2 py-0.5 rounded">{{ $offerDiscount }}</span>@endif
            @if($offerCode)
                <span class="inline-flex items-center gap-1.5 text-xs sm:text-sm">
                    <span class="text-white/80">Use code</span>
                    <span class="font-mono font-bold tracking-wider bg-white text-(--color-primary) px-2.5 py-1 rounded-md" style="border:1px dashed rgba(255,255,255,.7);">{{ $offerCode }}</span>
                </span>
            @endif
            <a href="{{ route('membership.index') }}" class="shrink-0 inline-flex items-center gap-1 bg-white text-sm font-bold px-4 py-1.5 rounded-lg hover:bg-white/90 transition-colors" style="color: var(--color-primary);">
                {{ $offerCta ?: 'View Plans' }}
            </a>
        </div>
    </div>
@endif
