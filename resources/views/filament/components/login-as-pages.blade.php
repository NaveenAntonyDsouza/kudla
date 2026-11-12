{{-- Audit Trail → Login as Member → "Pages opened". Inline styles: custom admin
     views get no Tailwind build. --}}
@php
    $tz = config('app.display_timezone', 'Asia/Kolkata');
    $pages = $record->pages ?? [];
@endphp
<div style="font-size: 0.875rem; color: #374151;">
    <table style="width: 100%;">
        <tr style="border-bottom: 1px solid #f3f4f6;">
            <td style="padding: 0.5rem 0; color: #6b7280; width: 30%;">Staff</td>
            <td style="padding: 0.5rem 0; font-weight: 500;">{{ $record->staff?->name ?? '(deleted account)' }}</td>
        </tr>
        <tr style="border-bottom: 1px solid #f3f4f6;">
            <td style="padding: 0.5rem 0; color: #6b7280;">Reason</td>
            <td style="padding: 0.5rem 0;">{{ $record->reason }}</td>
        </tr>
        <tr style="border-bottom: 1px solid #f3f4f6;">
            <td style="padding: 0.5rem 0; color: #6b7280;">Started</td>
            <td style="padding: 0.5rem 0;">{{ $record->started_at?->copy()->timezone($tz)->format('M j, Y g:i:s A') }}</td>
        </tr>
        <tr style="border-bottom: 1px solid #f3f4f6;">
            <td style="padding: 0.5rem 0; color: #6b7280;">Ended</td>
            <td style="padding: 0.5rem 0;">
                @if($record->ended_at)
                    {{ $record->ended_at->copy()->timezone($tz)->format('M j, Y g:i:s A') }}
                    ({{ \App\Models\LoginAsSession::ENDED[$record->ended_how] ?? $record->ended_how }})
                @else
                    Still open
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding: 0.5rem 0; color: #6b7280;">Refused</td>
            <td style="padding: 0.5rem 0;">{{ $record->blocked_actions }} button {{ $record->blocked_actions === 1 ? 'press' : 'presses' }} (view only)</td>
        </tr>
    </table>

    <p style="margin: 1rem 0 0.5rem; font-weight: 600;">{{ $record->pages_opened }} {{ $record->pages_opened === 1 ? 'page' : 'pages' }} opened</p>
    @if($pages)
        <div style="max-height: 320px; overflow: auto; background: #f9fafb; border-radius: 0.375rem; padding: 0.5rem 0.75rem; font-size: 0.8125rem;">
            @foreach($pages as $page)
                <div style="display: flex; gap: 0.75rem; padding: 0.15rem 0;">
                    <span style="color: #6b7280; white-space: nowrap;">{{ $page['t'] ?? '' }}</span>
                    <span style="word-break: break-all;">{{ $page['p'] ?? '' }}</span>
                </div>
            @endforeach
        </div>
        <p style="margin-top: 0.5rem; color: #6b7280; font-size: 0.75rem;">Addresses only: what was typed into a search is not kept.</p>
    @else
        <p style="color: #6b7280;">No page was opened.</p>
    @endif
</div>
