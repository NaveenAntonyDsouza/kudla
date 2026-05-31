<x-filament-panels::page>
    @php
        $expiring = $this->getExpiring();
        $expired = $this->getExpired();
    @endphp

    {{-- ───────────── Expiring within 7 days ───────────── --}}
    <x-filament::section icon="heroicon-o-clock" icon-color="warning">
        <x-slot name="heading">Expiring Within 7 Days ({{ $expiring->count() }})</x-slot>
        <x-slot name="description">Memberships ending in the next 7 days — reach out to renew.</x-slot>

        @forelse ($expiring as $membership)
            @php $profile = $membership->user?->profile; @endphp
            @if ($profile)
                <x-admin.member-row
                    :photo="$profile->primaryPhoto?->photo_url"
                    :name="$profile->full_name"
                    :subtitle="'(' . $profile->matri_id . ')'"
                    :href="route('filament.admin.resources.users.view', $profile->id)"
                    :first="$loop->first">
                    <x-admin.pill color="warning">{{ $membership->plan?->plan_name ?? 'Plan' }}</x-admin.pill>
                    <span>Expires {{ $membership->ends_at?->format('d M Y') }} ({{ $membership->ends_at?->diffForHumans() }})</span>
                    <span>{{ $profile->user?->phone ?? '—' }}</span>

                    <x-slot name="actions">
                        @if ($profile->user?->phone)
                            @php $phone = preg_replace('/[^0-9]/', '', $profile->user->phone); if (strlen($phone) === 10) $phone = '91' . $phone; @endphp
                            <x-filament::button tag="a" size="sm" color="success" icon="heroicon-o-chat-bubble-left-right"
                                href="https://wa.me/{{ $phone }}?text={{ urlencode('Hi ' . $profile->full_name . ', your membership plan is expiring soon. Renew now to continue enjoying premium features!') }}">
                                WhatsApp
                            </x-filament::button>
                        @endif
                    </x-slot>
                </x-admin.member-row>
            @endif
        @empty
            <p style="opacity:.6;font-size:.875rem;">No memberships expiring in the next 7 days.</p>
        @endforelse
    </x-filament::section>

    {{-- ───────────── Already expired ───────────── --}}
    <x-filament::section icon="heroicon-o-x-circle" icon-color="danger">
        <x-slot name="heading">Expired Memberships ({{ $expired->count() }})</x-slot>
        <x-slot name="description">Memberships that have already lapsed.</x-slot>

        @forelse ($expired as $membership)
            @php $profile = $membership->user?->profile; @endphp
            @if ($profile)
                <x-admin.member-row
                    :photo="$profile->primaryPhoto?->photo_url"
                    :name="$profile->full_name"
                    :subtitle="'(' . $profile->matri_id . ')'"
                    :href="route('filament.admin.resources.users.view', $profile->id)"
                    :first="$loop->first">
                    <x-admin.pill color="gray">{{ $membership->plan?->plan_name ?? 'Plan' }}</x-admin.pill>
                    <span style="color:#dc2626;">Expired {{ $membership->ends_at?->format('d M Y') }} ({{ $membership->ends_at?->diffForHumans() }})</span>
                    <span>{{ $profile->user?->phone ?? '—' }}</span>

                    <x-slot name="actions">
                        @if ($profile->user?->phone)
                            @php $phone = preg_replace('/[^0-9]/', '', $profile->user->phone); if (strlen($phone) === 10) $phone = '91' . $phone; @endphp
                            <x-filament::button tag="a" size="sm" color="success" icon="heroicon-o-chat-bubble-left-right"
                                href="https://wa.me/{{ $phone }}">
                                WhatsApp
                            </x-filament::button>
                        @endif
                    </x-slot>
                </x-admin.member-row>
            @endif
        @empty
            <p style="opacity:.6;font-size:.875rem;">No expired memberships.</p>
        @endforelse
    </x-filament::section>
</x-filament-panels::page>
