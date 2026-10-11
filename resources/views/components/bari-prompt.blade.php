{{--
    "Add your bari" (Oct 2026). The full Tulu bari list went live on 10 Oct,
    but members don't go back to their religious details: of 417 kudla
    members in bari castes only 27 had a real bari, and none added one in
    the first day. Shown on the dashboard to members of a bari caste whose
    bari is empty, "Don't Know" or a bare "Other". "Not now" hides it for
    14 days on this device. Counted as bari_prompt_click / bari_prompt_later.
--}}
@props(['profile'])

@php
    try {
        $bpRi = $profile?->religiousInfo;
        $bpShow = $bpRi
            && in_array($bpRi->religion, ['Hindu', 'Jain'], true)
            && \App\Support\GotraBari::lineage($bpRi->caste) === 'bari'
            && \App\Support\GotraBari::normalize($bpRi->gotra) === null;
    } catch (\Throwable) {
        $bpShow = false;
    }
@endphp

@if($bpShow)
    <div class="bari-prompt" x-data="{ bpHidden: (() => { try { return Number(localStorage.getItem('bariPromptHiddenUntil') || 0) > Date.now(); } catch (e) { return false; } })() }"
         x-show="!bpHidden"
         style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; padding: 16px 20px; border-radius: 8px; background: var(--brand-primary-light, #F3E8F7); border: 1px solid rgba(139, 29, 145, 0.25);">
        <span style="flex: 1; min-width: 220px;">
            <span style="display: block; font-size: 14px; font-weight: 600; color: var(--brand-primary, #8B1D91);">Add your bari</span>
            <span style="display: block; font-size: 12.5px; color: #4b5563; margin-top: 2px;">Families often ask about the bari before a proposal. We've added the full list of Tulu baris, so you can now choose yours in a few seconds.</span>
        </span>
        <span style="display: flex; align-items: center; gap: 10px;">
            <button type="button" style="font-size: 12.5px; color: #6b7280; background: none; border: 0; cursor: pointer; padding: 6px;"
                @click="bpHidden = true; try { localStorage.setItem('bariPromptHiddenUntil', String(Date.now() + 14 * 86400000)); } catch (e) {} if (window.gtag) { gtag('event', 'bari_prompt_later', {}); } if (window.posthog) { posthog.capture('bari_prompt_later'); }">Not now</button>
            <a href="{{ route('profile.show', ['section' => 'religious', 'edit' => 1]) }}"
               onclick="if (window.gtag) { gtag('event', 'bari_prompt_click', {}); } if (window.posthog) { posthog.capture('bari_prompt_click'); }"
               style="font-size: 13px; font-weight: 600; color: #fff; background: var(--brand-primary, #8B1D91); padding: 8px 14px; border-radius: 6px; text-decoration: none; white-space: nowrap;">Add my bari &rarr;</a>
        </span>
    </div>
@endif
