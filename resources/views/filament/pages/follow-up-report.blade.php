<x-filament-panels::page>
    @php
        $overdue = $this->getOverdueFollowUps();
        $today = $this->getTodayFollowUps();
        $upcoming = $this->getUpcomingFollowUps();
    @endphp

    {{-- ───────────── Overdue ───────────── --}}
    <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="danger">
        <x-slot name="heading">Overdue ({{ $overdue->count() }})</x-slot>
        <x-slot name="description">Follow-ups whose date has passed.</x-slot>

        @forelse ($overdue as $note)
            <x-admin.member-row
                :photo="$note->profile?->primaryPhoto?->photo_url"
                :name="$note->profile?->full_name ?? '—'"
                :subtitle="$note->profile ? '(' . $note->profile->matri_id . ')' : null"
                :href="$note->profile ? route('filament.admin.resources.users.view', $note->profile_id) : null"
                :first="$loop->first">
                <span style="width:100%;opacity:.9;">{{ $note->note }}</span>
                <span style="color:#dc2626;font-weight:500;">Follow-up {{ $note->follow_up_date->format('d M Y') }} ({{ $note->follow_up_date->diffForHumans() }})</span>
                <span>By {{ $note->adminUser?->name ?? 'Admin' }}</span>
                <span>{{ $note->profile?->user?->phone ?? '—' }}</span>

                <x-slot name="actions">
                    @if ($note->profile?->user?->phone)
                        @php $phone = preg_replace('/[^0-9]/', '', $note->profile->user->phone); if (strlen($phone) === 10) $phone = '91' . $phone; @endphp
                        <x-filament::button tag="a" size="sm" color="success" icon="heroicon-o-chat-bubble-left-right" href="https://wa.me/{{ $phone }}">WhatsApp</x-filament::button>
                    @endif
                    @if ($note->profile)
                        <x-filament::button tag="a" size="sm" color="primary" icon="heroicon-o-eye" href="{{ route('filament.admin.resources.users.view', $note->profile_id) }}">View</x-filament::button>
                    @else
                        <span style="font-size:.75rem;opacity:.55;font-style:italic;">Member deleted</span>
                    @endif
                </x-slot>
            </x-admin.member-row>
        @empty
            <p style="opacity:.6;font-size:.875rem;">No overdue follow-ups.</p>
        @endforelse
    </x-filament::section>

    {{-- ───────────── Today ───────────── --}}
    <x-filament::section icon="heroicon-o-calendar-days" icon-color="warning">
        <x-slot name="heading">Today ({{ $today->count() }})</x-slot>
        <x-slot name="description">Follow-ups scheduled for today.</x-slot>

        @forelse ($today as $note)
            <x-admin.member-row
                :photo="$note->profile?->primaryPhoto?->photo_url"
                :name="$note->profile?->full_name ?? '—'"
                :subtitle="$note->profile ? '(' . $note->profile->matri_id . ')' : null"
                :href="$note->profile ? route('filament.admin.resources.users.view', $note->profile_id) : null"
                :first="$loop->first">
                <span style="width:100%;opacity:.9;">{{ $note->note }}</span>
                <span style="font-weight:500;">Follow-up: Today</span>
                <span>By {{ $note->adminUser?->name ?? 'Admin' }}</span>
                <span>{{ $note->profile?->user?->phone ?? '—' }}</span>

                <x-slot name="actions">
                    @if ($note->profile?->user?->phone)
                        @php $phone = preg_replace('/[^0-9]/', '', $note->profile->user->phone); if (strlen($phone) === 10) $phone = '91' . $phone; @endphp
                        <x-filament::button tag="a" size="sm" color="success" icon="heroicon-o-chat-bubble-left-right" href="https://wa.me/{{ $phone }}">WhatsApp</x-filament::button>
                    @endif
                    @if ($note->profile)
                        <x-filament::button tag="a" size="sm" color="primary" icon="heroicon-o-eye" href="{{ route('filament.admin.resources.users.view', $note->profile_id) }}">View</x-filament::button>
                    @else
                        <span style="font-size:.75rem;opacity:.55;font-style:italic;">Member deleted</span>
                    @endif
                </x-slot>
            </x-admin.member-row>
        @empty
            <p style="opacity:.6;font-size:.875rem;">No follow-ups scheduled for today.</p>
        @endforelse
    </x-filament::section>

    {{-- ───────────── Upcoming 7 days ───────────── --}}
    <x-filament::section icon="heroicon-o-clock" icon-color="info">
        <x-slot name="heading">Upcoming 7 Days ({{ $upcoming->count() }})</x-slot>
        <x-slot name="description">Follow-ups scheduled within the next week.</x-slot>

        @forelse ($upcoming as $note)
            <x-admin.member-row
                :photo="$note->profile?->primaryPhoto?->photo_url"
                :name="$note->profile?->full_name ?? '—'"
                :subtitle="$note->profile ? '(' . $note->profile->matri_id . ')' : null"
                :href="$note->profile ? route('filament.admin.resources.users.view', $note->profile_id) : null"
                :first="$loop->first">
                <span style="width:100%;opacity:.9;">{{ $note->note }}</span>
                <span>Follow-up {{ $note->follow_up_date->format('d M Y') }} ({{ $note->follow_up_date->diffForHumans() }})</span>
                <span>By {{ $note->adminUser?->name ?? 'Admin' }}</span>

                <x-slot name="actions">
                    @if ($note->profile)
                        <x-filament::button tag="a" size="sm" color="primary" icon="heroicon-o-eye" href="{{ route('filament.admin.resources.users.view', $note->profile_id) }}">View</x-filament::button>
                    @else
                        <span style="font-size:.75rem;opacity:.55;font-style:italic;">Member deleted</span>
                    @endif
                </x-slot>
            </x-admin.member-row>
        @empty
            <p style="opacity:.6;font-size:.875rem;">No follow-ups scheduled for the next 7 days.</p>
        @endforelse
    </x-filament::section>
</x-filament-panels::page>
