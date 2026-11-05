{{--
    Member card above the tabs on the admin member edit page (EditUser::memberCard):
    who this is, how to reach them, what is still empty, the last call. Staff often
    have the member on the phone while on this page (5 Nov 2026).
    Inline styles: the panel has no theme build for custom classes (see x-admin.pill).
--}}
@php
    $chip = 'display:inline-flex;align-items:center;padding:.15rem .6rem;border-radius:9999px;font-size:.78rem;font-weight:600;line-height:1.4;white-space:nowrap;border:1px solid rgba(217,119,6,.45);background:rgba(217,119,6,.10);color:#b45309;';
    $muted = 'opacity:.62;';
@endphp
{{-- flex-wrap: on a phone the facts go below the photo instead of squeezing beside it --}}
<div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-start;padding:1rem 1.1rem;border:1px solid rgba(128,128,128,.22);border-radius:.75rem;background:rgba(128,128,128,.04);">
    <img src="{{ $photo ?: url('/images/default-avatar.svg') }}" alt=""
        onerror="this.onerror=null;this.src='{{ url('/images/default-avatar.svg') }}'"
        style="width:4.25rem;height:4.25rem;border-radius:9999px;object-fit:cover;flex-shrink:0;background:rgba(128,128,128,.12);">

    <div style="flex:1 1 15rem;min-width:0;display:flex;flex-direction:column;gap:.55rem;font-size:.875rem;line-height:1.45;">
        <div style="display:flex;flex-wrap:wrap;align-items:baseline;gap:.25rem .7rem;">
            <span style="font-weight:700;font-size:1.05rem;">{{ $matriId }}</span>
            @if ($facts)
                <span style="opacity:.85;">{{ implode(' · ', $facts) }}</span>
            @endif
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:.35rem;">
            @foreach ($pills as [$label, $color])
                <x-admin.pill :color="$color">{{ $label }}</x-admin.pill>
            @endforeach
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:.3rem 1.4rem;">
            @if ($phone)
                <span><span style="{{ $muted }}">Phone</span> <a href="{{ $phoneHref }}" style="font-weight:600;color:inherit;">{{ $phone }}</a></span>
            @else
                <span style="color:#dc2626;">No phone number</span>
            @endif
            @if ($whatsapp)
                <span><span style="{{ $muted }}">WhatsApp</span> {{ $whatsapp }}</span>
            @endif
            @if ($contactPerson)
                <span><span style="{{ $muted }}">Contact person</span> {{ $contactPerson }}</span>
            @endif
            @if ($callTime)
                <span><span style="{{ $muted }}">Best time to call</span> {{ $callTime }}</span>
            @endif
            @if ($createdBy)
                <span><span style="{{ $muted }}">Profile created by</span> {{ $createdBy }}</span>
            @endif
            <span><span style="{{ $muted }}">Last signed in</span> {{ $lastLogin }}</span>
        </div>

        @if ($ask)
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.35rem .4rem;">
                <span style="font-weight:600;">Missing, ask on this call:</span>
                @foreach ($ask as $item)
                    @if ($item['tab'])
                        {{-- opens that tab (the tab buttons carry data-tab-key) --}}
                        <button type="button" style="{{ $chip }}cursor:pointer;"
                            x-data
                            x-on:click="const tab = document.querySelector('[data-tab-key=\'{{ $item['tab'] }}\']'); if (tab) { tab.click(); tab.scrollIntoView({ block: 'nearest' }) }">
                            {{ $item['label'] }}
                        </button>
                    @else
                        <span style="{{ $chip }}">{{ $item['label'] }}</span>
                    @endif
                @endforeach
            </div>
        @endif

        <div style="display:flex;flex-wrap:wrap;gap:.3rem 1.4rem;font-size:.8125rem;">
            @if ($lastCall)
                <span><span style="{{ $muted }}">Last call</span> {{ $lastCall['when'] }}@if ($lastCall['by']) by {{ $lastCall['by'] }}@endif @if ($lastCall['note'] !== '')<span style="opacity:.8;">— “{{ $lastCall['note'] }}”</span>@endif</span>
            @else
                <span style="{{ $muted }}">No call logged yet (Notes → Type: Call)</span>
            @endif
            @if ($followUp)
                <span @if ($followUp['late']) style="color:#dc2626;font-weight:600;" @endif><span style="{{ $followUp['late'] ? '' : $muted }}">Follow-up {{ $followUp['late'] ? 'was due' : 'due' }}</span> {{ $followUp['date'] }}</span>
            @endif
            @if ($lastEdit)
                <span><span style="{{ $muted }}">Last edited</span> {{ $lastEdit['when'] }}@if ($lastEdit['by']) by {{ $lastEdit['by'] }}@endif</span>
            @endif
        </div>
    </div>
</div>
