{{--
    Contact Inbox → message page: everything the same sender (email address) sent,
    newest first, with each one's status and any reply (6 Nov 2026).
    Inline styles: custom admin views get no Tailwind build.
--}}
@php
    $muted = 'opacity:.62;';
    $colors = ['new' => 'danger', 'in_progress' => 'warning', 'replied' => 'success', 'closed' => 'gray'];
@endphp
<div style="display:flex;flex-direction:column;gap:.6rem;font-size:.875rem;line-height:1.45;">
    @foreach ($messages as $m)
        <div style="padding:.6rem .8rem;border:1px solid {{ $m->getKey() === $current ? 'rgba(217,119,6,.55)' : 'rgba(128,128,128,.22)' }};border-radius:.5rem;background:rgba(128,128,128,.04);">
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.3rem .6rem;margin-bottom:.25rem;">
                <span style="font-size:.78rem;{{ $muted }}">{{ $m->created_at->displayTz()->format('j M Y, g:i A') }}</span>
                <x-admin.pill :color="$colors[$m->status] ?? 'gray'">{{ str_replace('_', ' ', ucfirst($m->status)) }}</x-admin.pill>
                @if ($m->getKey() === $current)
                    <span style="font-size:.78rem;font-weight:600;color:#b45309;">this message</span>
                @endif
                <span style="font-weight:600;">{{ $m->subject }}</span>
            </div>
            <div style="white-space:pre-wrap;overflow-wrap:anywhere;">{{ $m->message }}</div>
            @if (filled($m->admin_reply))
                <div style="margin-top:.4rem;padding-left:.6rem;border-left:3px solid rgba(22,163,74,.5);">
                    <span style="font-size:.78rem;{{ $muted }}">Reply{{ $m->replied_at ? ' · ' . $m->replied_at->displayTz()->format('j M Y, g:i A') : '' }}</span>
                    <div style="white-space:pre-wrap;overflow-wrap:anywhere;">{{ $m->admin_reply }}</div>
                </div>
            @endif
        </div>
    @endforeach
</div>
