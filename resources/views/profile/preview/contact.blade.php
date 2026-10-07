@php
    $c = $profile->contactInfo;
    $s = $profile->socialMediaLink;
    $nm = fn($v) => $v ?: 'Not Mentioned';
    $isOwn = $isOwn ?? false;
@endphp

@php
    // Who may see this member's phone / WhatsApp, and if not, why — the same
    // answer as the profile panel and phone bar (App\Support\ContactAccess).
    // Numbers are only in $access['contact'] when it's open.
    $access = $isOwn ? null : \App\Support\ContactAccess::for(auth()->user()?->profile, $profile);
    $state = $access['state'] ?? null;
    $ca = \App\Support\ContactAccess::class;
    $hasPhone = filled($c?->primary_phone) || filled($user?->phone);
    $hasWhatsApp = filled($c?->whatsapp_number) || $hasPhone;
    $pendingInterest = ($interestStatus ?? null)['interest'] ?? null;
    // Social handles are a way to message someone too, so they open with the
    // contact (Oct 2026) — locked, only the names show as "opens once
    // connected". Plain web links only (http/https).
    $socialLinks = collect([
        ['label' => 'Instagram', 'url' => $s?->instagram_url],
        ['label' => 'Facebook', 'url' => $s?->facebook_url],
        ['label' => 'LinkedIn', 'url' => $s?->linkedin_url],
        ['label' => 'YouTube', 'url' => $s?->youtube_url],
        ['label' => 'Website', 'url' => $s?->website_url],
    ])->filter(fn ($l) => is_string($l['url']) && preg_match('#^https?://#i', trim($l['url'])));
@endphp

@if(!$isOwn)
    <style>
        .ct-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 22px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
        .ct-head { display: flex; align-items: flex-start; gap: 14px; }
        .ct-icon { flex-shrink: 0; width: 44px; height: 44px; border-radius: 9999px; display: flex; align-items: center; justify-content: center; }
        .ct-title { margin: 0; font-size: 17px; font-weight: 600; color: #111827; }
        .ct-text { margin: 4px 0 0; font-size: 14px; line-height: 1.5; color: #4b5563; }
        .ct-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
        .ct-chip { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 9999px; background: #f3f4f6; color: #374151; font-size: 12px; font-weight: 500; }
        .ct-cta { display: inline-flex; align-items: center; justify-content: center; gap: 7px; margin-top: 16px; min-height: 44px; padding: 0 20px; border-radius: 10px; border: 0; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; }
        .ct-cta-primary { background: var(--color-primary, #8B1D91); color: #fff; }
        .ct-cta-green { background: #16a34a; color: #fff; }
        .ct-link { display: inline-block; margin-top: 10px; margin-left: 4px; font-size: 13px; color: var(--color-primary, #8B1D91); }
        .ct-rows { margin-top: 18px; border-top: 1px solid #f3f4f6; }
        .ct-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f3f4f6; }
        .ct-label { font-size: 12px; color: #6b7280; }
        .ct-value { font-size: 15px; font-weight: 600; color: #111827; text-decoration: none; word-break: break-all; }
        .ct-value:hover { color: var(--color-primary, #8B1D91); }
        .ct-safety { display: flex; gap: 8px; margin-top: 16px; padding: 10px 12px; border-radius: 8px; background: #f9fafb; font-size: 12.5px; line-height: 1.5; color: #4b5563; }
    </style>
@endif

@if(!$isOwn && $state !== $ca::OPEN)
    {{-- Locked: what's needed, and the one thing to do next --}}
    <div class="ct-card">
        <div class="ct-head">
            <span class="ct-icon" style="background: #fef3c7; color: #b45309;">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
            </span>
            <div style="flex: 1; min-width: 0;">
                <h3 class="ct-title">
                    @switch($state)
                        @case($ca::NO_INTEREST) Want to talk? Send an interest first @break
                        @case($ca::SENT_PENDING) Waiting for their reply @break
                        @case($ca::RECEIVED_PENDING) They're interested in you @break
                        @case($ca::NEEDS_PLAN) You're connected — upgrade to call or WhatsApp @break
                        @default Contact details not available
                    @endswitch
                </h3>
                <p class="ct-text">{{ \App\Support\ContactAccess::lockedReason((string) $state) }}</p>

                @if(in_array($state, [$ca::NO_INTEREST, $ca::SENT_PENDING, $ca::RECEIVED_PENDING, $ca::NEEDS_PLAN], true) && ($hasPhone || $hasWhatsApp || $socialLinks->isNotEmpty()))
                    <div class="ct-chips" aria-label="Available once connected">
                        @if($hasPhone)<span class="ct-chip">&#128222; Mobile number</span>@endif
                        @if($hasWhatsApp)<span class="ct-chip">&#128172; WhatsApp</span>@endif
                        @if(filled($c?->preferred_call_time))<span class="ct-chip">&#128336; Best time to call</span>@endif
                        {{-- names only — the links open once connected --}}
                        @foreach($socialLinks as $social)<span class="ct-chip">&#128279; {{ $social['label'] }}</span>@endforeach
                    </div>
                @endif

                @if($state === $ca::NO_INTEREST)
                    <button type="button" class="ct-cta ct-cta-primary" x-data @click.stop="$dispatch('open-interest-modal', { source: 'contact_tab' })">
                        <svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                        Send Interest
                    </button>
                @elseif($state === $ca::RECEIVED_PENDING && $pendingInterest)
                    <a href="{{ route('interests.show', $pendingInterest) }}" class="ct-cta ct-cta-green">Accept or reply</a>
                @elseif($state === $ca::NEEDS_PLAN)
                    <a href="{{ route('membership.index') }}" class="ct-cta ct-cta-primary">View membership plans</a>
                    @if($pendingInterest)
                        <div><a href="{{ route('interests.show', $pendingInterest) }}" class="ct-link">Or chat here meanwhile &rarr;</a></div>
                    @endif
                @elseif($state === $ca::SENT_PENDING)
                    <div><a href="{{ route('matches.index') }}" class="ct-link">Meanwhile, see more matches &rarr;</a></div>
                @endif
            </div>
        </div>
    </div>
@elseif(!$isOwn)
    {{-- Open: talk on WhatsApp or call, with who answers and when --}}
    @php $k = $access['contact']; @endphp
    <div class="ct-card">
        <div class="ct-head" style="margin-bottom: 16px;">
            <span class="ct-icon" style="background: #dcfce7; color: #16a34a;">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
            </span>
            <div style="flex: 1; min-width: 0;">
                <h3 class="ct-title">Contact {{ $profile->matri_id }}</h3>
                @if($k['person'] || $k['relationship'])
                    <p class="ct-text">You'll be talking to <strong>{{ $k['person'] ?: 'the family' }}</strong>@if($k['relationship'] && strcasecmp($k['relationship'], 'self') !== 0) ({{ $k['relationship'] }})@elseif($k['relationship']) (the member)@endif</p>
                @endif
                @if($k['best_time'])
                    <p class="ct-text">&#128336; Best time to call: <strong>{{ $k['best_time'] }}</strong></p>
                @endif
            </div>
        </div>

        <x-contact-buttons :contact="$k" source="contact_tab" />

        <div class="ct-rows">
            @if($k['phone_display'])
                <div class="ct-row">
                    <div><div class="ct-label">Mobile</div>
                        @if($k['tel'])<a href="{{ $k['tel'] }}" class="ct-value" onclick="cbTrack('contact_call_click', 'contact_tab_number')">{{ $k['phone_display'] }}</a>@else<span class="ct-value">{{ $k['phone_display'] }}</span>@endif
                    </div>
                </div>
            @endif
            @if($k['whatsapp_display'])
                <div class="ct-row">
                    <div><div class="ct-label">WhatsApp{{ $k['whatsapp_is_phone'] ? ' (same as mobile)' : '' }}</div>
                        <a href="{{ $k['whatsapp_link'] }}" target="_blank" rel="noopener" class="ct-value" onclick="cbTrack('contact_whatsapp_click', 'contact_tab_number')">{{ $k['whatsapp_display'] }}</a>
                    </div>
                </div>
            @endif
            @if($k['email'])
                <div class="ct-row">
                    <div><div class="ct-label">Email</div><a href="mailto:{{ $k['email'] }}" class="ct-value">{{ $k['email'] }}</a></div>
                </div>
            @endif
            {{-- social handles open together with the phone / WhatsApp --}}
            @foreach($socialLinks as $social)
                <div class="ct-row">
                    <div style="min-width: 0;"><div class="ct-label">{{ $social['label'] }}</div>
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener nofollow" class="ct-value" style="font-size: 14px; font-weight: 500;"
                           onclick="cbTrack('contact_social_click', @js(strtolower($social['label'])))">{{ preg_replace('#^https?://(www\.)?#i', '', rtrim($social['url'], '/')) }}</a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="ct-safety">
            <span aria-hidden="true">&#128737;&#65039;</span>
            <span>Stay safe: involve your family, meet in public places, and never send money. <a href="{{ route('report.create', $profile) }}" style="color: var(--color-primary, #8B1D91);">Report a concern</a></span>
        </div>
    </div>

@else
    {{-- Own profile — show all contact details --}}

    {{-- Primary Contact Details --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 mb-6">
        <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Primary Contact Details</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
            <div><p class="text-xs text-gray-500">Mobile Number</p><p class="text-sm font-semibold text-gray-900">+91 {{ $user->phone ?? 'Not Mentioned' }}</p></div>
            <div><p class="text-xs text-gray-500">Custodian Name</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c?->contact_person) }}</p></div>
            <div><p class="text-xs text-gray-500">Custodian Relation</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c?->contact_relationship) }}</p></div>
            <div><p class="text-xs text-gray-500">Preferred Time To Reach You</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c?->preferred_call_time) }}</p></div>
        </div>
    </div>

    {{-- Other Contact Details --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 mb-6">
        <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Other Contact Details</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
            <div><p class="text-xs text-gray-500">Residential Landline No.</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c?->residential_phone_number) }}</p></div>
            <div><p class="text-xs text-gray-500">WhatsApp Number</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c?->whatsapp_number) }}</p></div>
            <div><p class="text-xs text-gray-500">Secondary Mobile No.</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c?->secondary_phone) }}</p></div>
            <div><p class="text-xs text-gray-500">Alternate Email</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c?->alternate_email) }}</p></div>
        </div>
    </div>

    {{-- Candidate's Address --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 mb-6">
        <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Candidate's Address</h3>
        <div class="space-y-4">
            <div><p class="text-xs text-gray-500">Communication Address</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c?->communication_address) }}{{ $c?->pincode ? ', ' . $c->pincode : '' }}</p></div>
            @if($c?->present_address)
                <div><p class="text-xs text-gray-500">Present Address</p><p class="text-sm font-semibold text-gray-900">{{ $c->present_address }}{{ $c->present_pin_zip_code ? ', ' . $c->present_pin_zip_code : '' }}</p></div>
            @endif
            @if($c?->permanent_address)
                <div><p class="text-xs text-gray-500">Permanent Address</p><p class="text-sm font-semibold text-gray-900">{{ $c->permanent_address }}{{ $c->permanent_pin_zip_code ? ', ' . $c->permanent_pin_zip_code : '' }}</p></div>
            @endif
        </div>
    </div>

    {{-- Reference Person --}}
    @if($c?->reference_name)
    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6 mb-6">
        <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Reference Person's Details</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
            <div><p class="text-xs text-gray-500">Name</p><p class="text-sm font-semibold text-gray-900">{{ $c->reference_name }}</p></div>
            <div><p class="text-xs text-gray-500">Relationship with Candidate</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c->reference_relationship) }}</p></div>
            <div><p class="text-xs text-gray-500">Mobile No.</p><p class="text-sm font-semibold text-gray-900">{{ $nm($c->reference_mobile) }}</p></div>
        </div>
    </div>
    @endif

    {{-- Social Media --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
        <h3 class="text-lg font-semibold text-(--color-primary) mb-4">Social Media Information</h3>
        <div class="space-y-3">
            @foreach([
                ['label' => 'Facebook', 'url' => $s?->facebook_url],
                ['label' => 'Instagram', 'url' => $s?->instagram_url],
                ['label' => 'LinkedIn', 'url' => $s?->linkedin_url],
                ['label' => 'YouTube', 'url' => $s?->youtube_url],
                ['label' => 'Website', 'url' => $s?->website_url],
            ] as $social)
                <div class="flex items-center gap-3 py-2 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <span class="text-sm font-medium text-gray-600 w-20 shrink-0">{{ $social['label'] }}</span>
                    <p class="text-sm font-medium text-gray-900 truncate">
                        @if($social['url'])
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="text-(--color-primary) hover:underline">{{ $social['url'] }}</a>
                        @else
                            Not Mentioned
                        @endif
                    </p>
                </div>
            @endforeach
        </div>
    </div>
@endif
