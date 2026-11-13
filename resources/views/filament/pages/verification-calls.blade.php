{{-- Today → Verification Calls (App\Filament\Pages\VerificationCalls). Inline styles only:
     custom admin views get no Tailwind build. --}}
@php
    $th = 'padding:.45rem .5rem;font-weight:600;text-align:left;white-space:nowrap;';
    $td = 'padding:.55rem .5rem;vertical-align:top;';
    $muted = 'opacity:.7;font-size:.8rem;';
    $num = 'padding:.45rem .5rem;text-align:right;white-space:nowrap;';
    $link = 'font-weight:600;color:inherit;text-decoration:underline;text-decoration-color:rgba(127,127,127,.5);';
    $select = 'padding:.25rem .4rem;border:1px solid rgba(127,127,127,.4);border-radius:.4rem;background:transparent;color:inherit;font-size:.8rem;max-width:11rem;';
@endphp

<x-filament-panels::page>
    {{-- on a phone: the member (with phone and status) and the buttons; the other columns hide --}}
    <style>
        .vc-narrow { display: none; }
        @media (max-width: 640px) { .vc-wide { display: none; } .vc-narrow { display: block; } }
    </style>

    @if ($report)
        <x-filament::section heading="This week" :description="'On the list: members who joined from '.$from->displayTz()->format('j M Y').'. '.$report['total'].' members, '.$report['verified'].' verified, '.$report['openTotal'].' still to call.'">
            @if ($report['rows']->isEmpty())
                <p style="font-size:.875rem;">
                    No staff member takes verification calls yet, so every new member waits under <strong>Anyone can call</strong>.
                    Give the right <strong>Take Verification Calls</strong> to a role in Staff Roles (it is on for Staff, Branch Staff and Telecaller).
                </p>
            @else
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                        <thead>
                            <tr style="opacity:.7;font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;">
                                <th style="{{ $th }}padding-left:0;">Staff</th>
                                <th style="{{ $num }}">Open now</th>
                                <th style="{{ $num }}">Due now</th>
                                <th style="{{ $num }}">Waiting {{ $anyoneDays }}+ days</th>
                                <th style="{{ $num }}">Calls this week</th>
                                <th style="{{ $num }}">Verified</th>
                                <th style="{{ $num }}padding-right:0;">Could not reach</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['rows'] as $r)
                                <tr style="border-top:1px solid rgba(127,127,127,.2);">
                                    <td style="{{ $td }}padding-left:0;font-weight:600;">{{ $r['name'] }} @if ($r['away'])<x-admin.pill color="warning">{{ $r['away'] }}</x-admin.pill>@endif</td>
                                    <td style="{{ $num }}">{{ $r['open'] ?: '—' }}</td>
                                    <td style="{{ $num }}{{ $r['due'] ? 'font-weight:700;' : '' }}">{{ $r['due'] ?: '—' }}</td>
                                    <td style="{{ $num }}{{ $r['late'] ? 'color:#dc2626;font-weight:700;' : '' }}">{{ $r['late'] ?: '—' }}</td>
                                    <td style="{{ $num }}">{{ $r['calls'] ?: '—' }}</td>
                                    <td style="{{ $num }}">{{ $r['verified'] ?: '—' }}</td>
                                    <td style="{{ $num }}padding-right:0;">{{ $r['not_reached'] ?: '—' }}</td>
                                </tr>
                            @endforeach
                            @if ($report['unassigned'])
                                <tr style="border-top:1px solid rgba(127,127,127,.2);">
                                    <td style="{{ $td }}padding-left:0;" colspan="7">Not given to anyone: <strong>{{ $report['unassigned'] }}</strong> (under Anyone can call)</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    @endif

    @if ($isCaller)
        <p style="{{ $muted }}font-size:.85rem;margin:0;">
            On the call: open the member, ask them to tell you their date of birth and the other details (don't read them out), fill in what is missing,
            ask for a photo on WhatsApp if there is none, then press <strong>Save and mark checked on call</strong>: that is "verified".
            No answer? Press <strong>No answer</strong> and send the WhatsApp message. After {{ $maxNoAnswer }} tries the call closes.
        </p>
    @endif

    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
        @foreach ($tabs as $key => $label)
            <x-filament::button size="sm" :color="$tab === $key ? 'primary' : 'gray'" :outlined="$tab !== $key" wire:click="$set('tab', '{{ $key }}')">
                {{ $label }} <span style="opacity:.8;margin-left:.2rem;">{{ $counts[$key] }}</span>
            </x-filament::button>
        @endforeach
    </div>

    <x-filament::section>
        {{-- managers: tick several calls and give them to one person (or to anyone) --}}
        @if ($canPick && $rows->isNotEmpty())
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;margin-bottom:.75rem;font-size:.875rem;">
                <span>Give the ticked calls to</span>
                <select style="{{ $select }}max-width:14rem;" wire:model="giveTo" aria-label="Give the ticked calls to">
                    <option value="">Anyone</option>
                    @foreach ($callers as $id => [$name, $branch])
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <x-filament::button size="sm" wire:click="givePicked">Give</x-filament::button>
                <span style="{{ $muted }}">The box in the heading ticks every call shown.</span>
            </div>
        @endif
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                <thead>
                    <tr style="opacity:.7;font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;">
                        @if ($canPick)
                            <th style="{{ $th }}padding-left:0;width:1.5rem;">
                                <input type="checkbox" aria-label="Tick every call shown" x-data
                                    x-bind:checked="$wire.picked.length > 0 && $wire.picked.length >= {{ $rows->count() }}"
                                    x-on:change="$wire.picked = $event.target.checked ? @js($rows->pluck('id')->map(fn ($id) => (string) $id)->all()) : []">
                            </th>
                        @endif
                        <th style="{{ $th }}padding-left:0;">Member</th>
                        <th class="vc-wide" style="{{ $th }}">Joined</th>
                        <th class="vc-wide" style="{{ $th }}">Phone</th>
                        <th class="vc-wide" style="{{ $th }}">Profile</th>
                        <th class="vc-wide" style="{{ $th }}">Status</th>
                        <th class="vc-wide" style="{{ $th }}">Given to</th>
                        <th style="{{ $th }}padding-right:0;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $p = $row->profile;
                            $phone = $p?->user?->phone;
                            $whats = $p?->contactInfo?->whatsapp_number;
                            $may = $mayWork($row);
                            $late = $isLate($row);
                        @endphp
                        @continue (! $p)
                        <tr wire:key="vc-{{ $row->id }}" style="border-top:1px solid rgba(127,127,127,.2);">
                            @if ($canPick)
                                <td style="{{ $td }}padding-left:0;">
                                    <input type="checkbox" value="{{ $row->id }}" wire:model="picked" aria-label="Tick {{ $p->matri_id }}">
                                </td>
                            @endif
                            <td style="{{ $td }}padding-left:0;">
                                <a href="{{ $callUrl($row) }}" style="{{ $link }}">{{ $p->full_name }}</a>
                                <div style="{{ $muted }}">{{ $p->matri_id }}@if ($p->gender) · {{ ucfirst($p->gender) }}@endif @if ($p->age) · {{ $p->age }} yrs @endif</div>
                                <div class="vc-narrow" style="margin-top:.3rem;">
                                    @if ($phone)
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" style="color:inherit;font-weight:600;">{{ $phone }}</a>
                                    @endif
                                    <div style="margin-top:.2rem;"><x-admin.pill :color="$row->color()">{{ $row->label() }}</x-admin.pill></div>
                                    <div style="{{ $muted }}">joined {{ $p->created_at?->diffForHumans() }} · {{ (int) $p->profile_completion_pct }}% filled @if ($row->attempts) · {{ $row->attempts }} {{ $row->attempts === 1 ? 'try' : 'tries' }} @endif @if ($late) · <span style="color:#dc2626;font-weight:600;">waiting {{ $anyoneDays }}+ days</span> @endif</div>
                                </div>
                            </td>
                            <td class="vc-wide" style="{{ $td }}white-space:nowrap;">{{ $p->created_at?->diffForHumans() }}</td>
                            <td class="vc-wide" style="{{ $td }}white-space:nowrap;">
                                @if ($phone)
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" style="color:inherit;font-weight:600;">{{ $phone }}</a>
                                @else
                                    <span style="color:#dc2626;">No phone</span>
                                @endif
                                @if ($whats && preg_replace('/[^0-9]/', '', $whats) !== preg_replace('/[^0-9]/', '', (string) $phone))
                                    <div style="{{ $muted }}">WhatsApp {{ $whats }}</div>
                                @endif
                            </td>
                            <td class="vc-wide" style="{{ $td }}">
                                <span style="{{ (int) $p->profile_completion_pct < 60 ? 'color:#dc2626;font-weight:600;' : '' }}">{{ (int) $p->profile_completion_pct }}% filled</span>
                                @unless ($p->primaryPhoto)
                                    <div><x-admin.pill color="warning">No photo</x-admin.pill></div>
                                @endunless
                            </td>
                            <td class="vc-wide" style="{{ $td }}">
                                <x-admin.pill :color="$row->color()">{{ $row->label() }}</x-admin.pill>
                                <div style="{{ $muted }}">
                                    @if ($row->attempts){{ $row->attempts }} {{ $row->attempts === 1 ? 'try' : 'tries' }}@else Not called yet @endif
                                    @if ($row->isOpen() && $row->due_at)
                                        · @if ($row->due_at->isFuture()) due {{ $row->due_at->displayTz()->format('j M, g:i A') }}
                                        @elseif ($late)<span style="color:#dc2626;font-weight:600;">due since {{ $row->due_at->displayTz()->format('j M') }}</span>
                                        @else due now @endif
                                    @endif
                                    @if (! $row->isOpen() && $row->done_at)
                                        · {{ $row->done_at->displayTz()->format('j M') }}@if ($row->doneBy) by {{ $row->doneBy->name }}@endif
                                    @endif
                                </div>
                            </td>
                            <td class="vc-wide" style="{{ $td }}">
                                @if ($manages && $row->isOpen())
                                    <select style="{{ $select }}" wire:change="assign({{ $row->id }}, $event.target.value)" aria-label="Give this call to">
                                        <option value="" @selected(! $row->assigned_to)>Anyone</option>
                                        @foreach ($callers as $id => [$name, $branch])
                                            @continue ($branch !== null && $p->branch_id !== null && (int) $branch !== (int) $p->branch_id)
                                            <option value="{{ $id }}" @selected((int) $row->assigned_to === (int) $id)>{{ $name }}</option>
                                        @endforeach
                                        @if ($row->assigned_to && ! array_key_exists($row->assigned_to, $callers))
                                            <option value="{{ $row->assigned_to }}" selected>{{ $row->caller?->name ?? 'Someone' }} (no longer calls)</option>
                                        @endif
                                    </select>
                                @else
                                    {{ $row->caller?->name ?? 'Anyone' }}
                                    @if ($row->isOpen() && $forAnyone($row) && $row->assigned_to)
                                        <div style="{{ $muted }}">open to anyone</div>
                                    @endif
                                @endif
                            </td>
                            <td style="{{ $td }}padding-right:0;white-space:nowrap;">
                                <div style="display:flex;gap:.35rem;flex-wrap:wrap;justify-content:flex-end;">
                                    @if ($row->isOpen())
                                        <x-filament::button size="xs" tag="a" :href="$callUrl($row)" icon="heroicon-m-phone">Call</x-filament::button>
                                        @if ($may)
                                            <x-filament::button size="xs" color="gray" wire:click="noAnswer({{ $row->id }})" wire:confirm="No answer from {{ $p->matri_id }}? It comes back on the list tomorrow.">No answer</x-filament::button>
                                            @if ($url = $missedUrl($row))
                                                <x-filament::button size="xs" color="success" tag="a" :href="$url" target="_blank" rel="noopener" wire:click="loggedWhatsApp({{ $row->id }})"
                                                    title="Opens the 'we tried to call you' message on WhatsApp, and notes it">WhatsApp</x-filament::button>
                                            @endif
                                        @endif
                                    @else
                                        <x-filament::button size="xs" color="gray" tag="a" :href="$callUrl($row)">Open</x-filament::button>
                                        @if ($may && $row->status !== 'verified')
                                            <x-filament::button size="xs" color="gray" wire:click="reopen({{ $row->id }})">Call again</x-filament::button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canPick ? 8 : 7 }}" style="{{ $td }}padding-left:0;opacity:.7;">
                                @switch($tab)
                                    @case('mine') No calls waiting for you. New members are shared out every 10 minutes. @break
                                    @case('anyone') Nothing waiting: every call is with someone, and none has waited {{ $anyoneDays }} days. @break
                                    @case('check') No member whose details did not match. @break
                                    @case('done') No calls finished in the last 30 days. @break
                                    @default No calls waiting.
                                @endswitch
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rows->count() >= 200)
            <p style="{{ $muted }}margin-top:.5rem;">Showing the first 200.</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
