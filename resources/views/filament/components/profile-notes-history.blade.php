{{--
    Member interaction-log timeline — the single history view used everywhere:
    the members-list "Notes" action, the View-page "Notes" action (both via
    App\Filament\Actions\ProfileNotesAction), and the Follow-up Report's "Notes"
    popup + the context shown inside its "Done" dialog.

    Newest first; each entry shows the interaction type, who logged it, when, the
    follow-up status (✓ done / overdue / scheduled), and the note text.
    Append-only — no edit/delete. Inline styles (Filament v5 won't compile
    Tailwind utilities in a custom view without a theme build).

    Expects:
      $notes   — Collection<ProfileNote> (newest first), adminUser eager-loaded
      $compact — bool (optional): tighter height + "Recent notes" caption, for
                 the in-dialog context block (caller passes an already-trimmed set)
--}}
@php
    $typeLabels = \App\Models\ProfileNote::NOTE_TYPES;
    $typeColors = \App\Models\ProfileNote::NOTE_TYPE_COLORS;
    $compact = $compact ?? false;
@endphp

@if ($notes->isEmpty())
    <p style="opacity:.6;font-size:.875rem;padding:.25rem 0 .5rem;">No notes yet — add the first one below.</p>
@else
    <div style="font-size:.75rem;font-weight:600;opacity:.6;text-transform:uppercase;letter-spacing:.03em;margin-bottom:.4rem;">
        @if ($compact)
            Recent notes
        @else
            {{ $notes->count() }} {{ \Illuminate\Support\Str::plural('note', $notes->count()) }}
        @endif
    </div>
    <div style="max-height:{{ $compact ? '13rem' : '42vh' }};overflow-y:auto;display:flex;flex-direction:column;gap:.55rem;margin-bottom:.5rem;padding-right:.25rem;">
        @foreach ($notes as $note)
            <div style="border:1px solid rgba(128,128,128,0.2);border-radius:.6rem;padding:.6rem .75rem;">
                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.3rem;">
                    <x-admin.pill :color="$typeColors[$note->note_type] ?? 'gray'">{{ $typeLabels[$note->note_type] ?? \Illuminate\Support\Str::headline($note->note_type ?? 'General') }}</x-admin.pill>
                    <span style="font-weight:600;font-size:.8125rem;">{{ $note->adminUser?->name ?? 'Staff' }}</span>
                    <span style="opacity:.55;font-size:.75rem;">{{ $note->created_at?->displayTz()->format('d M Y, h:i A') }}</span>
                    @if ($note->follow_up_date)
                        @php
                            $done = $note->follow_up_completed_at !== null;
                            $overdue = ! $done && $note->follow_up_date->isPast();
                        @endphp
                        <span style="margin-left:auto;font-size:.7rem;font-weight:600;color:{{ $done ? '#059669' : ($overdue ? '#dc2626' : '#b45309') }};">
                            {{ $done ? '✓ Follow-up done' : 'Follow-up: ' . $note->follow_up_date->format('d M Y') }}
                        </span>
                    @endif
                </div>
                <div style="font-size:.875rem;line-height:1.5;white-space:pre-wrap;opacity:.9;">{{ $note->note }}</div>
            </div>
        @endforeach
    </div>
@endif
