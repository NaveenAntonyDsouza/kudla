{{-- Members → Assisted Members (App\Filament\Pages\AssistedMembers). Inline styles only:
     custom admin views get no Tailwind build. --}}
@php
    $th = 'padding:.45rem .5rem;font-weight:600;text-align:left;white-space:nowrap;';
    $td = 'padding:.5rem .5rem;vertical-align:top;';
    $input = 'padding:.35rem .55rem;border:1px solid rgba(127,127,127,.4);border-radius:.5rem;background:transparent;color:inherit;font-size:.875rem;';
    $muted = 'opacity:.7;font-size:.8rem;';
    $stageColor = ['waiting_member' => 'warning', 'member_no' => 'gray', 'to_send' => 'danger', 'sent' => 'info', 'accepted' => 'success', 'declined' => 'gray', 'closed' => 'gray', 'meeting' => 'primary'];
    $staffStage = ['waiting_member' => 'Waiting for the member', 'member_no' => 'Member said no', 'to_send' => 'Member said yes: send it', 'sent' => 'Interest sent', 'accepted' => 'Accepted', 'declined' => 'Declined', 'closed' => 'Closed', 'meeting' => 'Meeting arranged'];
@endphp

<x-filament-panels::page>
@if ($mode === 'list')
    <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
        @if ($seesAll)
            <x-filament::button size="sm" :color="$mine ? 'gray' : 'primary'" :outlined="$mine" wire:click="$set('mine', false)">Everyone</x-filament::button>
            <x-filament::button size="sm" :color="$mine ? 'primary' : 'gray'" :outlined="! $mine" wire:click="$set('mine', true)">Only mine</x-filament::button>
        @endif
        <span style="margin-left:auto;{{ $muted }}">Weekly target: {{ $target }} matches per member (BharatMatrimony shares 4–5, Jeevansathi 2–3).</span>
    </div>

    <x-filament::section heading="Members on an assisted plan" description="Furthest behind the weekly target first. Open a member to suggest matches, send the ones they said yes to, and answer interests on their instruction.">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                <thead>
                    <tr style="opacity:.7;font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;">
                        <th style="{{ $th }}padding-left:0;">Member</th>
                        <th style="{{ $th }}">Plan</th>
                        <th style="{{ $th }}">Manager</th>
                        <th style="{{ $th }}">Consent</th>
                        <th style="{{ $th }}">This week</th>
                        <th style="{{ $th }}">Waiting for member</th>
                        <th style="{{ $th }}">To send</th>
                        <th style="{{ $th }}">Interests to answer</th>
                        <th style="{{ $th }}padding-right:0;">Last note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $r)
                        @php $p = $r['profile']; @endphp
                        <tr wire:key="am-{{ $p->id }}" style="border-top:1px solid rgba(127,127,127,.2);{{ $r['plan'] ? '' : 'opacity:.6;' }}">
                            <td style="{{ $td }}padding-left:0;">
                                <a href="#" wire:click.prevent="open({{ $p->id }})" style="font-weight:600;color:inherit;text-decoration:underline;text-decoration-color:rgba(127,127,127,.5);">{{ $p->full_name }}</a>
                                <div style="{{ $muted }}">{{ $p->matri_id }}</div>
                            </td>
                            <td style="{{ $td }}">
                                @if ($r['plan']){{ $r['plan'] }}<div style="{{ $muted }}">until {{ $r['ends']?->displayTz()->format('d M Y') ?? 'no end' }}</div>
                                @else<x-admin.pill>Plan ended</x-admin.pill>@endif
                            </td>
                            <td style="{{ $td }}">{!! $p->relationshipManager ? e($p->relationshipManager->name) : '<span style="color:#dc2626;font-weight:600;">None yet</span>' !!}</td>
                            <td style="{{ $td }}">
                                @if ($r['consent'])<x-admin.pill color="success">Given {{ $r['consent']->displayTz()->format('d M') }}</x-admin.pill>
                                @else<x-admin.pill color="warning">Not yet</x-admin.pill>@endif
                            </td>
                            <td style="{{ $td }}font-weight:700;{{ $r['plan'] && $r['week'] < $target ? 'color:#dc2626;' : '' }}">{{ $r['week'] }} / {{ $target }}</td>
                            <td style="{{ $td }}">{{ $r['waiting_member'] ?: '—' }}</td>
                            <td style="{{ $td }}{{ $r['to_send'] ? 'color:#dc2626;font-weight:700;' : '' }}">{{ $r['to_send'] ?: '—' }}</td>
                            <td style="{{ $td }}{{ $r['to_answer'] ? 'font-weight:700;' : '' }}">{{ $r['to_answer'] ?: '—' }}</td>
                            <td style="{{ $td }}padding-right:0;white-space:nowrap;">{{ $r['last_note']?->displayTz()->format('d M') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" style="padding:.75rem 0;opacity:.75;">
                            {{ $mine ? 'No assisted members are assigned to you.' : 'No member is on an assisted plan yet. Mark a plan "Assisted Service" (Money → Membership Plans), then assign it on Change Plan and choose the manager.' }}
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
@else
    <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;">
        <x-filament::button size="sm" color="gray" outlined wire:click="open(null)">← All assisted members</x-filament::button>
        <a href="{{ $memberUrl($member->id) }}" style="font-weight:600;">Open {{ $member->full_name }} ({{ $member->matri_id }}) in Members →</a>
    </div>

    <x-filament::section :heading="$member->full_name . ' (' . $member->matri_id . ')'">
        <div style="display:flex;gap:2rem;flex-wrap:wrap;font-size:.875rem;">
            <div><div style="{{ $muted }}">Plan</div>
                @if ($plan)<strong>{{ $plan->plan_name }}</strong> until {{ $ends?->displayTz()->format('d M Y') ?? 'no end' }}@else<x-admin.pill color="danger">No assisted plan now</x-admin.pill>@endif</div>
            <div><div style="{{ $muted }}">Relationship manager</div><strong>{{ $member->relationshipManager?->name ?? 'None yet' }}</strong></div>
            <div><div style="{{ $muted }}">Consent</div>
                @if ($member->assisted_consent_at)
                    <x-admin.pill color="success">Given {{ $member->assisted_consent_at->displayTz()->format('d M Y, h:i A') }}</x-admin.pill>
                    @if ($member->assisted_auto_send)<div style="{{ $muted }}margin-top:.2rem;">May send interests without asking</div>@else<div style="{{ $muted }}margin-top:.2rem;">Asks before each interest</div>@endif
                @else<x-admin.pill color="warning">Not yet</x-admin.pill><div style="{{ $muted }}margin-top:.2rem;">The member ticks it on their site: Menu → My Assisted Service</div>@endif</div>
            <div><div style="{{ $muted }}">This week</div><strong style="{{ $thisWeek < $target ? 'color:#dc2626;' : '' }}">{{ $thisWeek }} / {{ $target }}</strong> matches suggested</div>
        </div>
        @if ($seesAll)
            <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;margin-top:1rem;">
                <select wire:model="newManagerId" style="{{ $input }}">
                    <option value="">{{ $member->relationshipManager ? 'Change manager…' : 'Choose manager…' }}</option>
                    @foreach ($staff as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                </select>
                <x-filament::button size="sm" color="gray" wire:click="changeManager">Save manager</x-filament::button>
            </div>
        @endif
        @if ($refusal)
            <div style="margin-top:1rem;padding:.6rem .8rem;border-radius:.5rem;background:rgba(217,119,6,.12);color:#b45309;font-size:.875rem;font-weight:600;">You can't work on this member now: {{ $refusal }}</div>
        @endif
    </x-filament::section>

    <x-filament::section heading="Suggest a match" description="The member must be allowed to see the profile. They get a notice and say yes or no; yes sends the interest in their name, marked as from you.">
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
            <input type="text" wire:model="suggestId" placeholder="Matri ID" style="{{ $input }}width:9rem;">
            <input type="text" wire:model="suggestNote" placeholder="Why this match (the member sees it)" maxlength="500" style="{{ $input }}flex:1 1 18rem;">
            <x-filament::button size="sm" wire:click="suggest" :disabled="(bool) $refusal">Suggest</x-filament::button>
        </div>
        @if ($candidates->isNotEmpty())
            <div style="margin-top:1rem;{{ $muted }}">Best matches by score, not suggested yet:</div>
            <div style="display:flex;flex-direction:column;margin-top:.25rem;">
                @foreach ($candidates as $c)
                    <div wire:key="cand-{{ $c->id }}" style="display:flex;gap:.75rem;align-items:center;padding:.4rem 0;border-top:1px solid rgba(127,127,127,.2);font-size:.875rem;flex-wrap:wrap;">
                        <span style="min-width:3rem;font-weight:700;">{{ $c->score }}%</span>
                        <a href="{{ $memberUrl($c->id) }}" target="_blank" style="font-weight:600;min-width:7rem;">{{ $c->matri_id }}</a>
                        <span style="flex:1 1 12rem;">{{ $c->full_name }}{{ $c->age ? ', '.$c->age : '' }}</span>
                        <x-filament::button size="xs" color="gray" wire:click="suggest({{ $c->id }})" :disabled="(bool) $refusal">Suggest</x-filament::button>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>

    <x-filament::section heading="Interests the member received" description="Answer only when the member has told you what to do. Your note of what they said is kept and shown to the member.">
        @forelse ($received as $i)
            <div wire:key="rcv-{{ $i->id }}" style="display:flex;gap:.6rem;align-items:center;padding:.5rem 0;border-top:1px solid rgba(127,127,127,.2);font-size:.875rem;flex-wrap:wrap;">
                <span style="min-width:6rem;{{ $muted }}">{{ $i->created_at->displayTz()->format('d M') }}</span>
                @if ($i->senderProfile)<a href="{{ $memberUrl($i->senderProfile->id) }}" target="_blank" style="font-weight:600;min-width:7rem;">{{ $i->senderProfile->matri_id }}</a>
                <span style="min-width:10rem;">{{ $i->senderProfile->full_name }}</span>@endif
                <select wire:model="replyHow.{{ $i->id }}" style="{{ $input }}">
                    <option value="">Member told me by…</option>
                    @foreach ($how as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                </select>
                <input type="text" wire:model="replySaid.{{ $i->id }}" maxlength="200" placeholder="What the member said" style="{{ $input }}flex:1 1 14rem;">
                <x-filament::button size="xs" color="success" wire:click="reply({{ $i->id }}, true)" :disabled="(bool) $refusal">Accept</x-filament::button>
                <x-filament::button size="xs" color="danger" outlined wire:click="reply({{ $i->id }}, false)" :disabled="(bool) $refusal">Decline</x-filament::button>
            </div>
        @empty
            <p style="margin:0;opacity:.75;">No interest is waiting for this member's answer.</p>
        @endforelse
    </x-filament::section>

    <x-filament::section heading="Matches suggested" :description="$recs->count() . ' in all'">
        @forelse ($recs as $rec)
            @php $stage = $rec->stage(); $o = $rec->recommendedProfile; @endphp
            <div wire:key="rec-{{ $rec->id }}" style="display:flex;gap:.6rem;align-items:center;padding:.5rem 0;border-top:1px solid rgba(127,127,127,.2);font-size:.875rem;flex-wrap:wrap;">
                <span style="min-width:6rem;{{ $muted }}">{{ $rec->created_at->displayTz()->format('d M') }}</span>
                @if ($o)<a href="{{ $memberUrl($o->id) }}" target="_blank" style="font-weight:600;min-width:7rem;">{{ $o->matri_id }}</a><span style="min-width:10rem;">{{ $o->full_name }}{{ $o->trashed() ? ' (deleted)' : '' }}</span>@endif
                <x-admin.pill :color="$stageColor[$stage] ?? 'gray'">{{ $staffStage[$stage] ?? $stage }}</x-admin.pill>
                @if ($rec->member_decision === 'auto')<span style="{{ $muted }}">sent without asking (member's choice)</span>@endif
                @if ($rec->meeting_on)<span style="{{ $muted }}">{{ $rec->meeting_on->format('d M Y') }}{{ $rec->meeting_note ? ' · '.$rec->meeting_note : '' }}</span>@endif
                <span style="flex:1 1 8rem;{{ $muted }}">{{ $rec->admin_note }}</span>
                @if ($stage === 'to_send')
                    <x-filament::button size="xs" wire:click="sendInterest({{ $rec->id }})" :disabled="(bool) $refusal">Send interest</x-filament::button>
                @elseif ($stage === 'accepted')
                    <input type="date" wire:model="meetingOn.{{ $rec->id }}" style="{{ $input }}">
                    <input type="text" wire:model="meetingNote.{{ $rec->id }}" maxlength="255" placeholder="Where / with whom" style="{{ $input }}width:12rem;">
                    <x-filament::button size="xs" color="gray" wire:click="arrangeMeeting({{ $rec->id }})" :disabled="(bool) $refusal">Meeting arranged</x-filament::button>
                @endif
            </div>
        @empty
            <p style="margin:0;opacity:.75;">Nothing suggested yet.</p>
        @endforelse
    </x-filament::section>

    <x-filament::section heading="History" description="What the member sees on their Assisted page, latest first.">
        @forelse ($history as $e)
            <div wire:key="ev-{{ $e->id }}" style="display:flex;gap:.75rem;padding:.4rem 0;border-top:1px solid rgba(127,127,127,.2);font-size:.875rem;flex-wrap:wrap;">
                <span style="min-width:9rem;{{ $muted }}">{{ $e->created_at?->displayTz()->format('d M Y, h:i A') }}</span>
                <span style="flex:1 1 16rem;">{{ $describe($e) }}</span>
            </div>
        @empty
            <p style="margin:0;opacity:.75;">Nothing yet.</p>
        @endforelse
    </x-filament::section>
@endif
</x-filament-panels::page>
