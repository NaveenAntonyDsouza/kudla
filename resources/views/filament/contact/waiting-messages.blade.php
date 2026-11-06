{{--
    Contact Inbox → Reply popup: the sender's waiting messages, oldest first. One reply
    answers them all (ContactSubmissionResource::replyAction, 6 Nov 2026).
    Inline styles: custom admin views get no Tailwind build.
--}}
@php
    $muted = 'opacity:.62;';
@endphp
<div style="display:flex;flex-direction:column;gap:.6rem;font-size:.875rem;line-height:1.45;">
    <div style="font-weight:600;">
        @if ($messages->count() > 1)
            This reply answers all {{ $messages->count() }} messages waiting from this sender:
        @else
            Their message:
        @endif
    </div>
    <div style="display:flex;flex-direction:column;gap:.5rem;max-height:16rem;overflow-y:auto;">
        @foreach ($messages as $m)
            <div style="padding:.55rem .75rem;border:1px solid rgba(128,128,128,.22);border-radius:.5rem;background:rgba(128,128,128,.04);">
                <div style="font-size:.78rem;{{ $muted }}">{{ $m->created_at->displayTz()->format('j M Y, g:i A') }} · {{ $m->subject }}</div>
                <div style="white-space:pre-wrap;overflow-wrap:anywhere;">{{ $m->message }}</div>
            </div>
        @endforeach
    </div>
</div>
