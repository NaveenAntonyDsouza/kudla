{{-- Insights → Staff Activity (App\Filament\Pages\StaffActivity). Inline styles only:
     custom admin views get no Tailwind build. --}}
@php
    $th = 'padding:.45rem .5rem;font-weight:600;text-align:right;white-space:nowrap;';
    $td = 'padding:.5rem .5rem;text-align:right;white-space:nowrap;';
    $zero = fn ($v) => $v ? number_format($v) : '<span style="opacity:.35">0</span>';
    $card = 'flex:1 1 9rem;border:1px solid rgba(127,127,127,.25);border-radius:.75rem;padding:.75rem 1rem;';
    $label = 'font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;opacity:.7;font-weight:600;';
    $big = 'font-size:1.6rem;font-weight:700;margin-top:.15rem;';
@endphp

<x-filament-panels::page>
    {{-- Period --}}
    <div style="display:flex;gap:.4rem;flex-wrap:wrap;align-items:center;">
        @foreach ($ranges as $value => $text)
            <x-filament::button size="sm" :color="$range === $value ? 'primary' : 'gray'" :outlined="$range !== $value" wire:click="$set('range', '{{ $value }}')">{{ $text }}</x-filament::button>
        @endforeach
        @if ($range === 'custom')
            <span style="display:inline-flex;gap:.4rem;align-items:center;margin-left:.25rem;">
                <input type="date" wire:model.live="from" style="padding:.3rem .5rem;border:1px solid rgba(127,127,127,.4);border-radius:.5rem;background:transparent;color:inherit;">
                <span>to</span>
                <input type="date" wire:model.live="to" style="padding:.3rem .5rem;border:1px solid rgba(127,127,127,.4);border-radius:.5rem;background:transparent;color:inherit;">
            </span>
        @endif
        <span style="margin-left:auto;opacity:.75;font-size:.875rem;">{{ $periodLabel }}</span>
    </div>

    {{-- Totals --}}
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
        <div style="{{ $card }}"><div style="{{ $label }}">Calls</div><div style="{{ $big }}">{{ number_format($totals['calls']) }}</div></div>
        <div style="{{ $card }}"><div style="{{ $label }}">WhatsApp</div><div style="{{ $big }}">{{ number_format($totals['whatsapp']) }}</div></div>
        <div style="{{ $card }}"><div style="{{ $label }}">Email / meeting / walk-in</div><div style="{{ $big }}">{{ number_format($totals['other_contacts']) }}</div></div>
        <div style="{{ $card }}"><div style="{{ $label }}">Members contacted</div><div style="{{ $big }}">{{ number_format($totals['members_contacted']) }}</div></div>
        <div style="{{ $card }}"><div style="{{ $label }}">Follow-ups done</div><div style="{{ $big }}">{{ number_format($totals['followups_done']) }}</div>
            @if ($totals['followups_late'])<div style="font-size:.75rem;opacity:.75;">{{ $totals['followups_late'] }} after the date</div>@endif</div>
        <div style="{{ $card }}"><div style="{{ $label }}">Details checked on call</div><div style="{{ $big }}">{{ number_format($totals['details_checked']) }}</div></div>
    </div>

    {{-- Per staff member --}}
    <x-filament::section
        :heading="$everyone ? 'Each staff member' : 'Your figures'"
        description="From the Notes popup (the type picked: Call, WhatsApp…), the Follow-up Report and the member pages. Click a name for their days and notes.">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                <thead>
                    <tr style="opacity:.7;font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;">
                        <th style="{{ $th }}text-align:left;padding-left:0;">Staff</th>
                        <th style="{{ $th }}">Calls</th>
                        <th style="{{ $th }}">WhatsApp</th>
                        <th style="{{ $th }}" title="Email, meeting, walk-in">Other contact</th>
                        <th style="{{ $th }}">Members contacted</th>
                        <th style="{{ $th }}" title="Notes of type General">Plain notes</th>
                        <th style="{{ $th }}">Follow-ups set</th>
                        <th style="{{ $th }}">Follow-ups done</th>
                        <th style="{{ $th }}">Details checked</th>
                        <th style="{{ $th }}">Member edits</th>
                        <th style="{{ $th }}">Approvals</th>
                        <th style="{{ $th }}">Photos approved</th>
                        <th style="{{ $th }}">Plans assigned</th>
                        <th style="{{ $th }}" title="Login as member visits">Login as</th>
                        <th style="{{ $th }}">Sign-ins</th>
                        <th style="{{ $th }}padding-right:0;">Days active</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $r)
                        <tr wire:key="staff-{{ $r['staff_id'] }}" style="border-top:1px solid rgba(127,127,127,.2);{{ $r['total'] === 0 ? 'opacity:.6;' : '' }}{{ $selected === $r['staff_id'] ? 'background:rgba(139,29,145,.08);' : '' }}">
                            <td style="{{ $td }}text-align:left;padding-left:0;">
                                @if ($everyone)
                                    <a href="#" wire:click.prevent="pick({{ $r['staff_id'] }})" style="font-weight:600;color:inherit;text-decoration:underline;text-decoration-color:rgba(127,127,127,.5);">{{ $r['name'] }}</a>
                                @else
                                    <span style="font-weight:600;">{{ $r['name'] }}</span>
                                @endif
                                @unless ($r['is_active'])<span style="opacity:.6;font-size:.75rem;"> (inactive)</span>@endunless
                            </td>
                            <td style="{{ $td }}font-weight:700;">{!! $zero($r['calls']) !!}</td>
                            <td style="{{ $td }}">{!! $zero($r['whatsapp']) !!}</td>
                            <td style="{{ $td }}">{!! $zero($r['other_contacts']) !!}</td>
                            <td style="{{ $td }}">{!! $zero($r['members_contacted']) !!}</td>
                            <td style="{{ $td }}">{!! $zero($r['general_notes']) !!}</td>
                            <td style="{{ $td }}">{!! $zero($r['followups_set']) !!}</td>
                            <td style="{{ $td }}">{!! $zero($r['followups_done']) !!}@if ($r['followups_late'])<span style="opacity:.7;font-size:.75rem;"> ({{ $r['followups_late'] }} late)</span>@endif</td>
                            <td style="{{ $td }}">{!! $zero($r['details_checked']) !!}</td>
                            <td style="{{ $td }}">{!! $zero($r['member_edits']) !!}</td>
                            <td style="{{ $td }}">{!! $zero($r['approvals']) !!}</td>
                            <td style="{{ $td }}">{!! $zero($r['photos_approved']) !!}</td>
                            <td style="{{ $td }}">{!! $zero($r['plans_assigned']) !!}</td>
                            <td style="{{ $td }}">{!! $zero($r['login_as']) !!}@if ($r['login_as'])<span style="opacity:.7;font-size:.75rem;"> ({{ $r['login_as_minutes'] }} min)</span>@endif</td>
                            <td style="{{ $td }}">{!! $zero($r['sign_ins']) !!}</td>
                            <td style="{{ $td }}padding-right:0;">{!! $zero($r['days_active']) !!}</td>
                        </tr>
                    @empty
                        <tr><td colspan="16" style="padding:.75rem 0;opacity:.75;">No staff accounts.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    {{-- One staff member: day by day, then the notes --}}
    @if ($selected)
        <x-filament::section :heading="$selectedName . ': day by day'" description="First and last action is the time of the first and last thing they did that day (notes, follow-ups, edits, approvals); signing in doesn't count.">
            @if ($days === [])
                <p style="margin:0;opacity:.75;">Nothing recorded in this period.</p>
            @else
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                        <thead>
                            <tr style="opacity:.7;font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;">
                                <th style="{{ $th }}text-align:left;padding-left:0;">Day</th>
                                <th style="{{ $th }}">Calls</th>
                                <th style="{{ $th }}">WhatsApp</th>
                                <th style="{{ $th }}">Other contact</th>
                                <th style="{{ $th }}">Members contacted</th>
                                <th style="{{ $th }}">Follow-ups done</th>
                                <th style="{{ $th }}">Details checked</th>
                                <th style="{{ $th }}">Edits / approvals</th>
                                <th style="{{ $th }}">First action</th>
                                <th style="{{ $th }}padding-right:0;">Last action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($days as $d)
                                <tr wire:key="day-{{ $d['day'] }}" style="border-top:1px solid rgba(127,127,127,.2);">
                                    <td style="{{ $td }}text-align:left;padding-left:0;font-weight:600;">{{ \Carbon\Carbon::parse($d['day'])->format('D j M') }}</td>
                                    <td style="{{ $td }}font-weight:700;">{!! $zero($d['calls']) !!}</td>
                                    <td style="{{ $td }}">{!! $zero($d['whatsapp']) !!}</td>
                                    <td style="{{ $td }}">{!! $zero($d['other_contacts']) !!}</td>
                                    <td style="{{ $td }}">{!! $zero($d['members_contacted']) !!}</td>
                                    <td style="{{ $td }}">{!! $zero($d['followups_done']) !!}</td>
                                    <td style="{{ $td }}">{!! $zero($d['details_checked']) !!}</td>
                                    <td style="{{ $td }}">{!! $zero($d['member_edits'] + $d['approvals'] + $d['photos_approved'] + $d['plans_assigned']) !!}</td>
                                    <td style="{{ $td }}">{{ $d['first'] ?? '—' }}</td>
                                    <td style="{{ $td }}padding-right:0;">{{ $d['last'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section :heading="$selectedName . ': notes written'" :description="$notes->count() >= 100 ? 'The latest 100 in this period.' : null">
            @if ($notes->isEmpty())
                <p style="margin:0;opacity:.75;">No notes in this period.</p>
            @else
                <div style="display:flex;flex-direction:column;">
                    @foreach ($notes as $n)
                        <div wire:key="note-{{ $n->id }}" style="display:flex;gap:.75rem;padding:.5rem 0;border-top:1px solid rgba(127,127,127,.2);font-size:.875rem;align-items:baseline;flex-wrap:wrap;">
                            <span style="min-width:8.5rem;opacity:.75;white-space:nowrap;">{{ $n->created_at?->timezone(\App\Support\StaffActivity::tz())->format('D j M, g:i A') }}</span>
                            <x-admin.pill :color="\App\Models\ProfileNote::NOTE_TYPE_COLORS[$n->note_type] ?? 'gray'">{{ $noteTypes[$n->note_type] ?? ucfirst((string) $n->note_type) }}</x-admin.pill>
                            @if ($n->profile)
                                <a href="{{ $memberUrl($n->profile->id) }}" style="font-weight:600;white-space:nowrap;">{{ $n->profile->matri_id }}</a>
                            @endif
                            <span style="flex:1 1 16rem;">{{ \Illuminate\Support\Str::limit((string) $n->note, 220) }}</span>
                            @if ($n->follow_up_date)
                                <span style="opacity:.75;white-space:nowrap;">Follow-up {{ $n->follow_up_date->format('j M') }}{{ $n->follow_up_completed_at ? ' · done' : '' }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    @elseif ($everyone)
        <p style="margin:0;opacity:.75;font-size:.875rem;">Click a staff member's name to see their days and the notes they wrote.</p>
    @endif
</x-filament-panels::page>
