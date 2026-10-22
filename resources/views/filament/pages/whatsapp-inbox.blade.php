<x-filament-panels::page>
    @php
        $conversations = $this->conversations();
        $thread = $this->thread();
        $profile = $this->selectedProfile();
        $hoursLeft = $this->windowHoursLeft();
    @endphp

    <style>
        .wa-inbox { display: grid; grid-template-columns: minmax(0, 340px) minmax(0, 1fr); gap: 1rem; align-items: start; }
        .wa-list, .wa-chat { border: 1px solid rgba(127,127,127,.25); border-radius: .75rem; overflow: hidden; }
        .wa-list-scroll { max-height: 70vh; overflow-y: auto; }
        .wa-row { display: block; width: 100%; text-align: left; padding: .65rem .85rem; border-top: 1px solid rgba(127,127,127,.15); cursor: pointer; background: transparent; }
        .wa-row:hover { background: rgba(127,127,127,.08); }
        .wa-row.is-active { background: rgba(37,211,102,.12); }
        .wa-row-top { display: flex; justify-content: space-between; gap: .5rem; font-size: .875rem; }
        .wa-row-preview { font-size: .78rem; opacity: .65; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: .15rem; }
        .wa-unread { background: #16a34a; color: #fff; border-radius: 999px; font-size: .7rem; font-weight: 700; padding: 0 .45rem; line-height: 1.4rem; }
        .wa-head { padding: .75rem 1rem; border-bottom: 1px solid rgba(127,127,127,.2); display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; justify-content: space-between; }
        .wa-messages { height: 55vh; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: .5rem; background: rgba(127,127,127,.05); }
        .wa-bubble { max-width: 78%; padding: .5rem .75rem; border-radius: .75rem; font-size: .875rem; white-space: pre-wrap; overflow-wrap: anywhere; }
        .wa-in { align-self: flex-start; background: rgba(127,127,127,.14); }
        .wa-out { align-self: flex-end; background: rgba(37,211,102,.20); }
        .wa-meta { font-size: .68rem; opacity: .6; margin-top: .25rem; }
        .wa-note { font-size: .72rem; margin-top: .3rem; padding: .3rem .5rem; border-radius: .4rem; background: rgba(217,119,6,.14); }
        .wa-photo { display: block; max-width: 220px; max-height: 260px; border-radius: .5rem; margin-bottom: .3rem; }
        .wa-compose { padding: .75rem 1rem; border-top: 1px solid rgba(127,127,127,.2); }
        .wa-input { width: 100%; border: 1px solid rgba(127,127,127,.35); border-radius: .5rem; padding: .5rem .65rem; background: transparent; font-size: .875rem; }
        .wa-filters { display: flex; gap: .35rem; padding: .6rem .85rem; flex-wrap: wrap; }
        .wa-filter { font-size: .75rem; padding: .2rem .6rem; border-radius: 999px; border: 1px solid rgba(127,127,127,.3); cursor: pointer; background: transparent; }
        .wa-filter.is-on { background: rgba(37,211,102,.18); border-color: rgba(22,163,74,.5); font-weight: 600; }
        @media (max-width: 900px) {
            .wa-inbox { grid-template-columns: minmax(0, 1fr); }
            .wa-inbox.has-chat .wa-list { display: none; }
            .wa-messages { height: 60vh; }
        }
    </style>

    <div wire:poll.15s class="wa-inbox {{ $this->selected ? 'has-chat' : '' }}">
        {{-- Conversations --}}
        <div class="wa-list">
            <div style="padding:.6rem .85rem 0;">
                <input type="search" wire:model.live.debounce.400ms="search" class="wa-input" placeholder="Search name, member ID or number">
            </div>
            <div class="wa-filters">
                @foreach (['all' => 'All', 'unread' => 'Unread', 'unknown' => 'Not linked'] as $key => $label)
                    <button type="button" wire:click="$set('filter', '{{ $key }}')" class="wa-filter {{ $filter === $key ? 'is-on' : '' }}">{{ $label }}</button>
                @endforeach
            </div>
            <div class="wa-list-scroll">
                @forelse ($conversations as $c)
                    <button type="button" wire:key="conv-{{ $c->wa_id }}" wire:click="open('{{ $c->wa_id }}')" class="wa-row {{ $this->selected === $c->wa_id ? 'is-active' : '' }}">
                        <div class="wa-row-top">
                            <span style="font-weight:{{ $c->unread ? 700 : 500 }};overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                {{ $c->name }} @if ($c->matri_id)<span style="opacity:.6;font-weight:400;">({{ $c->matri_id }})</span>@endif
                            </span>
                            <span style="display:flex;gap:.35rem;align-items:center;flex-shrink:0;">
                                <span style="font-size:.7rem;opacity:.6;">{{ $c->last?->created_at?->isToday() ? $c->last->created_at->format('g:i a') : $c->last?->created_at?->format('j M') }}</span>
                                @if ($c->unread)<span class="wa-unread">{{ $c->unread }}</span>@endif
                            </span>
                        </div>
                        <div class="wa-row-preview">
                            @if ($c->last?->direction === 'out')You: @endif
                            {{ $c->last?->body ?: ($c->last?->type === 'image' ? 'Photo' : ucfirst((string) $c->last?->type)) }}
                        </div>
                    </button>
                @empty
                    <p style="padding:1rem;font-size:.875rem;opacity:.6;">No conversations{{ $search !== '' || $filter !== 'all' ? ' match' : ' yet' }}.</p>
                @endforelse
            </div>
        </div>

        {{-- Chat --}}
        <div class="wa-chat">
            @if (! $this->selected)
                <p style="padding:2rem;text-align:center;opacity:.6;font-size:.875rem;">Choose a conversation.</p>
            @else
                <div class="wa-head">
                    <div style="min-width:0;">
                        <button type="button" wire:click="$set('selected', null)" style="font-size:.8rem;opacity:.7;margin-right:.4rem;">&larr;</button>
                        <strong>+{{ $this->selected }}</strong>
                        @if ($profile)
                            &middot; <a href="{{ $this->profileUrl($profile) }}" target="_blank" style="text-decoration:underline;">{{ $profile->full_name }} ({{ $profile->matri_id }})</a>
                        @endif
                    </div>
                    <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                        <x-filament::button size="xs" color="gray" wire:click="markUnread">Mark unread</x-filament::button>
                    </div>
                </div>

                @unless ($profile)
                    <div style="padding:.6rem 1rem;border-bottom:1px solid rgba(127,127,127,.2);font-size:.8rem;display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;">
                        <span>Not linked to a member.</span>
                        @foreach ($this->suggestedProfiles() as $s)
                            <x-filament::button size="xs" color="gray" wire:click="linkMember('{{ $s->matri_id }}')">Link {{ $s->matri_id }} ({{ $s->full_name }})</x-filament::button>
                        @endforeach
                        <form wire:submit="linkMember" style="display:flex;gap:.35rem;">
                            <input type="text" wire:model="linkMatriId" class="wa-input" style="width:9rem;padding:.25rem .5rem;" placeholder="Member ID">
                            <x-filament::button size="xs" type="submit">Link</x-filament::button>
                        </form>
                    </div>
                @endunless

                <div class="wa-messages" x-data x-init="$el.scrollTop = $el.scrollHeight" x-effect="$el.scrollTop = $el.scrollHeight">
                    @foreach ($thread as $m)
                        <div wire:key="msg-{{ $m->id }}" class="wa-bubble {{ $m->direction === 'out' ? 'wa-out' : 'wa-in' }}">
                            @if ($m->media_id && (in_array($m->type, ['image', 'sticker']) || ($m->type === 'document' && str_starts_with((string) $m->media_mime, 'image/'))))
                                <a href="{{ $this->mediaUrl($m) }}" target="_blank"><img src="{{ $this->mediaUrl($m) }}" alt="Photo from WhatsApp" class="wa-photo" loading="lazy"></a>
                                @if (! $m->profile_photo_id && $m->direction === 'in')
                                    <x-filament::button size="xs" color="success" wire:click="attachPhoto({{ $m->id }})" wire:loading.attr="disabled">
                                        Add to {{ $profile?->matri_id ?? 'profile' }}
                                    </x-filament::button>
                                @endif
                            @elseif ($m->media_id)
                                <em>{{ ucfirst($m->type) }}</em> &middot; <a href="{{ $this->mediaUrl($m) }}" target="_blank" style="text-decoration:underline;">open</a>
                            @endif
                            @if ($m->body)<div>{{ $m->body }}</div>@endif
                            @if (! $m->body && ! $m->media_id)<em style="opacity:.7;">{{ ucfirst($m->type) }} message</em>@endif
                            <div class="wa-meta">
                                {{ $m->created_at?->format('j M, g:i a') }}
                                @if ($m->direction === 'out')
                                    &middot; {{ $m->status === 'read' ? 'Read' : ($m->status === 'delivered' ? 'Delivered' : ucfirst((string) $m->status)) }}
                                    @if ($m->note === 'auto-reply' || $m->note === 'photo-reply') &middot; automatic @elseif ($m->type === 'template') &middot; approved message @endif
                                @endif
                            </div>
                            @if ($m->note && ! in_array($m->note, ['auto-reply', 'photo-reply', 'test']) && (! str_starts_with($m->note, 'template:') || $m->status === 'failed'))
                                <div class="wa-note">{{ $m->note }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="wa-compose">
                    @if ($hoursLeft !== null)
                        <form wire:submit="send" style="display:flex;gap:.5rem;align-items:flex-end;">
                            <textarea wire:model="reply" rows="2" class="wa-input" placeholder="Type a reply" maxlength="4000"
                                x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); $wire.send() }"></textarea>
                            <x-filament::button type="submit" color="success" wire:loading.attr="disabled">Send</x-filament::button>
                        </form>
                        <p style="font-size:.7rem;opacity:.6;margin-top:.35rem;">Enter sends, Shift+Enter for a new line. You can reply freely for {{ $hoursLeft }} more {{ $hoursLeft === 1 ? 'hour' : 'hours' }} (WhatsApp's 24-hour rule).</p>
                    @elseif (! \App\Support\WhatsAppCloud::templatesEnabled())
                        <p style="font-size:.8rem;opacity:.75;">WhatsApp only allows a free reply within 24 hours of the member's last message. The approved "please reply" message can be sent once a payment card is added in WhatsApp Manager and "Send approved messages" is switched on in Settings &rarr; WhatsApp API. Until then, call the member or message them from the office phone.</p>
                    @else
                        <p style="font-size:.8rem;opacity:.75;">WhatsApp only allows a free reply within 24 hours of the member's last message. Send the approved "please reply" message: when they answer, you can write to them freely again.</p>
                        @php $lang = \App\Support\WhatsAppCloud::languageFor($profile); @endphp
                        <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.5rem;">
                            <x-filament::button size="sm" color="success" wire:click="sendReplyWaiting('{{ $lang }}')" wire:loading.attr="disabled">
                                Ask them to reply ({{ \App\Support\WhatsAppCloud::LANGUAGES[$lang] }})
                            </x-filament::button>
                            @php $other = $lang === 'en' ? 'kn' : 'en'; @endphp
                            <x-filament::button size="sm" color="gray" wire:click="sendReplyWaiting('{{ $other }}')" wire:loading.attr="disabled">
                                In {{ \App\Support\WhatsAppCloud::LANGUAGES[$other] }}
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
