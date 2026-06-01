{{--
    Interaction-log timeline shown at the top of the "Notes" action modal.
    Newest first; each entry shows the interaction type, who logged it, when,
    the follow-up date (if any), and the note text. Append-only — no edit/delete.
    Inline styles (no Tailwind utilities — Filament v5 won't compile them in a
    custom view without a theme build).
--}}
@php
    $typeLabels = \App\Models\ProfileNote::NOTE_TYPES;
    $typeColors = \App\Models\ProfileNote::NOTE_TYPE_COLORS;
@endphp

@if ($notes->isEmpty())
    <p style="opacity:.6;font-size:.875rem;padding:.25rem 0 .5rem;">No notes yet — add the first one below.</p>
@else
    <div style="font-size:.75rem;font-weight:600;opacity:.6;text-transform:uppercase;letter-spacing:.03em;margin-bottom:.4rem;">
        {{ $notes->count() }} {{ \Illuminate\Support\Str::plural('note', $notes->count()) }}
    </div>
    <div style="max-height:42vh;overflow-y:auto;display:flex;flex-direction:column;gap:.55rem;margin-bottom:.5rem;padding-right:.25rem;">
        @foreach ($notes as $note)
            <div style="border:1px solid rgba(128,128,128,0.2);border-radius:.6rem;padding:.6rem .75rem;">
                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.3rem;">
                    <x-admin.pill :color="$typeColors[$note->note_type] ?? 'gray'">{{ $typeLabels[$note->note_type] ?? \Illuminate\Support\Str::headline($note->note_type ?? 'General') }}</x-admin.pill>
                    <span style="font-weight:600;font-size:.8125rem;">{{ $note->adminUser?->name ?? 'Staff' }}</span>
                    <span style="opacity:.55;font-size:.75rem;">{{ $note->created_at?->displayTz()->format('d M Y, h:i A') }}</span>
                    @if ($note->follow_up_date)
                        <span style="margin-left:auto;font-size:.7rem;font-weight:600;color:#b45309;">Follow-up: {{ $note->follow_up_date->format('d M Y') }}</span>
                    @endif
                </div>
                <div style="font-size:.875rem;line-height:1.5;white-space:pre-wrap;opacity:.9;">{{ $note->note }}</div>
            </div>
        @endforeach
    </div>
@endif
