{{--
    Pieces of the Log call / Log WhatsApp box (App\Filament\Actions\LogContactAction, 25 Nov 2026):
    a notice, the link for the checklist's link item, and the member's WhatsApp number with the
    ready-made text, to copy into WhatsApp. Copy only: nothing is sent from here.
    Inline styles: custom admin views get no Tailwind build.
--}}
@php
    $box = 'border:1px solid rgba(127,127,127,.3);border-radius:.6rem;padding:.6rem .75rem;font-size:.85rem;display:flex;flex-direction:column;gap:.45rem;';
    $btn = 'display:inline-flex;align-items:center;padding:.25rem .6rem;border-radius:.45rem;border:1px solid rgba(127,127,127,.45);background:transparent;color:inherit;font-size:.78rem;font-weight:600;cursor:pointer;';
@endphp
<div x-data="{
        copied: null,
        copy(text, what) {
            const done = () => { this.copied = what; setTimeout(() => this.copied = null, 1500) };
            if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(text).then(done).catch(() => {}); }
        },
    }" style="display:flex;flex-direction:column;gap:.5rem;">
    @if (! empty($notice))
        <div style="{{ $box }}border-color:rgba(217,119,6,.45);background:rgba(217,119,6,.08);">{{ $notice }}</div>
    @endif

    @if (! empty($link))
        <div style="{{ $box }}">
            <span style="opacity:.7;">The link for "Sent them the search or community link"</span>
            <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;">
                <span style="word-break:break-all;">{{ $link }}</span>
                <button type="button" style="{{ $btn }}" x-on:click="copy(@js($link), 'link')"><span x-text="copied === 'link' ? 'Copied' : 'Copy link'">Copy link</span></button>
            </div>
        </div>
    @endif

    @if (! empty($whatsapp))
        <div style="{{ $box }}">
            @if (! empty($hint))
                <span style="opacity:.75;">{{ $hint }}</span>
            @endif
            @if (! empty($number))
                <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;">
                    <span style="opacity:.7;">Their WhatsApp</span>
                    <span style="font-weight:600;">{{ $number }}</span>
                    <button type="button" style="{{ $btn }}" x-on:click="copy(@js($number), 'number')"><span x-text="copied === 'number' ? 'Copied' : 'Copy number'">Copy number</span></button>
                </div>
            @else
                <span style="color:#dc2626;">No WhatsApp or phone number on the profile.</span>
            @endif
            @if (! empty($text))
                <div style="white-space:pre-wrap;opacity:.9;">{{ $text }}</div>
                <div><button type="button" style="{{ $btn }}" x-on:click="copy(@js($text), 'text')"><span x-text="copied === 'text' ? 'Copied' : 'Copy message'">Copy message</span></button></div>
            @endif
        </div>
    @endif
</div>
